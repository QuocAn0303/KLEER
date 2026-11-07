<?php

declare(strict_types=1);

namespace Kleer\Services;

use Kleer\Config\QuizQuestions;
use Kleer\Contracts\ProductRepositoryInterface;
use Kleer\Contracts\QuizProductRepositoryInterface;
use Kleer\Exceptions\RoutineUnavailableException;
use Kleer\Repositories\WooCommerceProductRepository;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Skin Quiz Evaluation and Personalized Routine Recommendation Engine.
 *
 * Layer: Services (Domain Application Service)
 * Responsibility: Tính điểm weighted matching, phân loại hồ sơ da liễu,
 *                 truy vấn sản phẩm theo Custom Taxonomies và thực thi Safe Fallback.
 *                 Hỗ trợ đồng thời:
 *                   - v1: Chuẩn 5 câu hỏi hiện hành (legacy).
 *                   - v4: Bản nháp 6 câu / 27 lựa chọn của PO (đang dùng trên UI để nghiệm thu).
 * Boundary: Không xử lý HTTP request/response trực tiếp; có thể tái sử dụng qua CLI/REST/GraphQL.
 */
class QuizEngineService
{
    private ?ProductRepositoryInterface $repository;

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $catalog;

    /**
     * @param ProductRepositoryInterface|list<array<string, mixed>>|null $catalogOrRepository Danh mục sản phẩm hoặc Repository
     * @param list<array<string, mixed>>|null $catalog Danh mục sản phẩm tùy biến khi tham số 1 là Repository
     */
    public function __construct(
        ProductRepositoryInterface|array|null $catalogOrRepository = null,
        ?array $catalog = null
    ) {
        if ($catalogOrRepository instanceof ProductRepositoryInterface) {
            $this->repository = $catalogOrRepository;
            $this->catalog = $catalog;
        } elseif (is_array($catalogOrRepository)) {
            $this->repository = null;
            $this->catalog = $catalogOrRepository;
        } else {
            $this->repository = null;
            $this->catalog = $catalog;
        }
    }


    /**
     * Xử lý toàn bộ payload Quiz submission và trả về dữ liệu đề xuất chuẩn schema.
     *
     * @param array<string, mixed> $payload Dữ liệu gửi lên đã được validate qua JSON Schema
     * @param string|null $version Phiên bản nghiệp vụ ('v1' hoặc 'v4')
     * @return array<string, mixed>
     */
    public function processSubmission(array $payload, ?string $version = null): array
    {
        $resolvedVersion = $version ?? (string) ($payload['version'] ?? 'v1');
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];

        // Ràng buộc nghiệp vụ: validate câu hỏi & đáp án hợp lệ, reject payload sai
        $this->validateSubmissionAnswers($answers, $resolvedVersion);

        if ($resolvedVersion === 'v4') {
            return $this->processSubmissionV4($payload);
        }

