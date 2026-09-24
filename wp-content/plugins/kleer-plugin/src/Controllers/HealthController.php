<?php

declare(strict_types=1);

namespace Kleer\Controllers;

final class HealthController
{
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('kleer/v1', '/health', [
                'methods' => 'GET',
                'callback' => [$this, 'health'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /** @return array{status: string, service: string} */
    public function health(): array
    {
        return ['status' => 'ok', 'service' => 'kleer-plugin'];
    }
}
