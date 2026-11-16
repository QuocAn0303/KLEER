<?php

declare(strict_types=1);

namespace KleerTheme;

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('KLEER_THEME_VERSION')) {
    define('KLEER_THEME_VERSION', '2.1.0');
}

/**
 * Thiết lập các tính năng nền tảng cho theme và WooCommerce
 */
function theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
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
 * Chuẩn bị danh sách thẻ sản phẩm khởi tạo từ WooCommerce.
 *
 * @return list<array<string, mixed>>
 */
function initial_product_cards(): array
{
    if (!function_exists('wc_get_products') || !class_exists('\\Kleer\\Support\\ProductCardPresenter')) {
        return [];
    }

    $total = function_exists('wp_count_posts') ? (int) (wp_count_posts('product')->publish ?? 0) : 0;
    $limit = ($total > 0 && $total <= 50) ? $total : 12;

    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => $limit,
        'orderby' => 'meta_value_num',
        'meta_key' => 'total_sales',
        'visibility' => 'visible',
        'order'   => 'DESC',
    ]);

    if (!is_array($products)) {
        return [];
    }

    $cards = [];
    foreach ($products as $product) {
        if ($product instanceof \WC_Product) {
            $cards[] = \Kleer\Support\ProductCardPresenter::present($product);
        }
    }

    return $cards;
}

/**
 * Enqueue scripts và styles nền tảng
 */
function enqueue_assets(): void
{
    // 1. Một họ font Tiếng Việt duy nhất: Be Vietnam Pro
    wp_enqueue_style(
        'kleer-google-fonts',
        'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap',
        [],
        null
    );

    // 2. CSS toàn cục
    wp_enqueue_style(
        'kleer-global',
        get_template_directory_uri() . '/assets/css/global.css',
        ['kleer-google-fonts'],
        KLEER_THEME_VERSION
    );

    // 3. CSS trang chủ (và trang Quiz dùng chung khung Chẩn đoán da)
    if (is_front_page() || is_home() || is_page_template('page-templates/template-quiz.php')) {
        wp_enqueue_style(
            'kleer-homepage-style',
            get_template_directory_uri() . '/assets/css/home.css',
            ['kleer-global'],
            KLEER_THEME_VERSION
        );
    }

    // 4. CSS Trang Shop Catalog
    if (
        (function_exists('is_shop') && is_shop()) ||
        (function_exists('is_product_category') && is_product_category()) ||
        (function_exists('is_product_taxonomy') && is_product_taxonomy()) ||
        is_post_type_archive('product') ||
        is_page('shop')
    ) {
        wp_enqueue_style(
            'kleer-catalog',
            get_template_directory_uri() . '/assets/css/catalog.css',
            ['kleer-global'],
            KLEER_THEME_VERSION
        );
    }

    // 5. CSS Trang Chi tiết sản phẩm
    if ((function_exists('is_product') && is_product()) || is_singular('product')) {
        wp_enqueue_style(
            'kleer-product-detail',
            get_template_directory_uri() . '/assets/css/product_detail.css',
            ['kleer-global'],
            KLEER_THEME_VERSION
        );
    }

    // 6. Vanilla JS ES6
    wp_enqueue_script(
        'kleer-routine-cart',
        get_template_directory_uri() . '/assets/js/routine-cart.js',
        [],
        KLEER_THEME_VERSION,
        true
    );

    wp_enqueue_script(
        'kleer-quiz',
        get_template_directory_uri() . '/assets/js/quiz.js',
        ['kleer-routine-cart'],
        KLEER_THEME_VERSION,
        true
    );

    wp_enqueue_script(
        'kleer-catalog-filter',
        get_template_directory_uri() . '/assets/js/catalog-filter.js',
        ['kleer-routine-cart'],
        KLEER_THEME_VERSION,
        true
    );

    wp_localize_script('kleer-routine-cart', 'kleerAjax', [
        'ajax_url'         => admin_url('admin-ajax.php'),
        'rest_url'         => esc_url_raw(rest_url('kleer/v1/')),
        'nonce'            => wp_create_nonce('kleer_ajax_nonce'),
        'rest_nonce'       => wp_create_nonce('wp_rest'),
        'cart_url'         => function_exists('wc_get_cart_url') ? esc_url(wc_get_cart_url()) : home_url('/cart/'),
        'checkout_url'     => function_exists('wc_get_checkout_url') ? esc_url(wc_get_checkout_url()) : home_url('/checkout/'),
        'shop_url'         => function_exists('wc_get_page_permalink') ? esc_url(wc_get_page_permalink('shop')) : home_url('/shop/'),
        'quiz_url'         => esc_url(home_url('/quiz/')),
        'theme_assets_url' => esc_url(get_template_directory_uri() . '/assets/images/'),
        'initial_products' => initial_product_cards(),
        'product_count'    => function_exists('wp_count_posts') ? (int) (wp_count_posts('product')->publish ?? 0) : 0,
    ]);

    if (
        (function_exists('is_shop') && is_shop()) ||
        (function_exists('is_product_category') && is_product_category()) ||
        (function_exists('is_product_taxonomy') && is_product_taxonomy()) ||
        is_post_type_archive('product') ||
        is_page('shop')
    ) {
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

    // 7. CSS & JS Tối ưu trải nghiệm Giỏ hàng (Cart) và Thanh toán (Checkout) — Task L03-G5-02
    if (
        (function_exists('is_cart') && is_cart()) ||
        (function_exists('is_checkout') && is_checkout()) ||
        is_page('cart') ||
        is_page('checkout')
    ) {
        wp_enqueue_style(
            'kleer-cart-checkout',
            get_template_directory_uri() . '/assets/css/cart-checkout.css',
            ['kleer-global'],
            KLEER_THEME_VERSION
        );

        wp_enqueue_script(
            'kleer-checkout-ux',
            get_template_directory_uri() . '/assets/js/checkout-ux.js',
            ['jquery'],
            KLEER_THEME_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets');

// Nạp file đăng ký script & style cho Quiz SPA, Catalog Filter và Cart/Checkout UX
require_once get_template_directory() . '/inc/quiz-assets.php';
require_once get_template_directory() . '/inc/ajax-catalog-filter.php';
require_once get_template_directory() . '/inc/cart-checkout-ux.php';

// Ép sử dụng giao diện KLEER cho trang danh mục sản phẩm và chi tiết sản phẩm
add_filter('template_include', function ($template) {
    if (
        (function_exists('is_shop') && is_shop()) ||
        (function_exists('is_product_taxonomy') && is_product_taxonomy()) ||
        (function_exists('is_product_category') && is_product_category()) ||
        (function_exists('is_product_tag') && is_product_tag()) ||
        is_post_type_archive('product') ||
        is_page('shop')
    ) {
        $custom_archive = get_template_directory() . '/archive-product.php';
        if (file_exists($custom_archive)) {
            return $custom_archive;
        }
    }

    if ((function_exists('is_product') && is_product()) || is_singular('product')) {
        $custom_single = get_template_directory() . '/single-product.php';
        if (file_exists($custom_single)) {
            return $custom_single;
        }
    }

    return $template;
}, 999);

require_once __DIR__ . '/inc/subpages.php';
