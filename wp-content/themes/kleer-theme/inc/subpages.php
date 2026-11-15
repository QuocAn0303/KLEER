<?php
declare(strict_types=1);
namespace KleerTheme;
defined('ABSPATH') || exit;

// Run once after theme setup; never flush rewrites on every request.
add_action('init', static function (): void {
    if (get_option('kleer_subpages_ready_v4') === 'yes') {
        return;
    }
    foreach (['shop' => 'Sản phẩm', 'quiz' => 'Chẩn đoán da sinh học', 'about' => 'Về chúng tôi', 'contact' => 'Liên hệ & Đổi trả 7 ngày'] as $slug => $title) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        $id = $page ? $page->ID : wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish', 'post_type' => 'page'], true);
        if (is_wp_error($id) || !$id) {
            return; // Retry rather than marking an incomplete setup as ready.
        }
        if ($page && $page->post_status !== 'publish') {
            if (is_wp_error(wp_update_post(['ID' => $id, 'post_status' => 'publish'], true))) {
                return;
            }
        }
        if ($slug === 'shop' && !get_option('woocommerce_shop_page_id')) {
            update_option('woocommerce_shop_page_id', (int) $id);
        }
        if ((int) get_option('page_on_front') === (int) $id) {
            update_option('page_on_front', 0);
            update_option('show_on_front', 'posts');
        }
    }
    if (!get_option('permalink_structure')) {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
    }
    add_rewrite_rule('^product/?$', 'index.php?post_type=product', 'top');
    flush_rewrite_rules(false);
    update_option('kleer_subpages_ready_v4', 'yes');
}, 30);

add_action('init', static function (): void {
    add_rewrite_rule('^product/?$', 'index.php?post_type=product', 'top');
}, 10);

add_filter('template_include', static function (string $template): string {
    foreach (['quiz', 'about', 'contact'] as $slug) {
        if (is_page($slug)) {
            return get_template_directory() . '/page-' . $slug . '.php';
        }
    }
    return $template;
}, 1000);

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('kleer-subpages', get_template_directory_uri() . '/assets/css/subpages.css', ['kleer-global'], (string) filemtime(__DIR__ . '/../assets/css/subpages.css'));
    if (is_page('quiz')) {
        wp_enqueue_style('kleer-homepage-style', get_template_directory_uri() . '/assets/css/home.css', ['kleer-global'], KLEER_THEME_VERSION);
    }
    wp_enqueue_script('kleer-subpages', get_template_directory_uri() . '/assets/js/subpages.js', [], (string) filemtime(__DIR__ . '/../assets/js/subpages.js'), true);
}, 30);

function product_card(\WC_Product $product): void
{
    if (!class_exists('\\Kleer\\Support\\ProductCardPresenter')) {
        return;
    }
    $item = \Kleer\Support\ProductCardPresenter::present($product);
    get_template_part('template-parts/apple-product-card', null, ['item' => $item, 'product' => $product]);
}
