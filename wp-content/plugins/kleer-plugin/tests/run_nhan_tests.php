<?php

declare(strict_types=1);

// Reuse existing isolated WP stubs and run the original regression suite first.
require __DIR__ . '/run_tests.php';

use Kleer\Config\QuizQuestions;
use Kleer\Controllers\SkinQuizController;
use Kleer\Endpoints\SkinQuizEndpoints;
use Kleer\Exceptions\RoutineUnavailableException;
use Kleer\Repositories\RoutineProductRepository;
use Kleer\Services\QuizEngineService;
use Kleer\Support\Validation\JsonSchema;

echo "\n16. Nhan: independent matrix, exhaustive combinations and failure cases\n";
foreach ([['v1', $oilyPayload], ['v4', $v4Payload]] as [$version, $payload]) {
    try {
        (new QuizEngineService())->processSubmission($payload, $version);
        assert_true(false, "$version without WooCommerce must not silently use fixtures");
    } catch (RoutineUnavailableException $e) {
        assert_true($e->reason === 'woocommerce_unavailable', "$version production mode rejects missing WooCommerce instead of using fixture IDs");
    }
}
try {
    (new \Kleer\Repositories\WooCommerceProductRepository())->getQuizCatalog();
    assert_true(false, 'Default repository must not manufacture a catalog');
} catch (RoutineUnavailableException $e) {
    assert_true($e->reason === 'woocommerce_unavailable', 'Default repository requires live WooCommerce or an explicitly injected test catalog');
}
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$matrix = [
    'q1_skin_feeling' => [
        'tight_dry' => ['skin_type' => ['dry' => 3]],
        'greasy_all' => ['skin_type' => ['oily' => 3]],
        'oily_tzone' => ['skin_type' => ['combination' => 3]],
        'comfortable' => ['skin_type' => ['normal' => 3]],
    ],
    'q2_pore_sebum' => [
        'large_pores' => ['skin_type' => ['oily' => 2], 'concerns' => ['acne' => 1]],
        'normal_pores' => ['skin_type' => ['normal' => 2, 'dry' => 1]],
        'tzone_only' => ['skin_type' => ['combination' => 2]],
        'flaky_rough' => ['skin_type' => ['dry' => 3]],
    ],
    'q3_sensitivity' => [
        'very_often' => ['sensitivity' => 4],
        'sometimes' => ['sensitivity' => 2],
        'rarely_never' => ['sensitivity' => 0],
    ],
    'q4_main_concern' => [
        'acne_blemish' => ['concerns' => ['acne' => 4]],
        'dark_spots' => ['concerns' => ['dark_spots' => 4]],
        'dehydration' => ['concerns' => ['hydration' => 4]],
        'anti_aging' => ['concerns' => ['aging' => 4]],
    ],
    'q5_routine_goal' => [
        'minimal' => ['routine_steps' => 3], 'intensive' => ['routine_steps' => 4],
    ],
];
$questions = QuizQuestions::getQuestions();
$combinations = [[]];
foreach ($matrix as $id => $options) {
    $next = [];
    foreach ($options as $option => $expectedWeights) {
        assert_true($questions[$id]['options'][$option]['weights'] === $expectedWeights, "Reference matrix $id/$option");
        foreach ($combinations as $answers) {
            $next[] = [...$answers, ['question_id' => $id, 'answer' => $option]];
        }
    }
    $combinations = $next;
}
$genericCatalog = [];
foreach (['cleanser', 'treatment', 'moisturizer', 'sunscreen'] as $i => $step) {
    $genericCatalog[] = ['id' => 900 + $i, 'name' => "Test gentle $step", 'price' => 100000,
        'routine_step' => $step, 'skin_types' => ['all_skin_types'], 'concerns' => ['soothing'], 'is_gentle_fallback' => true];
}
$fixtureEngine = new QuizEngineService(QuizQuestions::getDefaultCatalog());
$fallbackEngine = new QuizEngineService($genericCatalog);
$responseSchema = JsonSchema::fromFile('quiz-submission-response.schema.json');
$mismatches = 0;
foreach ($combinations as $answers) {
    $payload = $oilyPayload;
    $payload['answers'] = $answers;
    $scores = ['oily' => 0, 'dry' => 0, 'combination' => 0, 'normal' => 0];
    $concerns = ['acne' => 0, 'dark_spots' => 0, 'hydration' => 0, 'aging' => 0];
    $sensitivity = 0;
    foreach ($answers as $a) {
        $w = $matrix[$a['question_id']][$a['answer']];
        foreach ($w['skin_type'] ?? [] as $key => $points) { $scores[$key] += $points; }
        foreach ($w['concerns'] ?? [] as $key => $points) { $concerns[$key] += $points; }
        $sensitivity += $w['sensitivity'] ?? 0;
    }
    arsort($scores);
    arsort($concerns);
    $expectedCode = array_key_first($scores) . '_' . ($sensitivity >= 3 ? 'sensitive' : array_key_first($concerns));
    $expectedCount = $answers[4]['answer'] === 'minimal' ? 3 : 4;
    foreach ([$fixtureEngine, $fallbackEngine] as $engine) {
        $result = $engine->calculate_routine($payload);
        $mismatches += (int) ($result['data']['skin_profile']['code'] !== $expectedCode);
        $mismatches += (int) (count($result['data']['recommended_products']) !== $expectedCount);
        $mismatches += (int) !JsonSchema::validate($result, $responseSchema)->isValid();
    }
}
assert_true(count($combinations) === 384 && $mismatches === 0, 'All 384 combinations match independent draft matrix, 3/4 steps and schema for full and gentle-only catalogs');

