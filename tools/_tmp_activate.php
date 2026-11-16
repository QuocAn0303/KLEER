<?php
require __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugins = [
    'woocommerce/woocommerce.php',
    'kleer-plugin/kleer-plugin.php',
    'kleer-payments/kleer-payments.php',
    'kleer-mailer/kleer-mailer.php',
];
foreach ($plugins as $plugin) {
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
        echo "MISSING $plugin\n";
        continue;
    }
    if (is_plugin_active($plugin)) {
        echo "ACTIVE  $plugin\n";
        continue;
    }
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) {
        echo "FAIL    $plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "ENABLED $plugin\n";
    }
}
echo 'woocommerce=' . (class_exists('WooCommerce') ? 'yes' : 'no') . PHP_EOL;
echo 'woocommerce_version=' . (defined('WC_VERSION') ? WC_VERSION : 'n/a') . PHP_EOL;
