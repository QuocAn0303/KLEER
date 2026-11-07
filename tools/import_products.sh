#!/bin/bash
# tools/import_products.sh
set -e

echo "=== 1. KIỂM TRA HÌNH ẢNH SẢN PHẨM WEBP ==="
if [ -d "wp-content/uploads/products" ]; then
    echo "Đã có sẵn kho ảnh WebP trong wp-content/uploads/products."
fi

echo "=== 2. IMPORT 500+ SKU VÀO DATABASE ==="
if [ -f "docker-compose.yml" ]; then
    echo "Đang nạp sản phẩm vào container WordPress (kleer-php)..."
    docker compose exec -T php php /var/www/html/tools/import_500_skus.php
else
    php tools/import_500_skus.php
fi

echo "=== 3. XÁC NHẬN SỐ LƯỢNG SẢN PHẨM SAU IMPORT ==="
if [ -f "docker-compose.yml" ]; then
    docker compose exec -T php php -r "require 'wp-load.php'; echo 'Tổng số sản phẩm đã đăng: ' . wp_count_posts('product')->publish . PHP_EOL;"
fi

echo "Hoàn thành import 500 SKU thành công!"
