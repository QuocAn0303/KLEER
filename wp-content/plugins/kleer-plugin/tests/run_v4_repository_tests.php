<?php

declare(strict_types=1);

// Isolated adapter regression: no WordPress bootstrap, network or database writes.
define('ABSPATH', dirname(__DIR__, 4) . '/');
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Kleer\\')) {
        require_once dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
    }
});
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class WooCommerce {}
class WP_Error {}
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }

final class RepositoryTestProduct
{
    public function __construct(public int $id, public mixed $price = '100000', public string $visibility = 'visible') {}
    public function get_id(): int { return $this->id; }
    public function get_name(): string { return 'Product ' . $this->id; }
    public function get_price(): mixed { return $this->price; }
    public function get_catalog_visibility(): string { return $this->visibility; }
}

function wc_get_products(array $args): object
{
    $GLOBALS['queries'][] = $args;
    if (($GLOBALS['fail_page'] ?? 0) === $args['page']) {
        throw new RuntimeException('Private database failure must not escape.');
    }
    return (object) [
        'products' => array_slice($GLOBALS['products'], ($args['page'] - 1) * $args['limit'], $args['limit']),
        'max_num_pages' => (int) ceil(count($GLOBALS['products']) / $args['limit']),
    ];
}
function taxonomy_exists(string $taxonomy): bool { return in_array($taxonomy, $GLOBALS['taxonomies'], true); }
function wp_get_object_terms(array $ids, array $taxonomies, array $args): array|WP_Error
{
    $GLOBALS['term_reads']++;
    if ($GLOBALS['fail_terms'] ?? false) { return new WP_Error(); }
    $out = [];
    foreach ($ids as $id) {
        foreach ($GLOBALS['terms'][$id] ?? [] as $taxonomy => $slugs) {
            if (!in_array($taxonomy, $taxonomies, true)) { continue; }
            foreach ($slugs as $slug) {
                $out[] = (object) ['object_id' => $id, 'taxonomy' => $taxonomy, 'slug' => $slug];
            }
        }
    }
    return $out;
}
function update_meta_cache(string $type, array $ids): void { $GLOBALS['meta_primes'][] = $ids; }
function get_post_meta(int $id, string $key, bool $single): mixed { return $GLOBALS['meta'][$id][$key] ?? ''; }

final class RepositoryTestCache extends Kleer\Support\Cache\KleerCache
{
    public array $values = [];
    public array $writes = [];
    public function __construct() {}
    public function get(string $key, string $group = 'default'): mixed { return $this->values[$key] ?? false; }
    public function set(string $key, mixed $value, string $group = 'default', int|false $expire = false): bool
    {
        $this->writes[] = ['key' => $key, 'group' => $group, 'ttl' => $expire];
        $this->values[$key] = $value;
        return true;
    }
}

$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    $checks++;
    if (!$ok) { throw new RuntimeException($message); }
};
$GLOBALS['products'] = array_map(static fn(int $id) => new RepositoryTestProduct($id), range(1, 251));
$GLOBALS['queries'] = $GLOBALS['meta_primes'] = $GLOBALS['terms'] = $GLOBALS['meta'] = [];
$GLOBALS['term_reads'] = 0;
$GLOBALS['taxonomies'] = ['pa_skin_type', 'pa_skin_concern', 'pa_routine_step', 'skin_type', 'skin_concern', 'routine_step'];
foreach (range(1, 251) as $id) {
    $GLOBALS['terms'][$id] = ['pa_skin_type' => ['oily'], 'pa_skin_concern' => ['acne'], 'pa_routine_step' => [$id === 251 ? 'serum' : 'cleanser']];
}
$GLOBALS['meta'][1]['_kleer_strong_active'] = '1';
$GLOBALS['meta'][2]['_kleer_has_strong_actives'] = 'yes';
$GLOBALS['terms'][3]['pa_skin_type'] = ['sensitive', 'dry', 'combination', 'oily', 'normal'];
$GLOBALS['products'][3]->visibility = 'hidden';
$GLOBALS['products'][4]->visibility = 'search';
$GLOBALS['products'][5]->price = '0';
$GLOBALS['products'][6]->price = 'not-a-price';
$GLOBALS['terms'][8] = ['skin_type' => ['dry'], 'skin_concern' => ['aging'], 'routine_step' => ['serum']];
$GLOBALS['terms'][9]['pa_skin_type'] = ['dry', 'sensitive'];
$GLOBALS['meta'][10] = ['_kleer_strong_active' => '0', '_kleer_has_strong_actives' => '1', '_kleer_gentle_fallback' => 'false'];
$GLOBALS['meta'][11]['_kleer_strong_active'] = 'false';
$GLOBALS['meta'][12]['_kleer_strong_active'] = 'true';
$GLOBALS['meta'][13]['_kleer_strong_active'] = 'on';
$GLOBALS['terms'][14]['skin_type'] = ['dry'];
$GLOBALS['terms'][15]['pa_routine_step'] = [];

