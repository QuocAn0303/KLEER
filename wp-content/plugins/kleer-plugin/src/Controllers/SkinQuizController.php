<?php

declare(strict_types=1);

namespace Kleer\Controllers;

use Kleer\Controllers\Concerns\ValidatesJsonPayload;
use Kleer\Repositories\WooCommerceProductRepository;
use Kleer\Services\QuizEngineService;
use Kleer\Exceptions\RoutineUnavailableException;
use Kleer\Support\Validation\JsonSchema;

// Prevent direct file access
defined('ABSPATH') || exit;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Controller for the Skin Quiz submission endpoint.
 *
 * Layer: Controllers
 * Responsibility: Tiếp nhận HTTP request, xác thực schema JSON payload,
 *                 ủy quyền xử lý cho QuizEngineService và đóng gói response HTTP chuẩn.
 * Boundary: Không trực tiếp truy vấn cơ sở dữ liệu, không tính toán điểm nghiệp vụ.
 */
class SkinQuizController
{
    use ValidatesJsonPayload;

    private QuizEngineService $quizEngineService;

    public function __construct(?QuizEngineService $quizEngineService = null)
    {
        $this->quizEngineService = $quizEngineService
            ?? new QuizEngineService(new WooCommerceProductRepository());
    }

    /**
     * Xử lý gửi câu trả lời Quiz từ Frontend.
     *
     * Route: POST /wp-json/kleer/v1/skin-quiz (hoặc alias /wp-json/kleer/v1/recommend)
     */
    public function handleSubmission(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        // 1. Kiểm tra payload theo hợp đồng JSON Schema dùng chung
        $validatedPayload = $this->validateJsonPayload($request, 'quiz-submission.schema.json');

        if (is_wp_error($validatedPayload)) {
            return $validatedPayload; // Tự động trả HTTP 400 kèm danh sách lỗi có JSON Pointer
        }

        // 2. Consent phải được xác nhận ở backend, không chỉ ở JavaScript.
        $consent = is_array($validatedPayload['consent'] ?? null)
            ? $validatedPayload['consent']
            : [];

        if (($consent['policy_accepted'] ?? null) !== true) {
            return new WP_Error(
                'kleer_consent_required',
                __('Bạn cần đồng ý với Chính sách bảo mật của KLEER trước khi gửi bài Quiz.', 'kleer-plugin'),
                [
                    'status' => 400,
                ]
            );
        }

        // 3. Chuyển tiếp sang tầng Service xử lý thuật toán phân loại và gợi ý sản phẩm
        try {
            $version = 'v1';

            if (method_exists($request, 'get_param')) {
                $paramVersion = $request->get_param('version');

                if (is_string($paramVersion) && $paramVersion !== '') {
                    if (!in_array($paramVersion, ['v1', 'v4'], true)) {
                        return new WP_Error(
                            'kleer_invalid_version',
                            __('Phiên bản Quiz không hợp lệ. Chỉ chấp nhận "v1" hoặc "v4".', 'kleer-plugin'),
                            [
                                'status' => 400,
                            ]
                        );
                    }

                    $version = $paramVersion;
                }
            }

            $result = $this->quizEngineService->processSubmission($validatedPayload, $version);
            $result = $this->presentRecommendedProducts($result);
            if (JsonSchema::validate($result, JsonSchema::fromFile('quiz-submission-response.schema.json'))->isInvalid()) {
                throw new \RuntimeException('Invalid routine response contract.');
            }
            return new WP_REST_Response($result, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->answerError('/answers', $e->getMessage());
        } catch (RoutineUnavailableException $e) {
            return new WP_Error('kleer_' . $e->reason, $e->getMessage(), [
                'status' => 503,
                'missing_steps' => $e->missingSteps,
            ]);
        } catch (\Throwable $e) {
            // Không lộ exception/SQL/credentials ra response public
            return new WP_Error(
                'kleer_quiz_engine_error',
                __('Đã xảy ra lỗi nội bộ trong quá trình phân tích phác đồ da.', 'kleer-plugin'),
                [
                    'status' => 500,
                ]
            );
        }
    }

    /**
     * Bổ sung dữ liệu thẻ sản phẩm Hai Tông (màu nền, ảnh, nhãn INCI) cho từng sản phẩm gợi ý.
     * Chỉ thêm các khóa đã khai báo optional trong quiz-submission-response.schema.json.
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function presentRecommendedProducts(array $result): array
    {
        if (!function_exists('wc_get_product') || !class_exists('\WC_Product')) {
            return $result;
        }

        $products = $result['data']['recommended_products'] ?? null;
        if (!is_array($products)) {
            return $result;
        }

        foreach ($products as $index => $product) {
            $product_id = (int) ($product['id'] ?? 0);
            if ($product_id <= 0) {
                continue;
            }

            $wc_product = wc_get_product($product_id);
            if (!$wc_product instanceof \WC_Product) {
                continue;
            }

            $card = \Kleer\Support\ProductCardPresenter::present($wc_product);

            $products[$index]['cardTopBg'] = $card['cardTopBg'];
            $products[$index]['cardBottomBg'] = $card['cardBottomBg'];
            $products[$index]['cardAccent'] = $card['cardAccent'];
            $products[$index]['image'] = $card['image'];
            $products[$index]['skinLabel'] = $card['skinLabel'];
            $products[$index]['inciBadge'] = $card['inciBadge'];
            $products[$index]['inciGroup'] = $card['inciGroup'];
            $products[$index]['priceFormatted'] = $card['priceFormatted'];
        }

        $result['data']['recommended_products'] = $products;

        return $result;
    }

    private function answerError(string $pointer, string $message): WP_Error
    {
        return new WP_Error('kleer_invalid_payload', $message, [
            'status' => 400,
            'errors' => [['pointer' => $pointer, 'keyword' => 'quiz_answers', 'message' => $message]],
        ]);
    }
}
