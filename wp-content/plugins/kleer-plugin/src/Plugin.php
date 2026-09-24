<?php

declare(strict_types=1);

namespace Kleer;

use Kleer\Controllers\HealthController;

final class Plugin
{
    public function register(): void
    {
        (new HealthController())->register();
    }
}
