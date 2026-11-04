<?php
/**
 * KLEER - Import 500+ SKUs into WooCommerce
 *
 * Chạy trực tiếp qua PHP CLI:
 * docker exec kleer-php php /var/www/html/tools/import_500_skus.php
 */

// Đảm bảo không giới hạn bộ nhớ và thời gian thực thi cho import
ini_set('memory_limit', '1024M');
set_time_limit(0);

// Load WordPress environment
require_once __DIR__ . '/../wp-load.php';

if (!class_exists('WooCommerce')) {
    echo "[ERROR] WooCommerce is not active!\n";
    exit(1);
}

// Ensure admin environment functions are loaded
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

echo "========================================\n";
echo "  KLEER - IMPORT 500+ SKU VÀO DATABASE  \n";
echo "========================================\n\n";

// 1. Đăng ký các WooCommerce Attributes nếu chưa có
echo "[1/4] Đăng ký WooCommerce Product Attributes...\n";
$target_attributes = [
    'skin_type'         => 'Loại da',
    'skin_concern'      => 'Vấn đề da',
    'routine_step'      => 'Bước Routine',
    'brand'             => 'Thương hiệu',
    'active_ingredient' => 'Hoạt chất',
];

$existing_taxonomies = [];
foreach (wc_get_attribute_taxonomies() as $attr) {
    $existing_taxonomies['pa_' . $attr->attribute_name] = (int) $attr->attribute_id;
}

foreach ($target_attributes as $slug => $label) {
    $tax_name = 'pa_' . $slug;
    if (!isset($existing_taxonomies[$tax_name])) {
        $attr_id = wc_create_attribute([
            'name'         => $label,
            'slug'         => $slug,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => true,
        ]);
        if (!is_wp_error($attr_id)) {
            $existing_taxonomies[$tax_name] = (int) $attr_id;
            echo "   -> Đã tạo attribute: {$tax_name} ({$label})\n";
        }
    } else {
        echo "   -> Attribute {$tax_name} đã tồn tại (ID: {$existing_taxonomies[$tax_name]})\n";
    }

    if (!taxonomy_exists($tax_name)) {
        register_taxonomy($tax_name, ['product'], [
            'hierarchical' => false,
            'label'        => $label,
            'query_var'    => true,
            'rewrite'      => ['slug' => $slug],
        ]);
    }
    register_taxonomy_for_object_type($tax_name, 'product');
}

// 2. Index toàn bộ ảnh WebP trong uploads/products thành WordPress Attachment
echo "\n[2/4] Đồng bộ Media Library cho kho ảnh WebP...\n";
$images_dir = WP_CONTENT_DIR . '/uploads/products';
$image_map = []; // [filename => attachment_id]

// Lấy danh sách attachment hiện có để tránh trùng lặp
global $wpdb;
$existing_attachments = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE 'products/%'"
);
foreach ($existing_attachments as $row) {
    $fn = basename($row->meta_value);
    $image_map[$fn] = (int) $row->post_id;
}