$cache = new RepositoryTestCache();
$repo = new Kleer\Repositories\WooCommerceProductRepository($cache);
$catalog = $repo->getQuizCatalog(['version' => 'v4']);
$byId = array_column($catalog, null, 'id');
$check(count($GLOBALS['queries']) === 3, '251 products require all three pages.');
$check(isset($byId[251]), 'A product beyond page one must be available.');
$check($GLOBALS['queries'][0]['orderby'] === 'ID' && $GLOBALS['queries'][0]['order'] === 'ASC', 'Stable pagination order.');
$check($GLOBALS['queries'][0]['type'] === 'simple', 'Read supported simple products.');
$check($GLOBALS['queries'][0]['stock_status'] === 'instock', 'Query only in-stock products.');
$check($GLOBALS['term_reads'] === 1, 'Read taxonomy terms in one batch.');
$check(count($GLOBALS['meta_primes'][0]) === 251, 'Prime product metadata together.');
$check($byId[1]['has_strong_actives'] === true, 'Read canonical CSV strong-active flag.');
$check($byId[2]['has_strong_actives'] === true, 'Read legacy key when canonical is absent.');
$check($byId[10]['has_strong_actives'] === false, 'Canonical false takes precedence over legacy true.');
$check($byId[11]['has_strong_actives'] === false, 'String false is false.');
$check($byId[12]['has_strong_actives'] && $byId[13]['has_strong_actives'], 'Normalize true/on flags.');
$check($byId[10]['is_gentle_fallback'] === false, 'String false must not create a gentle-fallback tag.');
$check($byId[3]['skin_types'] === ['all_skin_types'], 'Five CSV skin types map to all_skin_types.');
$check($byId[9]['skin_types'] === ['dry', 'sensitive'], 'Two skin types retain their specific bonus.');
$check($byId[14]['skin_types'] === ['oily'], 'Canonical taxonomy takes precedence over legacy.');
$check($byId[8]['skin_types'] === ['dry'] && $byId[8]['routine_step'] === 'serum', 'Read existing legacy terms without a migration.');
$check(!isset($byId[4], $byId[5], $byId[6], $byId[7], $byId[15]), 'Exclude hidden, search-only, invalid-price and missing-step products.');
$check(count($catalog) === 246, 'All remaining eligible products are retained.');
$check($cache->writes[0]['ttl'] === 60 && $cache->writes[0]['group'] === 'kleer_products', 'Bound catalog caching to the existing invalidation group.');
$check(str_starts_with($cache->writes[0]['key'], 'catalog_live_v3_'), 'Bypass stale v2 caches.');
$again = $repo->getQuizCatalog(['version' => 'v4']);
$check($again === $catalog && count($GLOBALS['queries']) === 3, 'Warm catalog avoids repeating WooCommerce queries.');
$routine = (new Kleer\Services\QuizEngineService($catalog))->recommendRoutineV4('oily', false, 'none', 2, false, $catalog);
$check(in_array(251, array_column($routine['products'], 'id'), true), 'Engine can recommend the serum found after product 100.');

$GLOBALS['fail_page'] = 3;
$failedCache = new RepositoryTestCache();
try {
    (new Kleer\Repositories\WooCommerceProductRepository($failedCache))->getQuizCatalog(['version' => 'v4']);
    $check(false, 'Failed later page must not return an incomplete catalog.');
} catch (Kleer\Exceptions\RoutineUnavailableException $e) {
    $check($e->reason === 'catalog_unavailable', 'Page failure becomes controlled availability error.');
    $check($failedCache->writes === [], 'Never cache a partial failed read.');
}
$GLOBALS['fail_page'] = 0;
$GLOBALS['fail_terms'] = true;
try {
    (new Kleer\Repositories\WooCommerceProductRepository(new RepositoryTestCache()))->getQuizCatalog(['version' => 'v4']);
    $check(false, 'Term read failure must not pretend inventory is empty.');
} catch (Kleer\Exceptions\RoutineUnavailableException $e) {
    $check($e->reason === 'catalog_unavailable', 'Taxonomy failure becomes controlled availability error.');
}
$GLOBALS['fail_terms'] = false;
$GLOBALS['products'] = [];
$check((new Kleer\Repositories\WooCommerceProductRepository(new RepositoryTestCache()))->getQuizCatalog(['version' => 'v4']) === [], 'Genuinely empty inventory retains v4 missing_steps behavior.');
echo json_encode(['suite' => 'v4_live_repository_isolated', 'assertions' => $checks, 'failures' => 0], JSON_PRETTY_PRINT), "\n";
