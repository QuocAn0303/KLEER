<?php

declare(strict_types=1);

// Read-only business-data inspection. Does not activate plugins or create/update products.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost:8081';
$_SERVER['REQUEST_METHOD'] = 'GET';
require dirname(__DIR__, 2) . '/wp-load.php';

use Kleer\Config\QuizQuestions;
use Kleer\Repositories\WooCommerceProductRepository;
use Kleer\Services\QuizEngineService;
use Kleer\Support\Validation\JsonSchema;

global $wpdb;
$report = ['checked_at_utc' => gmdate('c'), 'home' => home_url(), 'php_version' => PHP_VERSION,
    'wordpress_version' => get_bloginfo('version'), 'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : null,
    'published_products' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"),
    'taxonomy_registration' => []];
foreach (['pa_skin_type', 'pa_skin_concern', 'pa_routine_step'] as $slug) {
    $report['taxonomy_registration'][$slug] = taxonomy_exists($slug);
}
try {
    $before = $wpdb->num_queries;
    $start = microtime(true);
    $catalog = (new WooCommerceProductRepository())->getQuizCatalog(['version' => 'v4']);
    $report['catalog_read'] = ['products' => count($catalog), 'queries' => $wpdb->num_queries - $before,
        'ms' => round((microtime(true) - $start) * 1000, 2), 'note' => 'Catalog adapter only; not complete HTTP latency.'];
    $report['catalog_steps'] = array_count_values(array_column($catalog, 'routine_step'));
    $report['strong_active_products'] = count(array_filter($catalog, static fn(array $p): bool => $p['has_strong_actives']));
    $report['catalog_max_id'] = $catalog === [] ? null : max(array_column($catalog, 'id'));
    $combinations = [[]];
    foreach (QuizQuestions::getV4Questions() as $id => $question) {
        $next = [];
        foreach ($combinations as $answers) {
            foreach (array_keys($question['options']) as $option) {
                $next[] = [...$answers, ['question_id' => $id, 'answer' => $option]];
            }
        }
        $combinations = $next;
    }
    $engine = new QuizEngineService($catalog);
    $byId = array_column($catalog, null, 'id');
    $schema = JsonSchema::fromFile('quiz-submission-response.schema.json');
    $failures = $missing = $belowTwo = 0;
    foreach ($combinations as $answers) {
        $payload = ['session_id' => '33333333-4444-4555-8666-777777777777',
            'consent' => ['policy_accepted' => true, 'marketing_opt_in' => false], 'answers' => $answers];
        $response = $engine->processSubmission($payload, 'v4');
        $data = $response['data'];
        $ids = array_column($data['recommended_products'], 'id');
        $ok = JsonSchema::validate($response, $schema)->isValid() && count($ids) === count(array_unique($ids));
        foreach ($data['recommended_products'] as $product) {
            $source = $byId[$product['id']] ?? null;
            $ok = $ok && $source !== null && $source['price'] === $product['price']
                && (!$data['skin_profile']['is_sensitive'] || !$source['has_strong_actives']);
        }
        $failures += (int) !$ok;
        $missing += (int) ($data['missing_steps'] !== []);
        $belowTwo += (int) (count($ids) < 2);
    }
    $report['real_catalog_sweep'] = ['combinations' => count($combinations), 'failures' => $failures,
        'routines_with_missing_steps' => $missing, 'routines_below_two_products' => $belowTwo,
        'note' => 'In-process logic over the live WooCommerce catalog; not 7500 HTTP requests or UAT.'];
} catch (\Throwable $e) {
    $report['availability_error'] = $e instanceof \Kleer\Exceptions\RoutineUnavailableException ? $e->reason : get_class($e);
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
exit(isset($report['availability_error']) || ($report['real_catalog_sweep']['failures'] ?? 1) > 0 ? 1 : 0);