$webp_files = glob($images_dir . '/*.webp');
$new_attachments = 0;
foreach ($webp_files as $file) {
    $fn = basename($file);
    if (!isset($image_map[$fn])) {
        $attachment = [
            'post_mime_type' => 'image/webp',
            'post_title'     => pathinfo($fn, PATHINFO_FILENAME),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        $attach_id = wp_insert_attachment($attachment, 'products/' . $fn);
        if (!is_wp_error($attach_id) && $attach_id > 0) {
            update_post_meta($attach_id, '_wp_attached_file', 'products/' . $fn);
            $image_map[$fn] = $attach_id;
            $new_attachments++;
        }
    }
}
echo "   -> Tổng số ảnh WebP sẵn sàng: " . count($image_map) . " (Tạo mới: {$new_attachments})\n";

// 3. Đọc CSV và Import sản phẩm
$csv_file = __DIR__ . '/../data/woocommerce_500_skus.csv';
if (!file_exists($csv_file)) {
    echo "[ERROR] File CSV không tồn tại: {$csv_file}\n";
    exit(1);
}

echo "\n[3/4] Bắt đầu đọc và nạp sản phẩm từ woocommerce_500_skus.csv...\n";
$handle = fopen($csv_file, 'r');
if (!$handle) {
    echo "[ERROR] Không thể mở file CSV!\n";
    exit(1);
}

$header = fgetcsv($handle);
$header_map = array_flip($header);

$count_total = 0;
$count_created = 0;
$count_updated = 0;
$count_failed = 0;

// Cache categories để tăng tốc
$cat_cache = [];

while (($row = fgetcsv($handle)) !== false) {
    if (empty($row) || !isset($row[$header_map['SKU']])) {
        continue;
    }

    $sku = trim($row[$header_map['SKU']]);
    if (empty($sku)) {
        continue;
    }

    $count_total++;
    $name          = trim($row[$header_map['Name']]);
    $short_desc    = isset($header_map['Short description']) ? trim($row[$header_map['Short description']]) : '';
    $stock         = isset($header_map['Stock']) ? (int) $row[$header_map['Stock']] : 50;
    $regular_price = isset($header_map['Regular price']) ? trim($row[$header_map['Regular price']]) : '';
    $sale_price    = isset($header_map['Sale price']) ? trim($row[$header_map['Sale price']]) : '';
    $categories_str = isset($header_map['Categories']) ? trim($row[$header_map['Categories']]) : '';
    $tags_str      = isset($header_map['Tags']) ? trim($row[$header_map['Tags']]) : '';
    $image_fn      = isset($header_map['Images']) ? trim($row[$header_map['Images']]) : '';
    $routine_step  = isset($header_map['Taxonomy: routine_step']) ? trim($row[$header_map['Taxonomy: routine_step']]) : '';
    $skin_type     = isset($header_map['Taxonomy: skin_type']) ? trim($row[$header_map['Taxonomy: skin_type']]) : '';
    $skin_concern  = isset($header_map['Taxonomy: skin_concern']) ? trim($row[$header_map['Taxonomy: skin_concern']]) : '';
    $active_ing    = isset($header_map['Attribute 1 value(s)']) ? trim($row[$header_map['Attribute 1 value(s)']]) : '';
    $brand         = isset($header_map['Attribute 2 value(s)']) ? trim($row[$header_map['Attribute 2 value(s)']]) : '';
    $strong_active = isset($header_map['Meta: _kleer_strong_active']) ? (int) $row[$header_map['Meta: _kleer_strong_active']] : 0;
    $gentle_fb     = isset($header_map['Meta: _kleer_gentle_fallback']) ? (int) $row[$header_map['Meta: _kleer_gentle_fallback']] : 0;
    $source_url    = isset($header_map['Source URL']) ? trim($row[$header_map['Source URL']]) : '';

    // Tìm product hiện có theo SKU
    $product_id = wc_get_product_id_by_sku($sku);
    $is_new = false;
    if ($product_id) {
        $product = wc_get_product($product_id);
    } else {
        $product = new WC_Product_Simple();
        $is_new = true;
    }

    if (!$product) {
        $count_failed++;
        continue;
    }

    $product->set_sku($sku);
    $product->set_name($name);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_short_description($short_desc);
    $product->set_description($short_desc);
    $product->set_regular_price($regular_price);
    if (!empty($sale_price) && (float)$sale_price > 0 && (float)$sale_price < (float)$regular_price) {
        $product->set_sale_price($sale_price);
    } else {
        $product->set_sale_price('');
    }
    $product->set_manage_stock(true);
    $product->set_stock_quantity($stock > 0 ? $stock : 50);
    $product->set_stock_status('instock');

    // Xử lý danh mục (Categories)
    if (!empty($categories_str)) {
        $cat_ids = [];
        $parts = array_map('trim', explode('>', $categories_str));
        $parent_id = 0;
        foreach ($parts as $part) {
            $cache_key = $parent_id . '_' . $part;
            if (isset($cat_cache[$cache_key])) {
                $parent_id = $cat_cache[$cache_key];
            } else {
                $term = term_exists($part, 'product_cat', $parent_id);
                if ($term) {
                    $parent_id = (int) (is_array($term) ? $term['term_id'] : $term);
                } else {
                    $created = wp_insert_term($part, 'product_cat', ['parent' => $parent_id]);
                    if (!is_wp_error($created)) {
                        $parent_id = (int) $created['term_id'];
                    }
                }
                $cat_cache[$cache_key] = $parent_id;
            }
            if ($parent_id > 0) {
                $cat_ids[] = $parent_id;
            }
        }
        $product->set_category_ids($cat_ids);
    }

    // Xử lý ảnh đại diện
    if (!empty($image_fn) && isset($image_map[$image_fn])) {
        $product->set_image_id($image_map[$image_fn]);
    }

    // Lưu cơ bản để lấy product ID
    $saved_id = $product->save();
    if (!$saved_id) {
        $count_failed++;
        continue;
    }

    // Xử lý Tags
    if (!empty($tags_str)) {
        $tags = array_map('trim', explode(',', $tags_str));
        wp_set_post_terms($saved_id, $tags, 'product_tag', false);
    }

    // Xử lý Attributes và Taxonomies
    $wc_attributes = [];

    // Helper gán attribute
    $assign_attr = function($slug, $raw_values) use ($saved_id, &$wc_attributes, $existing_taxonomies) {
        if (empty($raw_values)) return;
        $tax_name = 'pa_' . $slug;
        $values = array_filter(array_map('trim', explode(',', $raw_values)));
        if (empty($values)) return;

        // Đảm bảo term tồn tại và gán vào post
        wp_set_object_terms($saved_id, $values, $tax_name, false);

        $attr_obj = new WC_Product_Attribute();
        if (isset($existing_taxonomies[$tax_name])) {
            $attr_obj->set_id($existing_taxonomies[$tax_name]);
        }
        $attr_obj->set_name($tax_name);
        $attr_obj->set_options($values);
        $attr_obj->set_position(count($wc_attributes));
        $attr_obj->set_visible(true);
        $attr_obj->set_variation(false);
        $wc_attributes[$tax_name] = $attr_obj;
    };

    $assign_attr('skin_type', $skin_type);
    $assign_attr('skin_concern', $skin_concern);
    $assign_attr('routine_step', $routine_step);
    $assign_attr('brand', $brand);
    $assign_attr('active_ingredient', $active_ing);

    // Cập nhật attributes cho product
    $product = wc_get_product($saved_id);
    if (!empty($wc_attributes)) {
        $product->set_attributes($wc_attributes);
    }

    // Cập nhật Custom Meta
    $product->update_meta_data('_kleer_strong_active', $strong_active);
    $product->update_meta_data('_kleer_gentle_fallback', $gentle_fb);
    if (!empty($source_url)) {
        $product->update_meta_data('_source_url', $source_url);
    }
    $product->save();

    if ($is_new) {
        $count_created++;
    } else {
        $count_updated++;
    }

    if ($count_total % 50 === 0) {
        echo "   -> Đã xử lý {$count_total} SKU...\n";
        wp_cache_flush();
    }
}
fclose($handle);

echo "\n[4/4] Tối ưu và cập nhật các bảng chỉ mục WooCommerce...\n";
wc_update_product_lookup_tables();
wc_delete_product_transients();
delete_transient('wc_products_onsale');

echo "\n========================================\n";
echo "           KẾT QUẢ IMPORT               \n";
echo "========================================\n";
echo "Tổng số dòng SKU trong CSV : {$count_total}\n";
echo "Sản phẩm tạo mới           : {$count_created}\n";
echo "Sản phẩm cập nhật          : {$count_updated}\n";
echo "Sản phẩm thất bại          : {$count_failed}\n";
echo "Tổng sản phẩm trong DB     : " . wp_count_posts('product')->publish . "\n";
echo "========================================\n";
