<?php

declare(strict_types=1);

namespace Kleer\Support\Cache;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Automates cache invalidation when products, orders or WordPress options change.
 *
 * Layer: Support / Cache
 */
class CacheInvalidation
{
    private KleerCache $cache;

    public function __construct(KleerCache $cache)
    {
        $this->cache = $cache;
    }

    public function register(): void
    {
        $hooks = [
            'save_post_product',
            'deleted_post',
            'woocommerce_update_product',
            'updated_option',
            'deleted_option',
            'woocommerce_new_product',
            'woocommerce_delete_product',
        ];

        foreach ($hooks as $hook) {
            add_action($hook, [$this, 'invalidateProductCache']);
        }
    }

    public function invalidateProductCache(): void
    {
        $this->cache->flush(KleerCache::GROUP_PRODUCTS);
        $this->cache->flush(KleerCache::GROUP_API);
    }
}