foreach ([[], array_slice($genericCatalog, 0, 2)] as $sparseCatalog) {
    $controller = new SkinQuizController(new QuizEngineService($sparseCatalog));
    $result = $controller->handleSubmission(new WP_REST_Request('POST', json_encode($oilyPayload)));
    assert_true(is_wp_error($result) && $result->get_error_data()['status'] === 503, 'Incomplete inventory returns controlled 503, never partial/fake HTTP 200');
    assert_true($result->get_error_code() === 'kleer_catalog_incomplete', 'Stock shortage has stable error code');
}
$unsafeCatalog = array_map(static fn(array $p): array => array_replace($p, [
    'skin_types' => ['oily'], 'concerns' => ['aging'], 'is_gentle_fallback' => false,
]), $genericCatalog);
$unsafeResponse = (new SkinQuizController(new QuizEngineService($unsafeCatalog)))->handleSubmission(new WP_REST_Request('POST', json_encode($dryPayload)));
assert_true(is_wp_error($unsafeResponse) && $unsafeResponse->get_error_data()['status'] === 503, 'Sensitive skin never falls back to arbitrary incompatible product');
foreach (['missing', 'duplicate', 'unknown', 'multiple', 'no_consent'] as $case) {
    $p = $oilyPayload;
    if ($case === 'missing') { array_pop($p['answers']); }
    if ($case === 'duplicate') { $p['answers'][1] = $p['answers'][0]; }
    if ($case === 'unknown') { $p['answers'][0]['answer'] = 'not-an-option'; }
    if ($case === 'multiple') { $p['answers'][0]['answer'] = ['tight_dry', 'greasy_all']; }
    if ($case === 'no_consent') { $p['consent']['policy_accepted'] = false; }
    $r = (new SkinQuizController($fixtureEngine))->handleSubmission(new WP_REST_Request('POST', json_encode($p)));
    assert_true(is_wp_error($r) && $r->get_error_data()['status'] === 400, "Reject $case with HTTP 400");
}

