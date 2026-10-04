<?php
/**
 * Plugin Name:       KLEER Custom Plugin
 * Plugin URI:        https://github.com/QuocAn0303/KLEER
 * Description:       Application services, REST endpoints, and domain logic for KLEER Unisex Skincare E-commerce store.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Bùi Hiếu Nhân (KLEER Team)
 * Author URI:        https://github.com/QuocAn0303/KLEER
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kleer-plugin
 * Domain Path:       /languages
 */

declare(strict_types=1);

// Prevent direct script access (Security Case)
defined('ABSPATH') || exit;

// Plugin Constants
if (!defined('KLEER_PLUGIN_VERSION')) {
    define('KLEER_PLUGIN_VERSION', '0.1.0');
}
if (!defined('KLEER_MIN_PHP_VERSION')) {
    define('KLEER_MIN_PHP_VERSION', '8.2');
}
if (!defined('KLEER_PLUGIN_FILE')) {
    define('KLEER_PLUGIN_FILE', __FILE__);
}
if (!defined('KLEER_PLUGIN_DIR')) {
    define('KLEER_PLUGIN_DIR', function_exists('plugin_dir_path') ? plugin_dir_path(__FILE__) : __DIR__ . '/');
}
if (!defined('KLEER_PLUGIN_URL')) {
    define('KLEER_PLUGIN_URL', function_exists('plugin_dir_url') ? plugin_dir_url(__FILE__) : '');
}

/**
 * PSR-4 Autoloader for the Kleer\ namespace.
 * Automatically loads classes from the src/ directory without manual require_once.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Kleer\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Register Plugin Activation and Deactivation Hooks
register_activation_hook(__FILE__, ['Kleer\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Kleer\\Plugin', 'deactivate']);

// Bootstrap the plugin once all active plugins are loaded
add_action('plugins_loaded', static function (): void {
    Kleer\Plugin::getInstance()->register();
});
