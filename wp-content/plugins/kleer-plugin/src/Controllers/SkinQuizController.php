<?php

declare(strict_types=1);

namespace Kleer\Controllers;

use Kleer\Controllers\Concerns\ValidatesJsonPayload;
use Kleer\Services\QuizEngineService;

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
        $this->quizEngineService = $quizEngineService ?? new QuizEngineService();
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

        // 2. Chuyển tiếp sang tầng Service xử lý thuật toán phân loại và gợi ý sản phẩm
        try {
            $result = $this->quizEngineService->processSubmission($validatedPayload);
            return new WP_REST_Response($result, 200);
        } catch (\Throwable $e) {
            return new WP_Error(
                'kleer_quiz_engine_error',
                __('Đã xảy ra lỗi nội bộ trong quá trình phân tích phác đồ da.', 'kleer-plugin'),
                [
                    'status' => 500,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
