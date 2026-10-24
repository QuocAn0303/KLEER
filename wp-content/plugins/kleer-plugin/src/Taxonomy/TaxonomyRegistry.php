<?php

declare(strict_types=1);

namespace Kleer\Taxonomy;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Idempotent Custom Taxonomies and WooCommerce Attributes Registration.
 *
 * Layer: Domain / Taxonomy Infrastructure
 * Responsibility: Đăng ký chuẩn 3 Custom Taxonomies/WooCommerce Product Attributes:
 *                 - pa_skin_type (Loại da)
 *                 - pa_skin_concern (Vấn đề da)
 *                 - pa_routine_step (Bước Routine)
 * Ràng buộc:
 *   - Idempotent: kiểm tra taxonomy_exists() trước khi đăng ký.
 *   - Không tạo song song skin_type và pa_skin_type.
 *   - Không fatal nếu WooCommerce chưa kích hoạt; báo warning có kiểm soát.
 *   - Cung cấp dry-run schema mapping phục vụ migration & test.
 */
class TaxonomyRegistry
{
    public const TAXONOMY_SKIN_TYPE = 'pa_skin_type';
    public const TAXONOMY_SKIN_CONCERN = 'pa_skin_concern';
    public const TAXONOMY_ROUTINE_STEP = 'pa_routine_step';

    /**
     * @var list<string>
     */
    private const REGISTERED_TAXONOMIES = [
        self::TAXONOMY_SKIN_TYPE,
        self::TAXONOMY_SKIN_CONCERN,
        self::TAXONOMY_ROUTINE_STEP,
    ];

    /**
     * Đăng ký hook khởi tạo taxonomy vào WordPress lifecycle.
     */
    public function register(): void
    {
        if (function_exists('add_action')) {
            // Hook vào init với priority 10 để chạy sau khi WooCommerce đăng ký post_type product
            add_action('init', [$this, 'registerTaxonomies'], 10);
            add_action('admin_notices', [$this, 'checkWooCommerceNotice']);
        }
    }

    /**
     * Thực hiện đăng ký các taxonomy cho post type 'product'.
     */
    public function registerTaxonomies(): void
    {
        $objectType = 'product';

        $definitions = $this->getTaxonomyDefinitions();

        foreach ($definitions as $taxSlug => $config) {
            if (function_exists('taxonomy_exists') && taxonomy_exists($taxSlug)) {
                // Đã tồn tại (do WooCommerce attribute hoặc plugin khác), chỉ đảm bảo gắn vào 'product'
                if (function_exists('register_taxonomy_for_object_type')) {
                    register_taxonomy_for_object_type($taxSlug, $objectType);
                }
                continue;
            }

            if (function_exists('register_taxonomy')) {
                register_taxonomy($taxSlug, [$objectType], $config['args']);
            }
        }
    }

    /**
     * Thông báo quản trị nếu thiếu WooCommerce mà không làm sập trang.
     */
    public function checkWooCommerceNotice(): void
    {
        if (!class_exists('WooCommerce') && function_exists('current_user_can') && current_user_can('manage_options')) {
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . esc_html__('KLEER Plugin: WooCommerce chưa được kích hoạt. Các thuộc tính pa_skin_type, pa_skin_concern, pa_routine_step đang chạy ở chế độ fallback taxonomy độc lập.', 'kleer-plugin')
                . '</p></div>';
        }
    }

