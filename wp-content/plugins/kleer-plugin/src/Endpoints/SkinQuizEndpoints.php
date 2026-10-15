<?php

declare(strict_types=1);

namespace Kleer\Endpoints;

use Kleer\Controllers\SkinQuizController;

// Prevent direct file access
defined('ABSPATH') || exit;

use WP_REST_Request;

/**
 * Route registration for Skin Quiz and Routine Recommendation endpoints.
 *
 * Layer: Endpoints
 * Responsibility: Khai báo route REST API, định nghĩa HTTP methods, gán permission callbacks
 *                 và liên kết tới Controller. Tuyệt đối không chứa business logic.
 */
final class SkinQuizEndpoints
{
    private SkinQuizController $controller;

    public function __construct(?SkinQuizController $controller = null)
    {
        $this->controller = $controller ?? new SkinQuizController();
    }

    /**
     * Đăng ký các routes REST API với WordPress hook rest_api_init.
     */
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            // 1. Endpoint chính thức cho SPA Skin Quiz
            register_rest_route('kleer/v1', '/skin-quiz', [
                'methods' => 'POST',
                'callback' => [$this->controller, 'handleSubmission'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);

            // 2. Route alias phục vụ tương thích ngược với mô tả task /recommend
            register_rest_route('kleer/v1', '/recommend', [
                'methods' => 'POST',
                'callback' => [$this->controller, 'handleSubmission'],
                'permission_callback' => [$this, 'checkPermission'],
            ]);
        });
    }

    /**
     * Quyền truy cập: Cho phép khách vãng lai (Public) thực hiện Quiz mà không cần đăng nhập.
     * Nếu client gửi cookie xác thực thì tự động kiểm tra CSRF Nonce hợp lệ.
     */
    public function checkPermission(WP_REST_Request $request): bool
    {
        // Cho phép mọi người dùng ẩn danh hoặc đã đăng nhập chẩn đoán da
        return true;
    }
}
