<?php
declare(strict_types=1);
// docker compose exec -T php php tools/verify_subpages.php [--import-if-empty]
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../wp-load.php';
if (!function_exists('wc_get_products')) { fwrite(STDERR, "WooCommerce is not active\n"); exit(1); }
if ((int) (wp_count_posts('product')->publish ?? 0) === 0 && in_array('--import-if-empty', $argv, true)) {
    require __DIR__ . '/import_500_skus.php';
}
delete_option('kleer_subpages_ready_v4');
do_action('init');
flush_rewrite_rules(false);
$failed = 0;
foreach (['shop', 'quiz', 'about', 'contact'] as $slug) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    $ok = $page && $page->post_status === 'publish';
    echo ($ok ? 'PASS ' : 'FAIL ') . $slug . PHP_EOL;
    if (!$ok) $failed++;
}
$count = (int) (wp_count_posts('product')->publish ?? 0);
echo 'Published products: ' . $count . PHP_EOL;
if (!$count) $failed++;
foreach (['pa_skin_type', 'pa_skin_concern', 'pa_routine_step', 'pa_active_ingredient'] as $taxonomy) {
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
    $ok = !is_wp_error($terms) && count($terms) > 0;
    echo ($ok ? 'PASS ' : 'FAIL ') . $taxonomy . PHP_EOL;
    if (!$ok) $failed++;
}
exit($failed ? 1 : 0);