        return $this->processSubmissionV1($payload);
    }

    /**
     * Kiểm tra tính hợp lệ về mặt nghiệp vụ của danh sách câu trả lời.
     * Reject câu lạ, đáp án lạ, câu trùng, hoặc thiếu câu bắt buộc.
     *
     * @param list<array<string, mixed>> $answers
     * @param string $version
     * @throws \InvalidArgumentException Khi payload không thỏa ràng buộc nghiệp vụ
     */
    public function validateSubmissionAnswers(array $answers, string $version): void
    {
        if (empty($answers)) {
            throw new \InvalidArgumentException('Danh sách câu trả lời không được để trống.');
        }

        $seenQuestions = [];
        $errors = [];

        if ($version === 'v4') {
            $v4Def = QuizQuestions::getV4Questions();
            $requiredKeys = array_keys($v4Def);

            foreach ($answers as $idx => $ans) {
                $qId = (string) ($ans['question_id'] ?? '');
                $val = $ans['answer'] ?? null;

                if (!isset($v4Def[$qId])) {
                    $errors[] = sprintf('Câu hỏi không hợp lệ tại vị trí %d: "%s".', $idx, $qId);
                    continue;
                }

                if (isset($seenQuestions[$qId])) {
                    $errors[] = sprintf('Câu hỏi bị lặp lại: "%s". Mỗi câu chỉ được trả lời 1 lần.', $qId);
                }
                $seenQuestions[$qId] = true;

                if (is_array($val)) {
                    $errors[] = sprintf('Câu hỏi "%s" chỉ nhận một lựa chọn, không nhận mảng.', $qId);
                    continue;
                }

                if (!is_scalar($val) && $val !== null) {
                    $errors[] = sprintf('Giá trị trả lời không hợp lệ cho câu hỏi "%s".', $qId);
                    continue;
                }

                $valStr = (string) $val;
                if (!isset($v4Def[$qId]['options'][$valStr])) {
                    $errors[] = sprintf('Lựa chọn không hợp lệ "%s" cho câu hỏi "%s".', $valStr, $qId);
                }
            }

            foreach ($requiredKeys as $req) {
                if (!isset($seenQuestions[$req])) {
                    $errors[] = sprintf('Thiếu câu hỏi bắt buộc: "%s". Khách bắt buộc trả lời đủ 6 câu.', $req);
                }
            }
        } else {
            // v1: 5 câu hỏi cốt lõi
            $v1Def = QuizQuestions::getQuestions();
            $requiredKeys = array_keys($v1Def);

            foreach ($answers as $idx => $ans) {
                $qId = (string) ($ans['question_id'] ?? '');
                $val = $ans['answer'] ?? null;

                if (!isset($v1Def[$qId])) {
                    $errors[] = sprintf('Câu hỏi không hợp lệ tại vị trí %d: "%s".', $idx, $qId);
                    continue;
                }

                if (isset($seenQuestions[$qId])) {
                    $errors[] = sprintf('Câu hỏi bị lặp lại: "%s". Mỗi câu chỉ được trả lời 1 lần.', $qId);
                }
                $seenQuestions[$qId] = true;

                $selectedOpts = is_array($val) ? $val : [$val];
                foreach ($selectedOpts as $opt) {
                    $optStr = (string) $opt;
                    if (!isset($v1Def[$qId]['options'][$optStr])) {
                        $errors[] = sprintf('Lựa chọn không hợp lệ "%s" cho câu hỏi "%s".', $optStr, $qId);
                    }
                }
            }

            foreach ($requiredKeys as $req) {
                if (!isset($seenQuestions[$req])) {
                    $errors[] = sprintf('Thiếu câu hỏi bắt buộc: "%s". Khách bắt buộc trả lời đủ 5 câu.', $req);
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
    }

    /**
     * Xử lý luồng v1 (legacy 5 câu).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function processSubmissionV1(array $payload): array
    {
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        $this->validateAnswers($answers);
        $skinProfileData = $this->calculateSkinProfile($answers);
        $routineSteps = (int) ($skinProfileData['routine_steps'] ?? 4);

        $catalog = $this->resolveCatalog();

        $recommendedProducts = $this->recommendRoutine(
            $skinProfileData['profile'],
            $routineSteps,
            $catalog
        );

        $submissionId = $this->generateSubmissionId($payload['session_id'] ?? '');

        return [
            'status' => 'ok',
            'data' => [
                'submission_id' => $submissionId,
                'skin_profile' => [
                    'code' => $skinProfileData['profile']['code'],
                    'label' => $skinProfileData['profile']['label'],
                    'concerns' => $skinProfileData['profile']['concerns'],
                ],
                'recommended_products' => $recommendedProducts,
                'disclaimer' => 'Gợi ý tham khảo theo câu trả lời, không thay thế tư vấn bác sĩ. Với da nhạy cảm, ưu tiên sản phẩm dịu nhẹ. Thử một lượng nhỏ trên vùng da dưới quai hàm trước khi sử dụng toàn mặt.',
            ],
        ];
    }

    /** Compatibility entry point named in task L01-G4-01. */
    public function calculate_routine(array $payload): array
    {
        return $this->processSubmission($payload);
    }

    /** Reject missing, duplicated, unknown or multiple-choice answers for this five-question quiz. */
    public function validateAnswers(array $answers): void
    {
        $questions = QuizQuestions::getQuestions();
        $seen = [];
        foreach ($answers as $answer) {
            $id = $answer['question_id'] ?? '';
            $option = $answer['answer'] ?? null;
            if (!is_string($id) || !isset($questions[$id]) || isset($seen[$id])
                || !is_string($option) || !isset($questions[$id]['options'][$option])) {
                throw new \InvalidArgumentException('Cần trả lời đúng một lựa chọn hợp lệ cho mỗi câu hỏi.');
            }
            $seen[$id] = true;
        }
        if (count($seen) !== count($questions)) {
            throw new \InvalidArgumentException('Cần trả lời đủ 5 câu hỏi.');
        }
    }

    /**
     * Xử lý luồng v4 (Bản nháp 6 câu / 27 lựa chọn của PO Phát).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function processSubmissionV4(array $payload): array
    {
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        $eval = $this->calculateSkinProfileV4($answers);

        $catalog = $this->resolveCatalog(true);

        $recommendation = $this->recommendRoutineV4(
            $eval['skin_type'],
            $eval['is_sensitive'],
            $eval['primary_concern'],
            $eval['routine_steps_requested'],
            $eval['uses_sunscreen_makeup'],
            $catalog
        );

        $submissionId = $this->generateSubmissionId($payload['session_id'] ?? '');

        return [
            'status' => 'ok',
            'data' => [
                'submission_id' => $submissionId,
                'version' => 'v4-draft',
                'skin_profile' => [
                    'code' => $eval['code'],
                    'label' => $eval['label'],
                    'primary_type' => $eval['skin_type'],
                    'primary_concern' => $eval['primary_concern'],
                    'is_sensitive' => $eval['is_sensitive'],
                    'sensitivity_score' => $eval['scores']['sensitive'],
                    'concerns' => $eval['concerns_list'],
                ],
                'routine_steps_requested' => $eval['routine_steps_requested'],
                'routine_steps_selected' => $recommendation['selected_steps'],
                'recommended_products' => $recommendation['products'],
                'missing_steps' => $recommendation['missing_steps'],
                'warnings' => $recommendation['warnings'],
                'disclaimer' => 'Bản nháp phác đồ 6 bước v4 (đang chờ PO Nguyễn Tấn Phát phê duyệt chính thức). Vui lòng thử trước sản phẩm 24h trên vùng da quai hàm.',
            ],
        ];
    }

    /**
     * Tính điểm v4 theo đúng ma trận điểm và tie-break của Phát.
     *
     * @param list<array<string, mixed>> $answers
     * @return array<string, mixed>
     */
    public function calculateSkinProfileV4(array $answers): array
    {
        $v4Def = QuizQuestions::getV4Questions();

        $scores = [
            'sensitive'   => 0,
            'dry'         => 0,
            'combination' => 0,
            'oily'        => 0,
            'normal'      => 0,
        ];

        $primaryConcern = 'none';
        $routineStepsRequested = 4;
        $usesSunscreenMakeup = true;

        foreach ($answers as $ans) {
            $qId = (string) ($ans['question_id'] ?? '');
            $opt = (string) ($ans['answer'] ?? '');

            if (!isset($v4Def[$qId]['options'][$opt])) {
                continue;
            }

            $optDef = $v4Def[$qId]['options'][$opt];

            // Q1, Q2, Q3: cộng điểm 5 loại da
            if (isset($optDef['scores']) && is_array($optDef['scores'])) {
                foreach ($optDef['scores'] as $type => $pts) {
                    $scores[$type] = ($scores[$type] ?? 0) + (int) $pts;
                }
            }

            // Q4: Vấn đề chính
            if (isset($optDef['concern'])) {
                $primaryConcern = (string) $optDef['concern'];
            }

            // Q5: Số lượng sản phẩm routine mong muốn (2..6)
            if (isset($optDef['routine_steps'])) {
                $routineStepsRequested = (int) $optDef['routine_steps'];
            }

            // Q6: Có dùng kem chống nắng / trang điểm hay không
            if (isset($optDef['uses_sunscreen_makeup'])) {
                $usesSunscreenMakeup = (bool) $optDef['uses_sunscreen_makeup'];
            }
        }

        // Ngưỡng bật cờ nhạy cảm: điểm Nhạy cảm >= 4
        $isSensitive = $scores['sensitive'] >= 4;

        // Chọn loại da chính có điểm cao nhất.
        // Hòa điểm: ưu tiên theo thứ tự an toàn: Nhạy cảm > Khô > Hỗn hợp > Dầu > Thường
        $priorityOrder = ['sensitive', 'dry', 'combination', 'oily', 'normal'];
        $maxScore = -1;
        $primarySkinType = 'combination';

        foreach ($priorityOrder as $type) {
            $s = $scores[$type] ?? 0;
            if ($s > $maxScore) {
                $maxScore = $s;
                $primarySkinType = $type;
            }
        }

        $typeNames = [
            'sensitive'   => 'Da nhạy cảm',
            'dry'         => 'Da khô',
            'combination' => 'Da hỗn hợp',
            'oily'        => 'Da dầu',
            'normal'      => 'Da thường',
        ];

        $concernNames = [
            'acne'       => 'Mụn',
            'dark_spots' => 'Thâm / Nám',
            'aging'      => 'Lão hóa',
            'pores'      => 'Lỗ chân lông',
            'none'       => 'Không',
        ];

        $code = $primarySkinType . '_' . $primaryConcern . ($isSensitive ? '_sensitive' : '');
        $label = ($typeNames[$primarySkinType] ?? 'Da hỗn hợp') . ' (' . ($concernNames[$primaryConcern] ?? 'Duy trì') . ')';

        $concernsList = [];
        if ($primaryConcern !== 'none') {
            $concernsList[] = 'Vấn đề ưu tiên: ' . ($concernNames[$primaryConcern] ?? $primaryConcern);
        }
        if ($isSensitive) {
            $concernsList[] = 'Cờ nhạy cảm bật (loại bỏ hoạt chất mạnh BHA, AHA, PHA, Vitamin C ≥ 5%)';
        }

        return [
            'skin_type'               => $primarySkinType,
            'is_sensitive'            => $isSensitive,
            'primary_concern'         => $primaryConcern,
            'routine_steps_requested' => $routineStepsRequested,
            'uses_sunscreen_makeup'   => $usesSunscreenMakeup,
            'scores'                  => $scores,
            'code'                    => $code,
            'label'                   => $label,
            'concerns_list'           => $concernsList,
        ];
    }

    /**
     * Đề xuất Routine 2..6 bước theo đúng quy tắc chọn bước và xếp hạng v4.
     *
     * @param list<array<string, mixed>> $catalog
     * @return array{selected_steps: list<string>, products: list<array<string, mixed>>, missing_steps: list<string>, warnings: list<string>}
     */
    public function recommendRoutineV4(
        string $skinType,
        bool $isSensitive,
        string $primaryConcern,
        int $requestedSteps,
        bool $usesSunscreenMakeup,
        array $catalog
    ): array {
        // 1. Xác định hạng nền của từng bước
        // Sữa rửa mặt (1), Chống nắng (2), Tẩy trang (2.1), Serum (3), Toner (4), Dưỡng ẩm (5)
        $stepRanks = [
            'cleanser'       => 1.0,
            'sunscreen'      => 2.0,
            'makeup_remover' => 2.1,
            'serum'          => 3.0,
            'toner'          => 4.0,
            'moisturizer'    => 5.0,
        ];

        // 2. Quy tắc loại / giữ Chống nắng + Tẩy trang
        // Áp dụng khi Q6 = Không và vấn đề chính KHÔNG nằm trong ['dark_spots', 'aging']
        if (!$usesSunscreenMakeup) {
            if ($primaryConcern !== 'dark_spots' && $primaryConcern !== 'aging') {
                $stepRanks['sunscreen'] = 99.0;
                $stepRanks['makeup_remover'] = 99.0;
            }
        }

        // 3. Quy tắc bước được vấn đề chính đẩy lên hạng 2.5
        // Mụn: Toner nếu cờ nhạy cảm TẮT; Serum nếu cờ nhạy cảm BẬT.
        // Thâm/nám: Serum.
        // Lão hóa: Serum.
        // Lỗ chân lông: Toner.
        // Không: không đẩy.
        if ($primaryConcern === 'acne') {
            if ($isSensitive) {
                $stepRanks['serum'] = 2.5;
            } else {
                $stepRanks['toner'] = 2.5;
            }
        } elseif ($primaryConcern === 'dark_spots') {
            $stepRanks['serum'] = 2.5;
        } elseif ($primaryConcern === 'aging') {
            $stepRanks['serum'] = 2.5;
        } elseif ($primaryConcern === 'pores') {
            $stepRanks['toner'] = 2.5;
        }

        // Lọc các bước không bị loại (rank < 90) và sắp xếp theo hạng tăng dần
        $availableSteps = array_filter($stepRanks, static fn(float $rank): bool => $rank < 90.0);
        asort($availableSteps);

        // Lấy N bước có hạng nhỏ nhất
        $selectedStepKeys = array_slice(array_keys($availableSteps), 0, $requestedSteps);

        // Thứ tự trình bày chuẩn chu trình chăm sóc da
        $displayOrder = ['makeup_remover', 'cleanser', 'toner', 'serum', 'moisturizer', 'sunscreen'];
        usort($selectedStepKeys, static function (string $a, string $b) use ($displayOrder): int {
            $posA = array_search($a, $displayOrder, true);
            $posB = array_search($b, $displayOrder, true);
            return ($posA !== false && $posB !== false) ? $posA <=> $posB : 0;
        });

        $recommendedProducts = [];
        $missingSteps = [];
        $selectedProductIds = [];

        foreach ($selectedStepKeys as $step) {
            $product = $this->findBestProductForStepV4(
                $step,
                $skinType,
                $isSensitive,
                $primaryConcern,
                $catalog,
                $selectedProductIds
            );

            if ($product !== null) {
                $selectedProductIds[] = $product['id'];
                $recommendedProducts[] = $product;
            } else {
                $missingSteps[] = $step;
            }
        }

        $warnings = [];
        if (!empty($missingSteps)) {
            $stepNames = [
                'makeup_remover' => 'Tẩy trang',
                'moisturizer'    => 'Dưỡng ẩm',
                'cleanser'       => 'Sữa rửa mặt',
                'toner'          => 'Toner',
                'serum'          => 'Serum',
                'sunscreen'      => 'Chống nắng',
            ];
            $translated = array_map(static fn($s) => $stepNames[$s] ?? $s, $missingSteps);
            $warnings[] = sprintf('Kho dữ liệu hiện chưa có SKU cho các bước: %s.', implode(', ', $translated));
        }

        if (count($recommendedProducts) < 2) {
            $warnings[] = 'CẢNH BÁO DỮ LIỆU: Routine đề xuất có ít hơn 2 sản phẩm khả dụng. Vui lòng bổ sung SKU.';
        }

        return [
            'selected_steps' => $selectedStepKeys,
            'products'       => $recommendedProducts,
            'missing_steps'  => $missingSteps,
            'warnings'       => $warnings,
        ];
    }

    /**
     * Tìm sản phẩm tốt nhất cho bước Routine v4 theo trọng số và tie-break.
     *
     * @param list<array<string, mixed>> $catalog
     * @param list<int> $excludeIds
     * @return array<string, mixed>|null
     */
    private function findBestProductForStepV4(
        string $step,
        string $skinType,
        bool $isSensitive,
        string $primaryConcern,
        array $catalog,
        array $excludeIds
    ): ?array {
        // Lọc sản phẩm đúng bước, phù hợp loại da khách và chưa chọn
        $candidates = array_values(array_filter($catalog, static function (array $item) use ($step, $skinType, $excludeIds): bool {
            $primaryStep = (string) ($item['routine_step'] ?? '');
            $secondaryStep = (string) ($item['secondary_step'] ?? '');

            if ($primaryStep !== $step && $secondaryStep !== $step) {
                return false;
            }
            if (in_array($item['id'] ?? null, $excludeIds, true)) {
                return false;
            }
            $types = (array) ($item['skin_types'] ?? []);
            return in_array($skinType, $types, true) || in_array('all_skin_types', $types, true);
        }));

        if (empty($candidates)) {
            return null;
        }

        // Khi cờ nhạy cảm BẬT: loại bỏ sản phẩm có hoạt chất mạnh
        if ($isSensitive) {
            $safeCandidates = array_values(array_filter($candidates, static function (array $item): bool {
                return empty($item['has_strong_actives']);
            }));

            if (!empty($safeCandidates)) {
                $candidates = $safeCandidates;
            } else {
                return null;
            }
        }

        // Tính điểm lựa chọn:
        // Điểm = 2 * (khớp vấn đề chính) + 1 * (khớp loại da riêng, không tính 'all_skin_types')
        // Hòa điểm: chọn giá thấp hơn; nếu bằng cả giá: chọn ID thấp hơn.
        usort($candidates, static function (array $a, array $b) use ($primaryConcern, $skinType): int {
            $concernsA = (array) ($a['concerns'] ?? []);
            $concernsB = (array) ($b['concerns'] ?? []);
            $typesA = (array) ($a['skin_types'] ?? []);
            $typesB = (array) ($b['skin_types'] ?? []);

            $scoreA = (in_array($primaryConcern, $concernsA, true) ? 2 : 0)
                + ((in_array($skinType, $typesA, true) && !in_array('all_skin_types', $typesA, true)) ? 1 : 0);
            $scoreB = (in_array($primaryConcern, $concernsB, true) ? 2 : 0)
                + ((in_array($skinType, $typesB, true) && !in_array('all_skin_types', $typesB, true)) ? 1 : 0);

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA; // Điểm cao hơn xếp trước
            }

            // Hòa điểm -> giá thấp hơn
            $priceA = (int) ($a['price'] ?? 0);
            $priceB = (int) ($b['price'] ?? 0);
            if ($priceA !== $priceB) {
                return $priceA <=> $priceB;
            }

            // Bằng cả giá -> ID thấp hơn để ổn định kết quả
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $chosen = $candidates[0];

        return [
            'id'           => (int) $chosen['id'],
            'stt'          => (int) ($chosen['stt'] ?? $chosen['id']),
            'name'         => (string) $chosen['name'],
            'price'        => (int) ($chosen['price'] ?? 0),
            'routine_step' => $step,
            'reason'       => (string) ($chosen['reason'] ?? sprintf('Sản phẩm tối ưu cho bước %s.', $step)),
        ];
    }

    /**
     * Tính điểm weighted matching v1.
     *
     * @param list<array<string, mixed>> $answers
     * @return array{profile: array{code: string, label: string, concerns: list<string>, primary_type: string, primary_concern: string, is_sensitive: bool}, routine_steps: int}
     */
    public function calculateSkinProfile(array $answers): array
    {
        $questions = QuizQuestions::getQuestions();

        $scores = [
            'skin_type' => [
                'oily' => 0,
                'dry' => 0,
                'combination' => 0,
                'normal' => 0,
            ],
            'sensitivity' => 0,
            'concerns' => [
                'acne' => 0,
                'dark_spots' => 0,
                'hydration' => 0,
                'aging' => 0,
            ],
            'routine_steps' => 4,
        ];

        foreach ($answers as $ans) {
            $qId = (string) ($ans['question_id'] ?? '');
            $userAns = $ans['answer'] ?? null;

            if (!isset($questions[$qId])) {
                continue;
            }

            $questionDef = $questions[$qId];
            $selectedOptions = is_array($userAns) ? $userAns : [$userAns];

            foreach ($selectedOptions as $optKey) {
                $optStr = (string) $optKey;
                if (!isset($questionDef['options'][$optStr]['weights'])) {
                    continue;
                }

                $weights = $questionDef['options'][$optStr]['weights'];

                if (isset($weights['skin_type']) && is_array($weights['skin_type'])) {
                    foreach ($weights['skin_type'] as $type => $val) {
                        $scores['skin_type'][$type] = ($scores['skin_type'][$type] ?? 0) + (int) $val;
                    }
                }

                if (isset($weights['sensitivity'])) {
                    $scores['sensitivity'] += (int) $weights['sensitivity'];
                }

                if (isset($weights['concerns']) && is_array($weights['concerns'])) {
                    foreach ($weights['concerns'] as $concern => $val) {
                        $scores['concerns'][$concern] = ($scores['concerns'][$concern] ?? 0) + (int) $val;
                    }
                }

                if (isset($weights['routine_steps'])) {
                    $scores['routine_steps'] = (int) $weights['routine_steps'];
                }
            }
        }

        arsort($scores['skin_type']);
        $primaryType = (string) array_key_first($scores['skin_type']);
        if ($scores['skin_type'][$primaryType] === 0) {
            $primaryType = 'combination';
        }

        arsort($scores['concerns']);
        $primaryConcern = (string) array_key_first($scores['concerns']);

        $isSensitive = $scores['sensitivity'] >= 3;

        $code = $this->buildProfileCode($primaryType, $primaryConcern, $isSensitive);
        $label = $this->buildProfileLabel($primaryType, $primaryConcern, $isSensitive);
        $concernsList = $this->buildConcernsList($scores['concerns'], $isSensitive);

        return [
            'profile' => [
                'code' => $code,
                'label' => $label,
                'concerns' => $concernsList,
                'primary_type' => $primaryType,
                'primary_concern' => $primaryConcern,
                'is_sensitive' => $isSensitive,
            ],
            'routine_steps' => $scores['routine_steps'],
        ];
    }

    /**
     * Đề xuất phác đồ sản phẩm 3–4 bước có cơ chế Safe Fallback đảm bảo không rỗng (v1).
     *
     * @param array{primary_type: string, primary_concern: string, is_sensitive: bool, ...} $skinProfile
     * @param int $routineSteps Số bước (3 hoặc 4)
     * @param list<array<string, mixed>> $catalog
     * @return list<array<string, mixed>>
     */
    public function recommendRoutine(array $skinProfile, int $routineSteps, array $catalog): array
    {
        $requiredSteps = ($routineSteps === 3)
            ? ['cleanser', 'moisturizer', 'sunscreen']
            : ['cleanser', 'treatment', 'moisturizer', 'sunscreen'];

        $recommended = [];
        $selectedProductIds = [];
        $missingSteps = [];

        foreach ($requiredSteps as $step) {
            $product = $this->findBestProductForStep(
                $step,
                $skinProfile,
                $catalog,
                $selectedProductIds
            );

            if ($product !== null) {
                $selectedProductIds[] = $product['id'];
                $recommended[] = [
                    'id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'price' => (int) ($product['price'] ?? 0),
                    'routine_step' => $step,
                    'reason' => (string) ($product['reason'] ?? "Được tối ưu cho bước $step của phác đồ da."),
                ];
            } else {
                $missingSteps[] = $step;
            }
        }

        if ($missingSteps !== []) {
            throw new RoutineUnavailableException($missingSteps);
        }

        return $recommended;
    }

    /**
     * Tìm sản phẩm tối ưu nhất cho một bước Routine theo 3 tầng ưu tiên (v1).
     *
     * @param list<array<string, mixed>> $catalog
     * @param list<int> $excludeIds
     * @return array<string, mixed>|null
     */
    private function findBestProductForStep(
        string $step,
        array $profile,
        array $catalog,
        array $excludeIds
    ): ?array {
        $stepCandidates = array_values(array_filter($catalog, static function (array $item) use ($step, $excludeIds): bool {
            $stepMatch = ($item['routine_step'] ?? '') === $step
                || (($item['secondary_step'] ?? '') === $step);
            return $stepMatch && !in_array($item['id'] ?? null, $excludeIds, true);
        }));

        if (empty($stepCandidates)) {
            return null;
        }

        $targetType = $profile['is_sensitive'] ? 'sensitive' : $profile['primary_type'];
        $targetConcern = $profile['primary_concern'];
        if ($profile['is_sensitive']) {
            $stepCandidates = array_values(array_filter($stepCandidates, static function (array $p): bool {
                return (bool) ($p['is_gentle_fallback'] ?? false)
                    || in_array('sensitive', (array) ($p['skin_types'] ?? []), true);
            }));
        }

        // Tầng 1: Khớp cả loại da lẫn vấn đề
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            $concerns = (array) ($p['concerns'] ?? []);

            $typeMatch = in_array($targetType, $types, true) || in_array('all_skin_types', $types, true);
            $concernMatch = in_array($targetConcern, $concerns, true);

            if ($typeMatch && $concernMatch) {
                return $p;
            }
        }

        // Tầng 2: Khớp loại da
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            if (in_array($targetType, $types, true)
                && ((bool) ($p['is_gentle_fallback'] ?? false)
                    || in_array('soothing', (array) ($p['concerns'] ?? []), true))) {
                $this->recordFallback($step, 'partial');
                return $p;
            }
        }

        // Tầng 3: Safe Fallback lành tính
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            $isGentle = (bool) ($p['is_gentle_fallback'] ?? false);

            if ($isGentle || (in_array('all_skin_types', $types, true)
                && in_array('soothing', (array) ($p['concerns'] ?? []), true))) {
                $this->recordFallback($step, 'gentle');
                return $p;
            }
        }

        return null;
    }

    private function recordFallback(string $step, string $level): void
    {
        if (function_exists('do_action')) {
            do_action('kleer_quiz_fallback_used', $step, $level);
        }
    }

    private function resolveCatalog(bool $preferV4 = false): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $repository = $this->repository instanceof QuizProductRepositoryInterface
            ? $this->repository
            : new WooCommerceProductRepository();
        return $repository->getQuizCatalog(['version' => $preferV4 ? 'v4' : 'v1']);
    }

    private function buildProfileCode(string $type, string $concern, bool $isSensitive): string
    {
        if ($isSensitive) {
            return $type . '_sensitive';
        }

        return $type . '_' . $concern;
    }

    private function buildProfileLabel(string $type, string $concern, bool $isSensitive): string
    {
        $typeNames = [
            'oily' => 'Da dầu',
            'dry' => 'Da khô',
            'combination' => 'Da hỗn hợp',
            'normal' => 'Da thường',
        ];

        $concernNames = [
            'acne' => 'mụn & bã nhờn',
            'dark_spots' => 'thâm sạm xỉn màu',
            'hydration' => 'thiếu ẩm mất nước',
            'aging' => 'lão hóa & nếp nhăn',
        ];

        $typeName = $typeNames[$type] ?? 'Da hỗn hợp';
        $concernName = $concernNames[$concern] ?? 'cần cân bằng';

        if ($isSensitive) {
            return "{$typeName} nhạy cảm (Ưu tiên {$concernName})";
        }

        return "{$typeName} ({$concernName})";
    }

    /**
     * @param array<string, int> $concernsScores
     * @return list<string>
     */
    private function buildConcernsList(array $concernsScores, bool $isSensitive): array
    {
        $map = [
            'acne' => 'Mụn viêm & sợi bã nhờn bít tắc',
            'dark_spots' => 'Vết thâm mụn & tông màu da không đều',
            'hydration' => 'Thiếu nước bề mặt & hàng rào ẩm yếu',
            'aging' => 'Dấu hiệu nếp nhăn & kém đàn hồi',
        ];

        $list = [];
        if ($isSensitive) {
            $list[] = 'Dễ ửng đỏ, kích ứng châm chích khi thời tiết thay đổi';
        }

        foreach ($concernsScores as $k => $score) {
            if ($score > 0 && isset($map[$k])) {
                $list[] = $map[$k];
            }
        }

        return array_values(array_slice($list, 0, 4));
    }

    private function generateSubmissionId(string $sessionId): int
    {
        if ($sessionId === '') {
            return random_int(1000, 99999);
        }

        return (int) (abs(crc32($sessionId)) % 900000) + 100000;
    }
}