    /**
     * Định nghĩa cấu trúc 3 taxonomy và terms tiêu chuẩn.
     *
     * @return array<string, array{label: string, args: array<string, mixed>, default_terms: array<string, string>}>
     */
    public function getTaxonomyDefinitions(): array
    {
        return [
            self::TAXONOMY_SKIN_TYPE => [
                'label' => 'Loại da (Skin Type)',
                'args' => [
                    'hierarchical'      => false,
                    'labels'            => [
                        'name'          => 'Loại da',
                        'singular_name' => 'Loại da',
                        'search_items'  => 'Tìm loại da',
                        'all_items'     => 'Tất cả loại da',
                        'edit_item'     => 'Sửa loại da',
                        'update_item'   => 'Cập nhật loại da',
                        'add_new_item'  => 'Thêm mới loại da',
                        'menu_name'     => 'Loại da',
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_rest'      => true,
                    'query_var'         => true,
                    'rewrite'           => ['slug' => 'skin-type'],
                ],
                'default_terms' => [
                    'oily'           => 'Da dầu',
                    'dry'            => 'Da khô',
                    'combination'    => 'Da hỗn hợp',
                    'normal'         => 'Da thường',
                    'sensitive'      => 'Da nhạy cảm',
                    'all_skin_types' => 'Mọi loại da',
                ],
            ],
            self::TAXONOMY_SKIN_CONCERN => [
                'label' => 'Vấn đề da (Skin Concern)',
                'args' => [
                    'hierarchical'      => false,
                    'labels'            => [
                        'name'          => 'Vấn đề da',
                        'singular_name' => 'Vấn đề da',
                        'search_items'  => 'Tìm vấn đề da',
                        'all_items'     => 'Tất cả vấn đề da',
                        'edit_item'     => 'Sửa vấn đề da',
                        'update_item'   => 'Cập nhật vấn đề da',
                        'add_new_item'  => 'Thêm mới vấn đề da',
                        'menu_name'     => 'Vấn đề da',
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_rest'      => true,
                    'query_var'         => true,
                    'rewrite'           => ['slug' => 'skin-concern'],
                ],
                'default_terms' => [
                    'acne'       => 'Mụn',
                    'dark_spots' => 'Thâm / Nám',
                    'aging'      => 'Lão hóa',
                    'pores'      => 'Lỗ chân lông to',
                    'hydration'  => 'Thiếu ẩm / Mất nước',
                    'soothing'   => 'Làm dịu / Phục hồi',
                ],
            ],
            self::TAXONOMY_ROUTINE_STEP => [
                'label' => 'Bước Routine (Routine Step)',
                'args' => [
                    'hierarchical'      => false,
                    'labels'            => [
                        'name'          => 'Bước Routine',
                        'singular_name' => 'Bước Routine',
                        'search_items'  => 'Tìm bước Routine',
                        'all_items'     => 'Tất cả bước Routine',
                        'edit_item'     => 'Sửa bước Routine',
                        'update_item'   => 'Cập nhật bước Routine',
                        'add_new_item'  => 'Thêm mới bước Routine',
                        'menu_name'     => 'Bước Routine',
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_rest'      => true,
                    'query_var'         => true,
                    'rewrite'           => ['slug' => 'routine-step'],
                ],
                'default_terms' => [
                    'cleanser'       => 'Sữa rửa mặt',
                    'makeup_remover' => 'Tẩy trang',
                    'toner'          => 'Toner / Cân bằng',
                    'serum'          => 'Serum / Tinh chất đặc trị',
                    'moisturizer'    => 'Dưỡng ẩm',
                    'sunscreen'      => 'Kem chống nắng',
                ],
            ],
        ];
    }

    /**
     * Schema map cho dry-run kiểm tra cấu trúc taxonomy & terms mà không thay đổi database.
     *
     * @return array<string, mixed>
     */
    public function dryRunSchemaMap(): array
    {
        $map = [];
        foreach ($this->getTaxonomyDefinitions() as $slug => $def) {
            $map[$slug] = [
                'label'         => $def['label'],
                'object_type'   => 'product',
                'show_in_rest'  => $def['args']['show_in_rest'],
                'terms_count'   => count($def['default_terms']),
                'default_terms' => $def['default_terms'],
            ];
        }

        return [
            'status'     => 'dry_run_ready',
            'taxonomies' => $map,
            'summary'    => sprintf('Đã định nghĩa %d taxonomies với tổng cộng %d terms tiêu chuẩn.', count($map), array_sum(array_column($map, 'terms_count'))),
        ];
    }

    /**
     * Danh sách 3 slug taxonomy chuẩn.
     *
     * @return list<string>
     */
    public static function getTaxonomySlugs(): array
    {
        return self::REGISTERED_TAXONOMIES;
    }
}
