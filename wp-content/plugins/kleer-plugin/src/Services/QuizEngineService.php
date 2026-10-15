<?php

declare(strict_types=1);

namespace Kleer\Services;

use Kleer\Config\QuizQuestions;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Skin Quiz Evaluation and Personalized Routine Recommendation Engine.
 *
 * Layer: Services (Domain Application Service)
 * Responsibility: Tính điểm weighted matching, phân loại hồ sơ da liễu,
 *                 truy vấn sản phẩm theo Custom Taxonomies và thực thi Safe Fallback.
 * Boundary: Không xử lý HTTP request/response trực tiếp; có thể tái sử dụng qua CLI/REST/GraphQL.
 */
class QuizEngineService
{
    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $catalog;

    /**
     * @param list<array<string, mixed>>|null $catalog Danh mục sản phẩm tùy biến (nếu null sẽ dùng mặc định)
     */
    public function __construct(?array $catalog = null)
    {
        $this->catalog = $catalog;
    }

    /**
     * Xử lý toàn bộ payload Quiz submission và trả về dữ liệu đề xuất chuẩn schema.
     *
     * @param array<string, mixed> $payload Dữ liệu gửi lên đã được validate qua JSON Schema
     * @return array<string, mixed>
     */
    public function processSubmission(array $payload): array
    {
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        $skinProfileData = $this->calculateSkinProfile($answers);
        $routineSteps = (int) ($skinProfileData['routine_steps'] ?? 4);

        $recommendedProducts = $this->recommendRoutine(
            $skinProfileData['profile'],
            $routineSteps,
            $this->catalog ?? QuizQuestions::getDefaultCatalog()
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
                'disclaimer' => 'Phác đồ được xây dựng dựa trên thuật toán da liễu tham vấn từ chuyên gia KLEER. Vui lòng thử trước một lượng nhỏ sản phẩm lên vùng da dưới quai hàm 24h trước khi sử dụng toàn mặt.',
            ],
        ];
    }

    /**
     * Tính điểm weighted matching và xếp hạng hồ sơ da (Skin Profile).
     *
     * @param list<array<string, mixed>> $answers Danh sách câu trả lời
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

                // Cộng dồn điểm Skin Type
                if (isset($weights['skin_type']) && is_array($weights['skin_type'])) {
                    foreach ($weights['skin_type'] as $type => $val) {
                        $scores['skin_type'][$type] = ($scores['skin_type'][$type] ?? 0) + (int) $val;
                    }
                }

                // Cộng dồn điểm Sensitivity
                if (isset($weights['sensitivity'])) {
                    $scores['sensitivity'] += (int) $weights['sensitivity'];
                }

                // Cộng dồn điểm Concerns
                if (isset($weights['concerns']) && is_array($weights['concerns'])) {
                    foreach ($weights['concerns'] as $concern => $val) {
                        $scores['concerns'][$concern] = ($scores['concerns'][$concern] ?? 0) + (int) $val;
                    }
                }

                // Thiết lập Routine steps (3 hoặc 4)
                if (isset($weights['routine_steps'])) {
                    $scores['routine_steps'] = (int) $weights['routine_steps'];
                }
            }
        }

        // Tìm loại da có điểm cao nhất
        arsort($scores['skin_type']);
        $primaryType = (string) array_key_first($scores['skin_type']);
        if ($scores['skin_type'][$primaryType] === 0) {
            $primaryType = 'combination'; // Fallback mặc định cho làn da Châu Á
        }

        // Tìm vấn đề da ưu tiên nhất
        arsort($scores['concerns']);
        $primaryConcern = (string) array_key_first($scores['concerns']);

        $isSensitive = $scores['sensitivity'] >= 3;

        // Xây dựng code và nhãn hiển thị
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
     * Đề xuất phác đồ sản phẩm 3–4 bước có cơ chế Safe Fallback đảm bảo không rỗng.
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
            }
        }

        return $recommended;
    }

    /**
     * Tìm sản phẩm tối ưu nhất cho một bước Routine theo 3 tầng ưu tiên.
     *
     * @param string $step Tên bước (cleanser, treatment, moisturizer, sunscreen)
     * @param array{primary_type: string, primary_concern: string, is_sensitive: bool, ...} $profile
     * @param list<array<string, mixed>> $catalog
     * @param list<int> $excludeIds Danh sách ID sản phẩm đã chọn ở bước khác
     * @return array<string, mixed>|null
     */
    private function findBestProductForStep(
        string $step,
        array $profile,
        array $catalog,
        array $excludeIds
    ): ?array {
        $stepCandidates = array_values(array_filter($catalog, static function (array $item) use ($step, $excludeIds): bool {
            return ($item['routine_step'] ?? '') === $step && !in_array($item['id'] ?? null, $excludeIds, true);
        }));

        if (empty($stepCandidates)) {
            return null;
        }

        $targetType = $profile['is_sensitive'] ? 'sensitive' : $profile['primary_type'];
        $targetConcern = $profile['primary_concern'];

        // Tầng 1 (Exact Match): Khớp cả loại da lẫn vấn đề điều trị
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            $concerns = (array) ($p['concerns'] ?? []);

            $typeMatch = in_array($targetType, $types, true) || in_array('all_skin_types', $types, true);
            $concernMatch = in_array($targetConcern, $concerns, true);

            if ($typeMatch && $concernMatch) {
                return $p;
            }
        }

        // Tầng 2 (Partial Match): Khớp loại da
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            if (in_array($targetType, $types, true)) {
                return $p;
            }
        }

        // Tầng 3 (Safe Fallback): Lấy sản phẩm lành tính chung cho mọi loại da
        foreach ($stepCandidates as $p) {
            $types = (array) ($p['skin_types'] ?? []);
            $isGentle = (bool) ($p['is_gentle_fallback'] ?? false);

            if ($isGentle || in_array('all_skin_types', $types, true)) {
                return $p;
            }
        }

        // Chốt chặn cuối cùng: Lấy sản phẩm đầu tiên khả dụng trong danh mục bước đó
        return $stepCandidates[0];
    }

    private function buildProfileCode(string $type, string $concern, bool $isSensitive): string
    {
        if ($isSensitive) {
            return 'sensitive_' . $concern;
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

        // Sinh ID số nguyên xác định từ hash session UUID
        return (int) (abs(crc32($sessionId)) % 900000) + 100000;
    }
}
