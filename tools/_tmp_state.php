<?php
require __DIR__ . '/../wp-load.php';

echo 'show_on_front=' . get_option('show_on_front') . PHP_EOL;
echo 'page_on_front=' . get_option('page_on_front') . PHP_EOL;
echo 'page_for_posts=' . get_option('page_for_posts') . PHP_EOL;
echo 'shop_page_id=' . get_option('woocommerce_shop_page_id') . PHP_EOL;
echo 'perma=' . get_option('permalink_structure') . PHP_EOL;
echo 'home=' . home_url('/') . ' siteurl=' . site_url('/') . PHP_EOL;
echo 'theme=' . wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version') . PHP_EOL;

$counts = wp_count_posts('product');
echo 'products_publish=' . (int) $counts->publish . ' draft=' . (int) $counts->draft . PHP_EOL;

foreach (['shop', 'quiz', 'about', 'contact'] as $slug) {
    $p = get_page_by_path($slug);
    echo $slug . '=' . ($p ? $p->ID . '|' . $p->post_status . '|' . $p->post_type : 'MISSING') . PHP_EOL;
}

$front = (int) get_option('page_on_front');
if ($front) {
    echo 'front_template=' . get_post_meta($front, '_wp_page_template', true) . PHP_EOL;
}

echo 'kleer_subpages_ready_v4=' . get_option('kleer_subpages_ready_v4') . PHP_EOL;

foreach (['pa_skin_type', 'pa_skin_concern', 'pa_routine_step', 'pa_active_ingredient'] as $tax) {
    echo $tax . '=' . (taxonomy_exists($tax) ? wp_count_terms(['taxonomy' => $tax, 'hide_empty' => false]) : 'MISSING') . PHP_EOL;
}

$products = wc_get_products(['status' => 'publish', 'limit' => 3, 'orderby' => 'date', 'order' => 'DESC']);
foreach ($products as $product) {
    $pid = $product->get_id();
    echo 'product: ' . $pid . ' | ' . $product->get_name() . ' | price=' . $product->get_price() . ' | img=' . $product->get_image_id() . ' | steps=' . implode(',', wc_get_product_terms($pid, 'pa_routine_step', ['fields' => 'names'])) . ' | skins=' . implode(',', wc_get_product_terms($pid, 'pa_skin_type', ['fields' => 'names'])) . PHP_EOL;
}

global $wpdb;
echo 'hpos_orders_table=' . $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders'") . PHP_EOL;
echo 'active_plugins=' . implode(',', (array) get_option('active_plugins')) . PHP_EOL;
