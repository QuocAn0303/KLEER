<?php

declare(strict_types=1);

namespace Kleer;

use Kleer\Endpoints\HealthEndpoints;

final class Plugin
{
    public function register(): void
    {
        (new HealthEndpoints())->register();
    }
}
