<?php
require __DIR__ . '/../wp-load.php';
global $wpdb;
echo 'woocommerce=' . (class_exists('WooCommerce') ? 'yes' : 'no') . PHP_EOL;
echo 'wc_version=' . (defined('WC_VERSION') ? WC_VERSION : 'n/a') . PHP_EOL;
echo 'post_types_has_product=' . (post_type_exists('product') ? 'yes' : 'no') . PHP_EOL;
echo 'products=' . wp_count_posts('product')->publish . PHP_EOL;
echo 'wc_tables=' . count(array_filter($wpdb->get_col('SHOW TABLES'), fn($t) => str_contains($t, 'woocommerce') || str_contains($t, 'wc_'))) . PHP_EOL;
echo 'attributes=' . json_encode(array_map(fn($a) => $a->attribute_name, wc_get_attribute_taxonomies())) . PHP_EOL;
