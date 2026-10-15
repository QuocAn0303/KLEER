<?php

declare(strict_types=1);

namespace KleerTheme;

if (!defined('ABSPATH')) {
    exit;
}

define('KLEER_THEME_VERSION', '1.0.0');

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
        'nonce'    => wp_create_nonce('kleer_filter_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets');