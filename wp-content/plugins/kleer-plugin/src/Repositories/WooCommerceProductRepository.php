<?php

declare(strict_types=1);

namespace Kleer\Repositories;

use Kleer\Contracts\QuizProductRepositoryInterface;
use Kleer\Exceptions\RoutineUnavailableException;
use Kleer\Support\Cache\KleerCache;
use Kleer\Taxonomy\TaxonomyRegistry;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Data Access Layer for WooCommerce Skincare Products.
 *
 * Layer: Repositories (Data Persistence)
 * Responsibility: Truy vấn sản phẩm WooCommerce thực tế qua WC APIs (wc_get_products),
 *                 ánh xạ 3 Custom Taxonomies (pa_skin_type, pa_skin_concern, pa_routine_step),
 *                 tích hợp KleerCache và fallback an toàn khi WooCommerce chưa có dữ liệu.
 */
class WooCommerceProductRepository implements QuizProductRepositoryInterface

{
    private ?KleerCache $cache;

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $fallbackCatalog;

    /**
     * @param KleerCache|null $cache Bộ đệm tầng hạ tầng (nếu null sẽ lấy từ môi trường)
     * @param list<array<string, mixed>>|null $fallbackCatalog Explicit test fixtures only; never auto-populated in production.
     */
    public function __construct(?KleerCache $cache = null, ?array $fallbackCatalog = null)
    {
        $this->cache = $cache;
        $this->fallbackCatalog = $fallbackCatalog;
    }

    /**
     * Lấy danh sách sản phẩm nổi bật theo hợp đồng cũ.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function findFeatured(int $limit = 5): array
    {
        $catalog = $this->getQuizCatalog();
        $sliced = array_slice($catalog, 0, $limit);

        return array_map(static function (array $item): array {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
            ];
        }, $sliced);
    }

    /**
     * Lấy toàn bộ danh mục sản phẩm cho Quiz Engine kèm cache và taxonomy mapping.
     *
     * @param array<string, mixed> $criteria
     * @return list<array<string, mixed>>
     */
    public function getQuizCatalog(array $criteria = []): array
    {
        // Explicit fixtures are allowed for isolated tests, never as implicit live fallback.
        if ($this->fallbackCatalog !== null) {
            return $this->fallbackCatalog;
        }
        if (!class_exists('WooCommerce') || !function_exists('wc_get_products')) {
            throw new RoutineUnavailableException([], 'woocommerce_unavailable');
        }
        $version = $criteria['version'] ?? (($criteria['fallback_catalog'] ?? '') === 'v4' ? 'v4' : 'v1');
        if ($version === 'v1') {
            return (new RoutineProductRepository())->all();
        }
        $cache = $this->cache ?? KleerCache::fromEnvironment();
        // New key prevents reading the former truncated catalog / legacy meta mapping.
        $cacheKey = 'catalog_live_v3_' . md5(serialize($criteria));

        $cached = $cache->get($cacheKey, KleerCache::GROUP_PRODUCTS);

        if (is_array($cached) && !empty($cached)) {
            /** @var list<array<string, mixed>> $cached */
            return $cached;
        }

        if (is_string($cached) && $cached !== '') {
            $decoded = json_decode($cached, true);

            if (is_array($decoded) && !empty($decoded)) {
                /** @var list<array<string, mixed>> $decoded */
                return $decoded;
            }
        }

        $catalog = $this->fetchFromWooCommerce($criteria);

        // v4 retains its existing missing_steps/warnings contract for a genuinely empty catalog.

        // KleerCache chỉ nhận string, nên serialize catalog trước khi ghi cache.
        // Cache group phải trùng với group mà CacheInvalidation flush khi sản phẩm thay đổi.
        $encoded = function_exists('wp_json_encode')
            ? wp_json_encode($catalog)
            : json_encode($catalog);

        if (is_string($encoded) && $encoded !== '') {
            $cache->set($cacheKey, $encoded, KleerCache::GROUP_PRODUCTS, 60);
        }

        return $catalog;
    }

