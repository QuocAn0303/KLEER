<?php

declare(strict_types=1);

namespace Kleer;

use Kleer\Endpoints\HealthEndpoints;
use Kleer\Support\Cors\CorsService;

// Prevent direct file access (Security Case)
defined('ABSPATH') || exit;

/**
 * Main Plugin lifecycle orchestrator (Singleton).
 *
 * Manages plugin lifecycle, activation, deactivation, and service registration.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private ?CorsService $corsService = null;

    /**
     * Get the singleton instance of the plugin.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of the singleton instance.
     *
     * @throws \Exception
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Plugin activation hook callback.
     * Enforces environment compatibility (PHP >= 8.2) and flushes rewrite rules.
     */
    public static function activate(): void
    {
        $minPhpVersion = defined('KLEER_MIN_PHP_VERSION') ? KLEER_MIN_PHP_VERSION : '8.2';

        if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
            if (!function_exists('deactivate_plugins') && defined('ABSPATH')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            if (function_exists('deactivate_plugins') && defined('KLEER_PLUGIN_FILE')) {
                deactivate_plugins(plugin_basename(KLEER_PLUGIN_FILE));
            }

            $errorMessage = sprintf(
                'KLEER Plugin yêu cầu phiên bản PHP tối thiểu là %s. Phiên bản hiện tại của máy chủ là %s. Vui lòng nâng cấp PHP để kích hoạt plugin.',
                $minPhpVersion,
                PHP_VERSION
            );

            if (function_exists('wp_die')) {
                wp_die(
                    esc_html($errorMessage),
                    esc_html__('Lỗi kích hoạt Plugin', 'kleer-plugin'),
                    ['back_link' => true]
                );
            }

            throw new \RuntimeException($errorMessage);
        }

        // Flush rewrite rules on activation (executed only once)
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }

    /**
     * Plugin deactivation hook callback.
     * Flushes rewrite rules and clears temporary transients/caches.
     */
    public static function deactivate(): void
    {
        // Flush rewrite rules upon deactivation
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        // Clean up temporary cache or transient data
        if (function_exists('delete_transient')) {
            delete_transient('kleer_plugin_cache');
        }
    }

    /**
     * Register endpoints, services, and hooks with WordPress.
     */
    public function register(): void
    {
        $this->corsService = new CorsService();
        $this->corsService->register();

        (new HealthEndpoints())->register();
    }

    /**
     * CORS handler dang active, dung chokiem thu va cho tien ich noi bo.
     */
    public function corsService(): ?CorsService
    {
        return $this->corsService;
    }
}
