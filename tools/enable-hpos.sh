#!/bin/bash
# tools/enable-hpos.sh
set -e

echo "=== KÍCH HOẠT WOOCOMMERCE HPOS (HIGH-PERFORMANCE ORDER STORAGE) ==="

# Chạy thông qua Docker container wordpress
if [ -f "docker-compose.yml" ]; then
    echo "1. Bật tính năng Custom Orders Tables..."
    docker compose exec -T wordpress wp option update woocommerce_custom_orders_table_enabled yes --path=/var/www/html

    echo "2. Bật tính năng đồng bộ nền (Background Sync)..."
    docker compose exec -T wordpress wp option update woocommerce_custom_orders_table_data_sync_enabled yes --path=/var/www/html

    echo "3. Thực thi đồng bộ đơn hàng sang các bảng wp_wc_*..."
    docker compose exec -T wordpress wp wc cot sync --path=/var/www/html || true

    echo "4. Kiểm tra trạng thái kích hoạt:"
    docker compose exec -T wordpress wp option get woocommerce_custom_orders_table_enabled --path=/var/www/html
else
    # Chạy trực tiếp nếu có sẵn wp-cli cục bộ
    wp option update woocommerce_custom_orders_table_enabled yes
    wp option update woocommerce_custom_orders_table_data_sync_enabled yes
    wp wc cot sync || true
fi

echo "=== KÍCH HOẠT HPOS HOÀN TẤT THÀNH CÔNG ==="