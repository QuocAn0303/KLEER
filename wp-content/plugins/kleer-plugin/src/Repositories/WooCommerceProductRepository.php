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
        $cacheKey = 'catalog_live_v2_' . md5(serialize($criteria));

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

        $queryArgs = [
            'status'       => 'publish',
            'limit'        => $criteria['limit'] ?? 100,
            'stock_status' => 'instock',
            'return'       => 'objects',
        ];

        try {
            /** @var list<\WC_Product> $wcProducts */
            $wcProducts = wc_get_products($queryArgs);
        } catch (\Throwable $e) {
            return [];
        }

        if (empty($wcProducts)) {
            return [];
        }

        $catalog = [];

        foreach ($wcProducts as $p) {
            $id = $p->get_id();

            // Đọc 3 Taxonomies chuẩn
            $skinTypes = $this->getTermsSlugs($id, TaxonomyRegistry::TAXONOMY_SKIN_TYPE);
            $concerns  = $this->getTermsSlugs($id, TaxonomyRegistry::TAXONOMY_SKIN_CONCERN);
            $steps     = $this->getTermsSlugs($id, TaxonomyRegistry::TAXONOMY_ROUTINE_STEP);

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
                $hasStrongActive = (bool) get_post_meta($id, '_kleer_has_strong_actives', true);
            }

            $isGentleFallback = in_array('all_skin_types', $skinTypes, true)
                || in_array('sensitive', $skinTypes, true)
                || (function_exists('get_post_meta') && (bool) get_post_meta($id, '_kleer_gentle_fallback', true));

            $catalog[] = [
                'id'                 => $id,
                'name'               => $p->get_name(),
                'price'              => (int) $p->get_price(),
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
     * @return list<string>
     */
    private function getTermsSlugs(int $productId, string $taxonomy): array
    {
        if (!function_exists('wp_get_post_terms')) {
            return [];
        }

        $terms = wp_get_post_terms($productId, $taxonomy, ['fields' => 'slugs']);

        if (is_array($terms) && !is_wp_error($terms)) {
            return array_values(array_map('strval', $terms));
        }

        return [];
    }
}
