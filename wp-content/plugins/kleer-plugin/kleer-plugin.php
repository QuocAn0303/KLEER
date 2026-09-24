<?php
/**
 * Plugin Name: KLEER Custom Plugin
 * Description: Application services and REST endpoints for KLEER.
 * Version: 0.1.0
 * Requires PHP: 8.2
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Contracts/ProductRepositoryInterface.php';
require_once __DIR__ . '/src/Services/ProductService.php';
require_once __DIR__ . '/src/Controllers/HealthController.php';
require_once __DIR__ . '/src/Plugin.php';

add_action('plugins_loaded', static function (): void {
    (new Kleer\Plugin())->register();
});