    /**
     * Truy vấn thực tế từ WooCommerce qua wc_get_products().
     *
     * @param array<string, mixed> $criteria
     * @return list<array<string, mixed>>
     */
    private function fetchFromWooCommerce(array $criteria = []): array
    {
        if (!function_exists('wc_get_products')) {
            return [];
        }

        try {
            // limit is a page size, never a cap on the complete recommendation catalog.
            $pageSize = max(1, min(500, (int) ($criteria['page_size'] ?? $criteria['limit'] ?? 100)));
            $wcProducts = [];
            $page = 1;
            do {
                $result = wc_get_products([
                    'type' => 'simple',
                    'status' => 'publish',
                    'limit' => $pageSize,
                    'page' => $page,
                    'paginate' => true,
                    'orderby' => 'ID',
                    'order' => 'ASC',
                    'stock_status' => 'instock',
                    'return' => 'objects',
                ]);
                if (!is_object($result) || !isset($result->products, $result->max_num_pages)
                    || !is_array($result->products)) {
                    throw new \RuntimeException('Invalid WooCommerce paginated catalog.');
                }
                foreach ($result->products as $product) {
                    $wcProducts[(int) $product->get_id()] = $product;
                }
                $page++;
            } while ($page <= (int) $result->max_num_pages && $result->products !== []);

            $ids = array_keys($wcProducts);
            if ($ids === []) {
                return [];
            }
            if (function_exists('update_meta_cache')) {
                update_meta_cache('post', $ids);
            }
            $terms = $this->loadCatalogTerms($ids);
        } catch (\Throwable $e) {
            // A failed later page must not masquerade as a genuinely sparse catalog.
            throw new RoutineUnavailableException([], 'catalog_unavailable');
        }

        $catalog = [];

        foreach ($wcProducts as $p) {
            $id = $p->get_id();

            $price = $p->get_price();
            if (!is_numeric($price) || (float) $price <= 0
                || in_array($p->get_catalog_visibility(), ['hidden', 'search'], true)) {
                continue;
            }

            $skinTypes = $this->productTerms($terms, $id, TaxonomyRegistry::TAXONOMY_SKIN_TYPE);
            $concerns = $this->productTerms($terms, $id, TaxonomyRegistry::TAXONOMY_SKIN_CONCERN);
            $steps = $this->productTerms($terms, $id, TaxonomyRegistry::TAXONOMY_ROUTINE_STEP);
            // CSV's five explicit types mean "all types", not a skin-specific +1 bonus.
            if (array_diff(['sensitive', 'dry', 'combination', 'oily', 'normal'], $skinTypes) === []) {
                $skinTypes = ['all_skin_types'];
            }

            // Không gán bừa sản phẩm chưa phân loại vào bước cleanser.
            // Sản phẩm thiếu routine step sẽ không được đưa vào gợi ý.
            if (empty($steps)) {
                continue;
            }

            $stepOrder = array_flip([
                'makeup_remover',
                'cleanser',
                'toner',
                'serum',
                'treatment',
                'moisturizer',
                'sunscreen',
            ]);

            usort($steps, static function (string $a, string $b) use ($stepOrder): int {
                return ($stepOrder[$a] ?? 99) <=> ($stepOrder[$b] ?? 99);
            });

            $step = $steps[0];
            $secondaryStep = $steps[1] ?? '';

            // Đọc hình ảnh và link sản phẩm
            $imageUrl = '';
            if (function_exists('wp_get_attachment_image_url')) {
                $imgId = $p->get_image_id();
                $imageUrl = $imgId ? (string) wp_get_attachment_image_url($imgId, 'medium') : '';
            }

            $permalink = function_exists('get_permalink') ? (string) get_permalink($id) : '';

            // Đọc cờ hoạt chất mạnh và gentle fallback từ meta hoặc thuộc tính
            $hasStrongActive = false;
            $activeIngredients = '';
            if (function_exists('get_post_meta')) {
                $activeIngredients = (string) get_post_meta($id, '_kleer_active_ingredients', true);
                $flag = get_post_meta($id, '_kleer_strong_active', true);
                // Support data already stored under the former reader key; canonical 0 wins.
                if ($flag === '' || $flag === null) {
                    $flag = get_post_meta($id, '_kleer_has_strong_actives', true);
                }
                $hasStrongActive = $this->metaFlag($flag);
            }

            $isGentleFallback = in_array('all_skin_types', $skinTypes, true)
                || in_array('sensitive', $skinTypes, true)
                || (function_exists('get_post_meta') && $this->metaFlag(get_post_meta($id, '_kleer_gentle_fallback', true)));

            $catalog[] = [
                'id'                 => $id,
                'name'               => $p->get_name(),
                'price'              => (int) $price,
                'routine_step'       => $step,
                'secondary_step'     => $secondaryStep,
                'skin_types'         => $skinTypes,
                'concerns'           => $concerns,
                'is_gentle_fallback' => $isGentleFallback,
                'has_strong_actives' => $hasStrongActive,
                'active_ingredients' => $activeIngredients,
                'image_url'          => $imageUrl,
                'permalink'          => $permalink,
                'reason'             => sprintf('Sản phẩm chuẩn WooCommerce khớp với bước %s.', $step),
            ];
        }

        return $catalog;
    }

    /**
     * Read all product terms in one call, rather than three queries per SKU.
     * Existing legacy terms can be read without registering or migrating taxonomies.
     *
     * @param list<int> $ids
     * @return array<int, array<string, list<string>>>
     */
    private function loadCatalogTerms(array $ids): array
    {
        $taxonomies = [];
        foreach (TaxonomyRegistry::getTaxonomySlugs() as $canonical) {
            foreach ([$canonical, substr($canonical, 3)] as $slug) {
                if (taxonomy_exists($slug)) {
                    $taxonomies[] = $slug;
                }
            }
        }
        if ($taxonomies === []) {
            return [];
        }
        $result = wp_get_object_terms($ids, $taxonomies, ['fields' => 'all_with_object_id']);
        if (is_wp_error($result) || !is_array($result)) {
            throw new \RuntimeException('Unable to read product taxonomy terms.');
        }
        $mapped = [];
        foreach ($result as $term) {
            $mapped[(int) $term->object_id][$term->taxonomy][] = $term->slug;
        }
        return $mapped;
    }

    /** @return list<string> */
    private function productTerms(array $terms, int $id, string $canonical): array
    {
        return array_values(array_unique($terms[$id][$canonical] ?? $terms[$id][substr($canonical, 3)] ?? []));
    }

    private function metaFlag(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true', 'on'], true);
    }
}
