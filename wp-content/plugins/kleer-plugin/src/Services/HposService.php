<?php

namespace Kleer\Plugin\Services;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

class HposService
{
    /**
     * Khởi tạo và đăng ký các hook tương thích HPOS
     */
    public function register(): void
    {
        // 1. Khai báo tương thích HPOS với WooCommerce Core
        add_action('before_woocommerce_init', [$this, 'declareHposCompatibility']);

        // 2. Kích hoạt cờ Custom Tables & Background sync khi khởi tạo
        add_action('init', [$this, 'ensureHposConfiguration']);
    }

    /**
     * Khai báo plugin KLEER tương thích hoàn toàn với HPOS
     */
    public function declareHposCompatibility(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                KLEER_PLUGIN_FILE ?? __FILE__,
                true
            );
        }
    }

    /**
     * Đảm bảo cấu hình HPOS Custom Tables và Background Sync được bật
     */
    public function ensureHposConfiguration(): void
    {
        if (!class_exists(OrderUtil::class)) {
            return;
        }

        // Bật lưu trữ bảng chuyên biệt nếu chưa bật
        if (get_option('woocommerce_custom_orders_table_enabled') !== 'yes') {
            update_option('woocommerce_custom_orders_table_enabled', 'yes');
        }

        // Bật cờ đồng bộ nền (background sync) để đảm bảo an toàn dữ liệu
        if (get_option('woocommerce_custom_orders_table_data_sync_enabled') !== 'yes') {
            update_option('woocommerce_custom_orders_table_data_sync_enabled', 'yes');
        }
    }

    /**
     * Kiểm tra xem hệ thống đã thực sự chuyển sang dùng HPOS hay chưa
     */
    public static function isHposActive(): bool
    {
        if (class_exists(OrderUtil::class)) {
            return OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
}