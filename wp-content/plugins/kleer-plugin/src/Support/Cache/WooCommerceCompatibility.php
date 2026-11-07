<?php

declare(strict_types=1);

namespace Kleer\Support\Cache;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Declares HPOS (High-Performance Order Storage) and Block Compatibility for WooCommerce.
 *
 * Layer: Support / Cache
 */
class WooCommerceCompatibility
{
    public const FEATURE_HPOS = 'custom_order_tables';
    public const FEATURE_CART = 'cart_block';
    public const FEATURE_CHECKOUT = 'checkout_block';

    /**
     * @var list<string>
     */
    private array $features;

    /**
     * @param list<string> $features
     */
    public function __construct(array $features = [self::FEATURE_HPOS, self::FEATURE_CART, self::FEATURE_CHECKOUT])
    {
        $this->features = $features;
    }

    /**
     * @return list<string>
     */
    public function features(): array
    {
        return $this->features;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public function subscriptionMap(): array
    {
        return [
            ['before_woocommerce_init', 'declareCompatibility'],
        ];
    }

    public function register(): void
    {
        add_action('before_woocommerce_init', [$this, 'declareCompatibility']);
        add_action('before_woocommerce_init', [$this, 'declareCartCheckoutCompatibility']);
    }

    public function declareCompatibility(): bool
    {
        if (!class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            return false;
        }

        $pluginFile = defined('KLEER_PLUGIN_FILE') ? KLEER_PLUGIN_FILE : __FILE__;

        if (in_array(self::FEATURE_HPOS, $this->features, true)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', $pluginFile, true);
        }

        return true;
    }

    public function declareCartCheckoutCompatibility(): bool
    {
        if (!class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            return false;
        }

        $pluginFile = defined('KLEER_PLUGIN_FILE') ? KLEER_PLUGIN_FILE : __FILE__;

        if (in_array(self::FEATURE_CART, $this->features, true)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_block', $pluginFile, true);
        }

        if (in_array(self::FEATURE_CHECKOUT, $this->features, true)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('checkout_block', $pluginFile, true);
        }

        return true;
    }
}
