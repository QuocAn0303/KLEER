<?php
/**
 * AJAX Catalog Filter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_kleer_filter_products', 'kleer_filter_products_ajax');
add_action('wp_ajax_nopriv_kleer_filter_products', 'kleer_filter_products_ajax');

function kleer_filter_products_ajax(): void
{
    $nonce = $_POST['nonce'] ?? ($_GET['nonce'] ?? '');
    if (!is_string($nonce) || $nonce === '' || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'kleer_filter_products')) {
        wp_send_json_error(['message' => __('Nonce không hợp lệ.', 'kleer-theme')], 403);
        return;
    }

    $taxonomies = ['skin_type', 'skin_concern', 'routine_step'];

    $taxQuery = ['relation' => 'AND'];
    foreach ($taxonomies as $taxonomy) {
        if (isset($_POST[$taxonomy]) || isset($_GET[$taxonomy])) {
            $raw = $_POST[$taxonomy] ?? $_GET[$taxonomy];
            if (is_string($raw)) {
                $decoded = json_decode(stripslashes($raw), true);
                if (is_array($decoded)) {
                    $raw = $decoded;
                } else {
                    $raw = explode(',', $raw);
                }
            }
            if (!is_array($raw) || empty($raw)) {
                continue;
            }
            $terms = [];
            foreach ($raw as $term) {
                if (!is_string($term)) {
                    continue;
                }
                $termSanitized = sanitize_title($term);
                if ($termSanitized !== '') {
                    $terms[] = $termSanitized;
                }
            }
            if (!empty($terms)) {
                $taxQuery[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN',
                ];
            }
        }
    }

    $metaQuery = ['relation' => 'AND'];
    $minPrice = $_POST['min_price'] ?? $_GET['min_price'] ?? null;
    $maxPrice = $_POST['max_price'] ?? $_GET['max_price'] ?? null;

    if ($minPrice !== null && $minPrice !== '') {
        $minPriceInt = intval($minPrice);
        if ($minPriceInt >= 0) {
            $metaQuery[] = [
                'key' => '_price',
                'value' => $minPriceInt,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
    }

    if ($maxPrice !== null && $maxPrice !== '') {
        $maxPriceInt = intval($maxPrice);
        if ($maxPriceInt >= 0) {
            $metaQuery[] = [
                'key' => '_price',
                'value' => $maxPriceInt,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }
    }

    $paged = isset($_POST['paged']) ? absint($_POST['paged']) : (isset($_GET['paged']) ? absint($_GET['paged']) : 1);
    if ($paged < 1) {
        $paged = 1;
    }

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => $paged,
    ];

    if (count($taxQuery) > 1) {
        $args['tax_query'] = $taxQuery;
    }

    if (count($metaQuery) > 1) {
        $args['meta_query'] = $metaQuery;
    }

    $cacheKey = 'kleer_filter_' . md5(serialize([
        $taxQuery,
        $metaQuery,
        $paged,
    ]));
    $transient = get_transient($cacheKey);
    if (is_string($transient) && $transient !== '') {
        wp_send_json_success(['html' => $transient, 'cached' => true]);
        return;
    }

    $query = new WP_Query($args);

    ob_start();
    if ($query->have_posts()) {
        woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            do_action('woocommerce_shop_loop');
            wc_get_template_part('content', 'product');
        }
        woocommerce_product_loop_end();
    } else {
        echo '<div class="kleer-no-products">' . esc_html__('Không tìm thấy sản phẩm phù hợp với bộ lọc.', 'kleer-theme') . '</div>';
    }
    $html = ob_get_clean();
    wp_reset_postdata();

    set_transient($cacheKey, $html, 60);

    wp_send_json_success(['html' => $html]);
}
?>
