<?php

declare(strict_types=1);

namespace Kleer\Repositories;

use Kleer\Exceptions\RoutineUnavailableException;
use Kleer\Support\Cache\KleerCache;

defined('ABSPATH') || exit;

/** Batched read adapter for WooCommerce's current product CPT storage. */
final class RoutineProductRepository
{
    private const CACHE_KEY = 'routine_catalog_v1';

    public static function invalidate(): void
    {
        KleerCache::fromEnvironment()->delete(self::CACHE_KEY, KleerCache::GROUP_PRODUCTS);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        if (!class_exists('WooCommerce')) {
            throw new RoutineUnavailableException([], 'woocommerce_unavailable');
        }
        $cache = KleerCache::fromEnvironment();
        $cached = $cache->get(self::CACHE_KEY, KleerCache::GROUP_PRODUCTS);
        if (is_string($cached)) {
            $catalog = json_decode($cached, true);
            if (is_array($catalog)) {
                return $catalog;
            }
        }
        global $wpdb;
        $catalog = $this->readCatalog($wpdb);
        $cache->set(self::CACHE_KEY, json_encode($catalog, JSON_THROW_ON_ERROR), KleerCache::GROUP_PRODUCTS, 60);
        return $catalog;
    }

    /**
     * Four batched SELECTs, no N+1 queries and no fixture ID substitution.
     * Public for integration/EXPLAIN checks against a real wpdb connection.
     * @return list<array<string, mixed>>
     */
    public function readCatalog(object $db): array
    {
        $products = $db->get_results(
            "SELECT ID, post_title FROM {$db->posts} WHERE post_type = 'product' AND post_status = 'publish' ORDER BY ID",
            ARRAY_A
        );
        $this->checkDatabase($db);
        if (!$products) {
            return [];
        }
        $ids = implode(',', array_map(static fn(array $p): int => (int) $p['ID'], $products));
        $meta = $db->get_results(
            "SELECT post_id, meta_key, meta_value FROM {$db->postmeta} WHERE post_id IN ($ids) AND meta_key IN ('_price', '_stock_status', '_kleer_gentle_fallback')",
            ARRAY_A
        );
        $this->checkDatabase($db);
        $relations = $db->get_results(
            "SELECT object_id, term_taxonomy_id FROM {$db->term_relationships} WHERE object_id IN ($ids)",
            ARRAY_A
        );
        $this->checkDatabase($db);
        $terms = $db->get_results(
            "SELECT tt.term_taxonomy_id, tt.taxonomy, t.slug FROM {$db->term_taxonomy} tt INNER JOIN {$db->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('skin_type','skin_concern','routine_step','pa_skin_type','pa_skin_concern','pa_routine_step','product_visibility','product_type')",
            ARRAY_A
        );
        $this->checkDatabase($db);
        $metadata = [];
        foreach ($meta as $row) {
            $metadata[(int) $row['post_id']][$row['meta_key']] = $row['meta_value'];
        }
        $termMap = [];
        foreach ($terms as $term) {
            $termMap[(int) $term['term_taxonomy_id']] = $term;
        }
        $tags = [];
        foreach ($relations as $relation) {
            $term = $termMap[(int) $relation['term_taxonomy_id']] ?? null;
            if ($term !== null) {
                $taxonomy = preg_replace('/^pa_/', '', $term['taxonomy']);
                $tags[(int) $relation['object_id']][$taxonomy][] = $term['slug'];
            }
        }
        $catalog = [];
        foreach ($products as $product) {
            $id = (int) $product['ID'];
            $m = $metadata[$id] ?? [];
            $t = $tags[$id] ?? [];
            if (($m['_stock_status'] ?? '') !== 'instock' || !isset($m['_price']) || !is_numeric($m['_price']) || (float) $m['_price'] < 0) {
                continue;
            }
            // Variable/external/grouped products require a separate purchasable-variation contract.
            if (array_diff($t['product_type'] ?? ['simple'], ['simple']) !== []) {
                continue;
            }
            if (in_array('exclude-from-catalog', $t['product_visibility'] ?? [], true)) {
                continue;
            }
            $steps = array_values(array_unique($t['routine_step'] ?? []));
            if (count($steps) !== 1 || !in_array($steps[0], ['cleanser','treatment','moisturizer','sunscreen'], true)) {
                continue;
            }
            $types = array_values(array_unique($t['skin_type'] ?? []));
            $concerns = array_values(array_unique($t['skin_concern'] ?? []));
            $catalog[] = [
                'id' => $id,
                'name' => $product['post_title'],
                'price' => (int) round((float) $m['_price']),
                'routine_step' => $steps[0],
                'skin_types' => $types,
                'concerns' => $concerns,
                'is_gentle_fallback' => ($m['_kleer_gentle_fallback'] ?? '') === 'yes'
                    || (in_array('all_skin_types', $types, true) && in_array('soothing', $concerns, true)),
            ];
        }
        return $catalog;
    }

    private function checkDatabase(object $db): void
    {
        if (!empty($db->last_error)) {
            throw new RoutineUnavailableException([], 'catalog_unavailable');
        }
    }
}