// Test a realistic result set without creating products or changing the shared database.
if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
$db = new class {
    public string $posts = 'wp_posts';
    public string $postmeta = 'wp_postmeta';
    public string $term_relationships = 'wp_term_relationships';
    public string $term_taxonomy = 'wp_term_taxonomy';
    public string $terms = 'wp_terms';
    public string $last_error = '';
    public array $queries = [];
    public function get_results(string $sql, string $mode): array {
        $this->queries[] = $sql;
        return match(count($this->queries)) {
            1 => [['ID' => 812, 'post_title' => 'Actual DB cleanser'], ['ID' => 900, 'post_title' => 'Out of stock'], ['ID' => 901, 'post_title' => 'Hidden'], ['ID' => 902, 'post_title' => 'Variable']],
            2 => [
                ['post_id' => 812, 'meta_key' => '_price', 'meta_value' => '125000'],
                ['post_id' => 812, 'meta_key' => '_stock_status', 'meta_value' => 'instock'],
                ['post_id' => 900, 'meta_key' => '_price', 'meta_value' => '125000'],
                ['post_id' => 900, 'meta_key' => '_stock_status', 'meta_value' => 'outofstock'],
                ['post_id' => 901, 'meta_key' => '_price', 'meta_value' => '125000'],
                ['post_id' => 901, 'meta_key' => '_stock_status', 'meta_value' => 'instock'],
                ['post_id' => 902, 'meta_key' => '_price', 'meta_value' => '125000'],
                ['post_id' => 902, 'meta_key' => '_stock_status', 'meta_value' => 'instock'],
            ],
            3 => [
                ['object_id' => 812, 'term_taxonomy_id' => 1], ['object_id' => 812, 'term_taxonomy_id' => 2], ['object_id' => 812, 'term_taxonomy_id' => 3],
                ['object_id' => 900, 'term_taxonomy_id' => 1],
                ['object_id' => 901, 'term_taxonomy_id' => 1], ['object_id' => 901, 'term_taxonomy_id' => 4],
                ['object_id' => 902, 'term_taxonomy_id' => 1], ['object_id' => 902, 'term_taxonomy_id' => 5],
            ],
            4 => [
                ['term_taxonomy_id' => 1, 'taxonomy' => 'routine_step', 'slug' => 'cleanser'],
                ['term_taxonomy_id' => 2, 'taxonomy' => 'pa_skin_type', 'slug' => 'all_skin_types'],
                ['term_taxonomy_id' => 3, 'taxonomy' => 'skin_concern', 'slug' => 'soothing'],
                ['term_taxonomy_id' => 4, 'taxonomy' => 'product_visibility', 'slug' => 'exclude-from-catalog'],
                ['term_taxonomy_id' => 5, 'taxonomy' => 'product_type', 'slug' => 'variable'],
            ], default => [],
        };
    }
};
$catalog = (new RoutineProductRepository())->readCatalog($db);
assert_true(count($db->queries) === 4 && count($catalog) === 1, 'Repository uses four batched SELECTs and excludes unavailable, hidden and variable products');
assert_true($catalog[0]['id'] === 812 && $catalog[0]['price'] === 125000, 'Returned IDs/prices come from database, not fixture IDs');
assert_true($catalog[0]['is_gentle_fallback'] && $catalog[0]['skin_types'] === ['all_skin_types'], 'Reader accepts canonical and legacy pa_* terms');
$db->last_error = 'Internal credential details must not leak';
try {
    (new RoutineProductRepository())->readCatalog($db);
    assert_true(false, 'Database error must throw');
} catch (RoutineUnavailableException $e) {
    assert_true($e->reason === 'catalog_unavailable' && !str_contains($e->getMessage(), 'credential'), 'Database errors become a generic availability error');
}

function home_url(): string { return 'http://localhost:8081'; }
function wp_verify_nonce(string $nonce, string $action): bool { return $nonce === 'test-valid' && $action === 'wp_rest'; }
function is_user_logged_in(): bool { return false; }
$requestWithHeaders = static function (array $headers): WP_REST_Request {
    return new class($headers) extends WP_REST_Request {
        public function __construct(private array $headers) { parent::__construct('POST', ''); }
        public function get_header(string $key): ?string { return $this->headers[$key] ?? null; }
    };
};
$permission = new SkinQuizEndpoints();
assert_true($permission->checkPermission($requestWithHeaders([])) === true, 'WordPress null headers allow anonymous server-side requests');
foreach ([['origin' => 'https://evil.invalid'], ['x-wp-nonce' => 'invalid']] as $headers) {
    $r = $permission->checkPermission($requestWithHeaders($headers));
    assert_true(is_wp_error($r) && $r->get_error_data()['status'] === 403, 'Unapproved origin or supplied bad nonce receives 403');
}
assert_true($permission->checkPermission($requestWithHeaders(['origin' => home_url(), 'x-wp-nonce' => 'test-valid'])) === true, 'Same-origin request with valid nonce is accepted');
restore_error_handler();
echo "\nNhan verification: {$tests} assertions, {$failures} failures; 384 combinations x 2 catalogs.\n";
exit($failures > 0 ? 1 : 0);
