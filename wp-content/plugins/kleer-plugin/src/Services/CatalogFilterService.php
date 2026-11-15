<?php

declare(strict_types=1);

namespace Kleer\Services;

use Kleer\Support\Cache\KleerCache;
use Kleer\Support\ProductCardPresenter;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * AJAX catalog query for the storefront (home shelf + catalog grid + shop filters).
 *
 * Layer: Services
 * Responsibility: Nhận tham số lọc, truy vấn WooCommerce, chuẩn hóa qua ProductCardPresenter
 *                 và trả JSON cho catalog-filter.js. Không render HTML.
 */
final class CatalogFilterService
{
    private const AJAX_ACTION = 'kleer_filter_products';
    private const NONCE_ACTIONS = ['kleer_filter_products', 'kleer_ajax_nonce'];
    private const CACHE_TTL = 300;
    private const MAX_LIMIT = 48;

    public function register(): void
    {
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleAjax']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handleAjax']);
    }

    public function handleAjax(): void
    {
        if (!$this->verifyNonce()) {
            wp_send_json_error(['message' => __('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'kleer-plugin')], 403);
            return;
        }

        if (!function_exists('wc_get_product') || !class_exists('WC_Product')) {
            wp_send_json_error(['message' => __('WooCommerce không khả dụng.', 'kleer-plugin')], 400);
            return;
        }

        $args = $this->readArgs();
        $cache = KleerCache::fromEnvironment();
        $cache_key = 'filter_' . md5((string) wp_json_encode($args));

        $cached = $cache->get($cache_key, KleerCache::GROUP_PRODUCTS);
        if (is_array($cached) && isset($cached['products'])) {
            wp_send_json_success($cached);
            return;
        }

        $query = new \WP_Query($this->buildQueryArgs($args));
        $products = [];

        foreach ($query->posts as $post) {
            $product_id = is_object($post) ? (int) ($post->ID ?? 0) : (int) $post;
            $product = wc_get_product($product_id);
            if (!$product instanceof \WC_Product) {
                continue;
            }
            $products[] = ProductCardPresenter::present($product);
        }

        $payload = [
            'products' => $products,
            'total' => (int) $query->found_posts,
            'has_more' => $query->found_posts > count($products),
            'filters' => $args,
        ];

        $cache->set($cache_key, $payload, KleerCache::GROUP_PRODUCTS, self::CACHE_TTL);

        wp_reset_postdata();

        wp_send_json_success($payload);
    }

    private function verifyNonce(): bool
    {
        $nonce = $_POST['nonce'] ?? ($_GET['nonce'] ?? ($_SERVER['HTTP_X_WP_NONCE'] ?? ''));
        if (!is_string($nonce) || $nonce === '') {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($nonce));

        foreach (self::NONCE_ACTIONS as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function readArgs(): array
    {
        return [
            'skin_type' => $this->readList('skin_type'),
            'routine_step' => $this->readList('routine_step'),
            'skin_concern' => $this->readList('skin_concern'),
            'search' => isset($_POST['search']) ? sanitize_text_field(wp_unslash((string) $_POST['search'])) : '',
            'min_price' => isset($_POST['min_price']) && $_POST['min_price'] !== '' ? max(0, (int) $_POST['min_price']) : null,
            'max_price' => isset($_POST['max_price']) && $_POST['max_price'] !== '' ? max(0, (int) $_POST['max_price']) : null,
            'limit' => isset($_POST['limit']) ? min(self::MAX_LIMIT, max(1, (int) $_POST['limit'])) : 24,
        ];
    }

    /**
     * @return list<string>
     */
    private function readList(string $key): array
    {
        $raw = $_POST[$key] ?? $_POST[$key . '[]'] ?? null;
        if ($raw === null) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode(stripslashes($raw), true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        if (!is_array($raw)) {
            return [];
        }

        $terms = [];
        foreach ($raw as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $slug = sanitize_title((string) $value);
            if ($slug !== '') {
                $terms[$slug] = $slug;
            }
        }

        return array_values($terms);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function buildQueryArgs(array $args): array
    {
        $query = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => (int) $args['limit'],
            'ignore_sticky_posts' => true,
            'fields' => 'ids',
            'no_found_rows' => false,
        ];

        if ($args['search'] !== '') {
            $query['s'] = (string) $args['search'];
        }

        $tax_query = ['relation' => 'AND'];
        foreach (
            [
                'pa_skin_type' => $args['skin_type'],
                'pa_routine_step' => $args['routine_step'],
                'pa_skin_concern' => $args['skin_concern'],
            ] as $taxonomy => $terms
        ) {
            if (empty($terms) || !taxonomy_exists($taxonomy)) {
                continue;
            }
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $terms,
                'operator' => 'IN',
            ];
        }
        if (count($tax_query) > 1) {
            $query['tax_query'] = $tax_query;
        }

        $meta_query = ['relation' => 'AND'];
        if ($args['min_price'] !== null) {
            $meta_query[] = [
                'key' => '_price',
                'value' => (int) $args['min_price'],
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        if ($args['max_price'] !== null) {
            $meta_query[] = [
                'key' => '_price',
                'value' => (int) $args['max_price'],
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }
        if (count($meta_query) > 1) {
            $query['meta_query'] = $meta_query;
        }

        return $query;
    }
}
