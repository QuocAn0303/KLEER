<?php

declare(strict_types=1);

// CLI only. This verifier reads state and dispatches quiz requests without inserting products.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost:8081';
$_SERVER['REQUEST_METHOD'] = 'GET';
require dirname(__DIR__, 2) . '/wp-load.php';

global $wpdb;
$payload = json_decode(file_get_contents(__DIR__ . '/valid-submission.json'), true, 64, JSON_THROW_ON_ERROR);
$taxonomies = [];
foreach (['pa_skin_type', 'pa_skin_concern', 'pa_routine_step', 'skin_type', 'skin_concern', 'routine_step'] as $slug) {
    $taxonomy = get_taxonomy($slug);
    $taxonomies[$slug] = $taxonomy ? [
        'show_ui' => $taxonomy->show_ui, 'show_in_menu' => $taxonomy->show_in_menu,
        'object_type' => $taxonomy->object_type,
    ] : false;
}
$dispatch = static function (array $body): array {
    $request = new WP_REST_Request('POST', '/kleer/v1/recommend');
    $request->set_header('Content-Type', 'application/json');
    $request->set_body(json_encode($body, JSON_THROW_ON_ERROR));
    $response = rest_do_request($request);
    return ['http_status' => $response->get_status(), 'body' => $response->get_data()];
};
$valid = $dispatch($payload);
$payload['answers'][0]['answer'] = 'invalid';
$invalid = $dispatch($payload);
$indexes = [];
foreach ([$wpdb->posts, $wpdb->postmeta, $wpdb->term_relationships, $wpdb->term_taxonomy, $wpdb->terms] as $table) {
    $indexes[$table] = array_map(static fn(array $i): array => array_intersect_key($i, array_flip(['Key_name','Seq_in_index','Column_name'])), $wpdb->get_results("SHOW INDEX FROM `$table`", ARRAY_A));
}
echo json_encode([
    'checked_at_utc' => gmdate('c'),
    'plugin_active' => in_array('kleer-plugin/kleer-plugin.php', (array) get_option('active_plugins'), true),
    'woocommerce_loaded' => class_exists('WooCommerce'),
    'product_post_type' => post_type_exists('product'),
    'published_products' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"),
    'taxonomy_registration' => $taxonomies,
    'valid_request' => $valid,
    'invalid_request' => $invalid,
    'indexes' => $indexes,
    'note' => 'Taxonomy registration is not proof of WooCommerce admin menu visibility. HTTP 503 is not a successful routine or a performance pass.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
