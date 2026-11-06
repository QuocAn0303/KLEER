<?php
/**
 * Plugin Name: KLEER Product Importer CLI
 * Description: Script Import tự động 500+ SKU từ CSV vào WooCommerce sử dụng CRUD API.
 * Version: 1.0.0
 * Author: Plugin Dev 2 (KLEER Team)
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Bootstrap WordPress nếu chạy độc lập qua WP-CLI hoặc PHP CLI từ thư mục gốc
    $possible_paths = [
        dirname( __FILE__ ) . '/../../../wp-load.php',
        dirname( __FILE__ ) . '/../../wp-load.php',
        '/var/www/html/wp-load.php'
    ];
    $loaded = false;
    foreach ( $possible_paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once( $path );
            $loaded = true;
            break;
        }
    }
    if ( ! $loaded ) {
        die( "Không thể tìm thấy file wp-load.php để bootstrap WordPress.\n" );
    }
}

if ( ! class_exists( 'WC_Product_Simple' ) ) {
    die( "WooCommerce chưa được kích hoạt hoặc chưa sẵn sàng.\n" );
}

class Kleer_Product_Importer {
    private $csv_file;
    private $img_dir;
    private $log_file;
    private $batch_size;
    private $dry_run;

    public function __construct( $csv_file, $img_dir, $batch_size = 50, $dry_run = false ) {
        $this->csv_file   = $csv_file;
        $this->img_dir    = rtrim( $img_dir, '/' ) . '/';
        $this->log_file   = dirname( __FILE__ ) . '/logs/import.log';
        $this->batch_size = $batch_size;
        $this->dry_run    = $dry_run;

        if ( ! file_exists( dirname( $this->log_file ) ) ) {
            mkdir( dirname( $this->log_file ), 0755, true );
        }
    }

    private function log( $message ) {
        $timestamp = date( 'Y-m-d H:i:s' );
        $formatted = "[{$timestamp}] {$message}\n";
        echo $formatted;
        file_put_contents( $this->log_file, $formatted, FILE_APPEND );
    }

    public function run() {
        if ( ! file_exists( $this->csv_file ) ) {
            $this->log( "LỖI: Không tìm thấy file CSV tại: {$this->csv_file}" );
            return;
        }

        $this->log( "=== BẮT ĐẦU QUÁ TRÌNH IMPORT SẢN PHẨM KLEER ===" );
        if ( $this->dry_run ) {
            $this->log( ">>> ĐANG CHẠY Ở CHẾ ĐỘ DRY-RUN (Không thay đổi Database) <<<" );
        }

        $file = fopen( $this->csv_file, 'r' );
        $header = fgetcsv( $file ); // Đọc header

        // Xử lý BOM UTF-8 nếu có
        if ( isset( $header[0] ) ) {
            $header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
        }

        $row_count = 0;
        $success_count = 0;
        $error_count = 0;
        $batch_counter = 0;

        while ( ( $data = fgetcsv( $file ) ) !== false ) {
            if ( count( $data ) < count( $header ) ) {
                continue;
            }
            $row = array_combine( $header, $data );
            $row_count++;

            $sku   = trim( $row['SKU'] ?? '' );
            $name  = trim( $row['Name'] ?? '' );
            $reg_p = floatval( $row['Regular price'] ?? 0 );
            $sale_p = ! empty( $row['Sale price'] ) ? floatval( $row['Sale price'] ) : '';

            // 1. Kiểm tra tính hợp lệ cơ bản
            if ( empty( $sku ) || empty( $name ) || $reg_p <= 0 ) {
                $this->log( "LỖI [Dòng {$row_count}]: Sản phẩm thiếu thông tin bắt buộc hoặc giá không hợp lệ (SKU: {$sku}, Giá: {$reg_p}). Bỏ qua." );
                $error_count++;
                continue;
            }

            // 2. Kiểm tra SKU đã tồn tại (Hỗ trợ Resume)
            $existing_id = wc_get_product_id_by_sku( $sku );
            if ( $existing_id ) {
                $this->log( "BỎ QUA [SKU: {$sku}]: Sản phẩm đã tồn tại trong hệ thống với ID {$existing_id}." );
                continue;
            }

            if ( $this->dry_run ) {
                $this->log( "DRY-RUN: Hợp lệ sản phẩm [SKU: {$sku}] - {$name}" );
                $success_count++;
                continue;
            }

            // 3. Tạo sản phẩm WooCommerce CRUD API
            try {
                $product = new WC_Product_Simple();
                $product->set_name( $name );
                $product->set_sku( $sku );
                $product->set_status( 'publish' );
                $product->set_catalog_visibility( 'visible' );
                $product->set_regular_price( $reg_p );
                
                if ( $sale_p !== '' && $sale_p < $reg_p ) {
                    $product->set_sale_price( $sale_p );
                }

                $product->set_short_description( $row['Short description'] ?? '' );
                $product->set_description( $row['Description'] ?? '' );
                $product->set_manage_stock( true );
                $product->set_stock_quantity( 50 );
                $product->set_stock_status( 'instock' );

                // 4. Gán Custom Taxonomies
                $this->assign_taxonomies( $product, $row );

                // 5. Lưu sản phẩm trước để lấy ID
                $product_id = $product->save();

                if ( ! $product_id ) {
                    throw new Exception( "Không thể lưu sản phẩm vào database." );
                }

                // 6. Xử lý ảnh WebP
                $this->process_webp_image( $product_id, $sku, $row );

                $this->log( "THÀNH CÔNG: Đã tạo sản phẩm ID {$product_id} [SKU: {$sku}]" );
                $success_count++;
                $batch_counter++;

                // Quản lý bộ nhớ theo Batch
                if ( $batch_counter >= $this->batch_size ) {
                    $batch_counter = 0;
                    if ( function_exists( 'gc_collect_cycles' ) ) {
                        gc_collect_cycles();
                    }
                }

            } catch ( Exception $e ) {
                $this->log( "LỖI [SKU: {$sku}]: " . $e->getMessage() );
                $error_count++;
            }
        }

        fclose( $file );
        $this->log( "=== KẾT THÚC IMPORT ===" );
        $this->log( "Tổng số xử lý: {$row_count} | Thành công: {$success_count} | Lỗi: {$error_count}" );
    }

    private function assign_taxonomies( $product, $row ) {
        $product_id = $product->get_id();

        // routine_step (Đơn giá trị)
        if ( ! empty( $row['Taxonomy: routine_step'] ) ) {
            wp_set_object_terms( $product_id, sanitize_text_field( $row['Taxonomy: routine_step'] ), 'routine_step', false );
        }

        // skin_type (Đa giá trị ngăn cách bởi dấu phẩy)
        if ( ! empty( $row['Taxonomy: skin_type'] ) ) {
            $types = array_map( 'trim', explode( ',', $row['Taxonomy: skin_type'] ) );
            wp_set_object_terms( $product_id, $types, 'skin_type', false );
        }

        // skin_concern
        if ( ! empty( $row['Taxonomy: skin_concern'] ) ) {
            $concerns = array_map( 'trim', explode( ',', $row['Taxonomy: skin_concern'] ) );
            wp_set_object_terms( $product_id, $concerns, 'skin_concern', false );
        }
    }

    private function process_webp_image( $product_id, $sku, $row ) {
        $img_path = $this->img_dir . $sku . '.webp';
        
        if ( ! file_exists( $img_path ) ) {
            // Thử tìm theo tên file nguồn nếu có trong CSV
            $this->log( "CẢNH BÁO [SKU: {$sku}]: Không tìm thấy file ảnh cục bộ tại {$img_path}." );
            return;
        }

        // Kiểm tra dung lượng < 120 KB
        $file_size = filesize( $img_path );
        if ( $file_size > 120 * 1024 ) {
            $this->log( "CẢNH BÁO [SKU: {$sku}]: Dung lượng ảnh vượt quá 120KB (" . round($file_size/1024, 2) . "KB)." );
            return;
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name( $sku . '.webp' );
        $target_file = $upload_dir['path'] . '/' . $filename;

        if ( copy( $img_path, $target_file ) ) {
            $filetype = wp_check_filetype( $filename, null );
            $attachment = [
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_text_field( $sku ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attach_id = wp_insert_attachment( $attachment, $target_file, $product_id );
            if ( ! is_wp_error( $attach_id ) ) {
                $attach_data = wp_generate_attachment_metadata( $attach_id, $target_file );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                set_post_thumbnail( $product_id, $attach_id );
            }
        }
    }
}

// Thực thi qua WP-CLI nếu được gọi từ command line
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'kleer import', function( $args, $assoc_args ) {
        $csv = $assoc_args['csv'] ?? WP_PLUGIN_DIR . '/kleer-plugin/woocommerce_500_skus.csv';
        $img = $assoc_args['img'] ?? WP_CONTENT_DIR . '/uploads/kleer-webp';
        $batch = intval( $assoc_args['batch'] ?? 50 );
        $dry_run = isset( $assoc_args['dry-run'] );

        $importer = new Kleer_Product_Importer( $csv, $img, $batch, $dry_run );
        $importer->run();
    } );
}