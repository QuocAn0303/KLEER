<?php

declare(strict_types=1);

namespace Kleer\Support;

use WC_Product;

// Prevent direct file access
defined('ABSPATH') || exit;

/**
 * Chuẩn hóa WC_Product thành cấu trúc dữ liệu đồng bộ cho cả PHP Template và REST/AJAX JSON.
 *
 * Layer: Support (Presentation DTO)
 * Boundary: Không ghi dữ liệu, không gọi HTTP. Chỉ đọc thuộc tính sản phẩm và taxonomy.
 */
final class ProductCardPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(WC_Product $product, ?string $user_skin_type = null): array
    {
        $product_id = $product->get_id();

        // 1. Bốn trục Taxonomy chuẩn 3NF của KLEER
        $step_terms    = self::termObjects($product_id, 'pa_routine_step');
        $step_names    = self::termNames($product_id, 'pa_routine_step');
        $skin_terms    = self::termObjects($product_id, 'pa_skin_type');
        $concern_names = self::termNames($product_id, 'pa_skin_concern');
        $inci_names    = self::termNames($product_id, 'pa_active_ingredient');

        $step_label = $step_names[0] ?? (string) (self::meta($product_id, '_kleer_routine_step') ?: 'Bước dưỡng cơ bản');
        $concern_label = $concern_names[0] ?? (string) (self::meta($product_id, '_kleer_skin_concern') ?: 'Phục hồi màng ẩm');

        $inci_badge = !empty($inci_names)
            ? implode(' + ', array_slice($inci_names, 0, 2))
            : (string) (self::meta($product_id, '_kleer_inci_highlight') ?: 'Chiết xuất thực vật lành tính');

        $skin_slugs = [];
        $skin_names = [];
        foreach ($skin_terms as $term) {
            $skin_slugs[] = (string) $term->slug;
            $skin_names[] = (string) $term->name;
        }

        if (empty($skin_slugs)) {
            $meta_skins = self::meta($product_id, '_kleer_skin_types');
            $skin_slugs = is_array($meta_skins)
                ? array_values(array_filter(array_map('strval', $meta_skins)))
                : [];
        }

        $skin_label = !empty($skin_names)
            ? implode(' · ', array_slice($skin_names, 0, 2))
            : 'Mọi loại da';

        $step_slugs = [];
        foreach ($step_terms as $term) {
            $step_slugs[] = (string) $term->slug;
        }

        $concern_slugs = [];
        foreach (self::termObjects($product_id, 'pa_skin_concern') as $term) {
            $concern_slugs[] = (string) $term->slug;
        }

        // 2. Nhóm xung đột hoạt chất cho CartComboService
        $inci_lower = mb_strtolower(implode(' ', $inci_names) . ' ' . $inci_badge . ' ' . $product->get_name(), 'UTF-8');
        $conflict_group = 'neutral';

        if (preg_match('/(bha|aha|salicylic|glycolic|lactic|vỏ liễu)/u', $inci_lower)) {
            $conflict_group = 'aha_bha';
        } elseif (preg_match('/(retinol|retinoid|tretinoin|adapalene|bakuchiol)/u', $inci_lower)) {
            $conflict_group = 'retinol';
        }

        // 3. Nhãn Bán chạy / Nổi bật cho kệ ngang số 1
        $total_sales = (int) $product->get_total_sales();
        $rating = (float) $product->get_average_rating();

        if ($total_sales >= 100) {
            $bestseller_tag = sprintf('BÁN CHẠY · %s+ ĐÃ BÁN', number_format_i18n($total_sales));
        } elseif ($rating >= 4.5) {
            $bestseller_tag = sprintf('ĐƯỢC YÊU THÍCH · %.1f★', $rating);
        } else {
            $bestseller_tag = 'CÔNG THỨC NỔI BẬT · ' . mb_strtoupper($concern_label, 'UTF-8');
        }

        // 4. Bảng màu Hai Tông chuẩn Apple Store
        $palette = self::resolve_apple_two_tone_palette($product_id, $step_label, $inci_lower);

        $price_num = (float) $product->get_price();
        $short_description = $product->get_short_description();
        $sensory = $short_description
            ? wp_strip_all_tags($short_description)
            : 'Kết cấu mỏng nhẹ, thẩm thấu nhanh và tôn trọng màng ẩm sinh học tự nhiên của làn da.';

        $image_url = self::imageUrl($product);
        $is_matched = $user_skin_type ? in_array($user_skin_type, $skin_slugs, true) : true;

        return [
            'id'             => $product_id,
            'sku'            => $product->get_sku() ?: 'KLR-' . $product_id,
            'name'           => $product->get_name(),
            'permalink'      => get_permalink($product_id),
            'step'           => $step_label,
            'bestsellerTag'  => $bestseller_tag,
            'skinTypes'      => $skin_slugs,
            'skinLabel'      => $skin_label,
            'concern'        => $concern_label,
            'stepSlug'       => $step_slugs[0] ?? '',
            'concernSlugs'   => $concern_slugs,
            'inciGroup'      => $conflict_group,
            'inciBadge'      => $inci_badge,
            'sensory'        => $sensory,
            'price'          => $price_num,
            'priceFormatted' => $product->get_price() !== '' ? number_format($price_num, 0, ',', '.') . 'đ' : 'Liên hệ',
            'cardTopBg'      => $palette['top'],
            'cardBottomBg'   => $palette['bottom'],
            'cardAccent'     => $palette['accent'],
            'image'          => $image_url,
            'imageAlt'       => $product->get_name(),
            'isMatched'      => $is_matched,
        ];
    }

    /**
     * @return array{top: string, bottom: string, accent: string}
     */
    public static function resolve_apple_two_tone_palette(int $product_id, string $step, string $inci_lower): array
    {
        $custom = self::meta($product_id, '_kleer_card_palette');
        if (is_array($custom) && isset($custom['top'], $custom['bottom'], $custom['accent'])) {
            return [
                'top'    => (string) $custom['top'],
                'bottom' => (string) $custom['bottom'],
                'accent' => (string) $custom['accent'],
            ];
        }

        if (strpos($inci_lower, 'retinol') !== false || strpos($inci_lower, 'đêm') !== false) {
            return ['top' => '#dedbf0', 'bottom' => '#534c76', 'accent' => '#2b224f'];
        }

        if (strpos($inci_lower, 'vitamin c') !== false || strpos($inci_lower, 'kakadu') !== false) {
            return ['top' => '#f4e7ba', 'bottom' => '#d9a653', 'accent' => '#5a3d08'];
        }

        if (strpos($inci_lower, 'bha') !== false || strpos($inci_lower, 'aha') !== false || strpos($inci_lower, 'liễu') !== false) {
            return ['top' => '#e9d7bf', 'bottom' => '#cc8b55', 'accent' => '#5c3312'];
        }

        if (mb_stripos($step, 'Bước 1') !== false || strpos($inci_lower, 'trà xanh') !== false) {
            return ['top' => '#d4e6d8', 'bottom' => '#73957c', 'accent' => '#1c4627'];
        }

        if (mb_stripos($step, 'Bước 2') !== false || strpos($inci_lower, 'nha đam') !== false) {
            return ['top' => '#ccdfdd', 'bottom' => '#5b848a', 'accent' => '#18444a'];
        }

        return ['top' => '#f0d8d6', 'bottom' => '#ac6a6c', 'accent' => '#592327'];
    }

    /**
     * @return list<\WP_Term>
     */
    private static function termObjects(int $product_id, string $taxonomy): array
    {
        if (!function_exists('wc_get_product_terms') || !taxonomy_exists($taxonomy)) {
            return [];
        }

        $terms = wc_get_product_terms($product_id, $taxonomy, ['fields' => 'all']);

        return is_array($terms) ? array_values(array_filter($terms, 'is_object')) : [];
    }

    /**
     * @return list<string>
     */
    private static function termNames(int $product_id, string $taxonomy): array
    {
        if (!function_exists('wc_get_product_terms') || !taxonomy_exists($taxonomy)) {
            return [];
        }

        $terms = wc_get_product_terms($product_id, $taxonomy, ['fields' => 'names']);

        return is_array($terms) ? array_values(array_map('strval', $terms)) : [];
    }

    private static function meta(int $product_id, string $key): mixed
    {
        return function_exists('get_post_meta') ? get_post_meta($product_id, $key, true) : '';
    }

    private static function imageUrl(WC_Product $product): string
    {
        $image_id = (int) $product->get_image_id();

        if ($image_id > 0 && function_exists('wp_get_attachment_image_url')) {
            $url = wp_get_attachment_image_url($image_id, 'large');
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        $external = self::meta($product->get_id(), '_kleer_image_url') ?: self::meta($product->get_id(), 'image_url');
        if (is_string($external) && $external !== '' && esc_url_raw($external, ['http', 'https']) !== '') {
            return esc_url_raw($external, ['http', 'https']);
        }
        return function_exists('wc_placeholder_img_src') ? (string) wc_placeholder_img_src('large') : '';
    }
}
