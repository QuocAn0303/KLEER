<?php

declare(strict_types=1);

namespace Kleer\Controllers;

use Kleer\Endpoints\HealthEndpoints;

/**
 * Request adapter for health check requests.
 *
 * Layer: Controllers
 * Responsibility: Handle incoming requests, format responses, and isolate HTTP boundaries.
 * Must not contain business logic or query the database directly.
 */
final class HealthController
{
    /**
     * Backward-compatible route registration hook.
     * Primary route registration is handled by HealthEndpoints.
     */
    public function register(): void
    {
        (new HealthEndpoints($this))->register();
    }

    /**
     * Return health status payload.
     *
     * @return array{status: string, service: string}
     */
    public function health(): array
    {
        return [
            'status' => 'ok',
            'service' => 'kleer-plugin',
        ];
    }
}
