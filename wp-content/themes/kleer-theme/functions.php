<?php

declare(strict_types=1);

namespace KleerTheme;

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('KLEER_THEME_VERSION')) {
    define('KLEER_THEME_VERSION', '1.0.0');
}

/**
 * Thiết lập các tính năng nền tảng cho theme và WooCommerce
 */
function theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    // Kế thừa đầy đủ tính năng WooCommerce
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary_menu' => __('Primary Menu', 'kleer-theme'),
    ]);
}
add_action('after_setup_theme', __NAMESPACE__ . '\\theme_setup');

/**
 * Enqueue scripts và styles nền tảng
 */
function enqueue_assets(): void
{
    wp_enqueue_style(
        'kleer-main-style',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        KLEER_THEME_VERSION
    );

    wp_enqueue_script(
        'kleer-main-script',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        KLEER_THEME_VERSION,
        true
    );

    wp_localize_script('kleer-main-script', 'kleerAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kleer_add_routine_to_cart'),
    ]);

    wp_enqueue_script(
        'kleer-routine-cart',
        get_template_directory_uri() . '/assets/js/routine-cart.js',
        ['jquery'],
        KLEER_THEME_VERSION,
        true
    );

    wp_localize_script('kleer-routine-cart', 'kleerAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kleer_add_routine_to_cart'),
    ]);

    if (function_exists('is_shop') && is_shop()) {
        wp_enqueue_script(
            'kleer-catalog-filter',
            get_template_directory_uri() . '/assets/js/catalog-filter.js',
            [],
            KLEER_THEME_VERSION,
            true
        );
        wp_localize_script('kleer-catalog-filter', 'kleerAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('kleer_filter_products'),
        ]);
    }
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets');

// Nạp file đăng ký script & style cho Quiz SPA
require_once get_template_directory() . '/inc/quiz-assets.php';

require_once get_template_directory() . '/inc/ajax-catalog-filter.php';
