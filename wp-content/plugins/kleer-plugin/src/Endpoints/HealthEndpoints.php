<?php

declare(strict_types=1);

namespace Kleer\Endpoints;

use Kleer\Controllers\HealthController;

/**
 * Route registration for health check endpoints.
 *
 * Layer: Endpoints
 * Responsibility: Map REST API routes to controller actions, define HTTP methods,
 *                 and set permission callbacks. Must not contain business logic.
 */
final class HealthEndpoints
{
    private HealthController $controller;

    public function __construct(?HealthController $controller = null)
    {
        $this->controller = $controller ?? new HealthController();
    }

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('kleer/v1', '/health', [
                'methods' => 'GET',
                'callback' => [$this->controller, 'health'],
                'permission_callback' => '__return_true',
            ]);
        });
    }
}
