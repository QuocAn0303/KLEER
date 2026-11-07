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
        'footer_menu'  => __('Footer Menu', 'kleer-theme'),
    ]);
}
add_action('after_setup_theme', __NAMESPACE__ . '\\theme_setup');

/**
 * Enqueue scripts và styles nền tảng
 */
function enqueue_assets(): void
{
    // 1. Google Fonts (Fraunces & Outfit)
    wp_enqueue_style(
        'kleer-google-fonts',
        'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Outfit:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // 2. CSS Global (Dùng chung cho toàn website: Navbar, Footer, Ombre, Reset)
    wp_enqueue_style(
        'kleer-global',
        get_template_directory_uri() . '/assets/css/global.css',
        ['kleer-google-fonts'],
        time()
    );

    // 3. CSS Trang chủ (Chỉ nạp khi ở Front Page hoặc Home)
    if (is_front_page() || is_home()) {
        wp_enqueue_style(
            'kleer-homepage-style',
            get_template_directory_uri() . '/assets/css/home.css',
            ['kleer-global'],
            time()
        );
    }

    // 4. CSS Trang Shop Catalog
    if (function_exists('is_shop') && (is_shop() || is_product_category() || is_product_taxonomy() || is_post_type_archive('product'))) {
        wp_enqueue_style(
            'kleer-catalog',
            get_template_directory_uri() . '/assets/css/catalog.css',
            ['kleer-global'],
            time()
        );
    }

    // 5. CSS Trang Chi tiết sản phẩm
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style(
            'kleer-product-detail',
            get_template_directory_uri() . '/assets/css/product_detail.css',
            ['kleer-global'],
            time()
        );
    }

    // 6. File CSS/JS Main của dự án
    wp_enqueue_style(
        'kleer-main-style',
        get_template_directory_uri() . '/assets/css/main.css',
        ['kleer-global'],
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

// Ép sử dụng giao diện KLEER cho trang danh mục sản phẩm
add_filter('template_include', function ($template) {
    if (function_exists('is_shop') && (is_shop() || is_product_category() || is_product_taxonomy())) {
        $custom_archive = get_template_directory() . '/archive-product.php';
        if (file_exists($custom_archive)) {
            return $custom_archive;
        }
    }
    return $template;
}, 999);