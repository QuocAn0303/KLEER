#!/bin/bash
# tools/import_products.sh
set -e

echo "=== 1. TỐI ƯU HÓA HÌNH ẢNH SANG WEBP (< 120KB) ==="
# Cần cài imagemagick hoặc cwebp trong môi trường
if command -v cwebp &> /dev/null; then
    for img in images/products/*.{jpg,png}; do
        [ -f "$img" ] || continue
        filename=$(basename -- "$img")
        name="${filename%.*}"
        cwebp -q 75 -resize 800 800 "$img" -o "wp-content/uploads/products/${name}.webp"
    done
    echo "Đã tối ưu hóa ảnh sang định dạng WebP."
else
    echo "Bỏ qua cwebp (sử dụng ảnh webp định dạng sẵn)."
fi

echo "=== 2. IMPORT 500 SKU VÀO DATABASE BẰNG WP-CLI ==="
CSV_PATH="data/woocommerce_500_skus.csv"

# Kiểm tra nếu chạy trực tiếp qua docker compose
if [ -f "docker-compose.yml" ]; then
    echo "Đang nạp sản phẩm vào container WordPress..."
    docker compose exec -T wordpress wp wc product import "$CSV_PATH" \
        --user=admin \
        --path=/var/www/html
else
    # Chạy trực tiếp nếu có wp-cli cục bộ
    wp wc product import "$CSV_PATH" --user=admin
fi

echo "=== 3. XÁC NHẬN SỐ LƯỢNG SẢN PHẨM SAU IMPORT ==="
if [ -f "docker-compose.yml" ]; then
    docker compose exec -T wordpress wp post list --post_type=product --format=count
fi

echo "Hoàn thành import 500 SKU thành công!"