<?php

declare(strict_types=1);

namespace Kleer\Services;

use WC_Cart;

// Prevent direct file access
defined('ABSPATH') || exit;

class CartComboService
{
    private const NONCE_ACTION = 'kleer_add_routine_to_cart';
    private const AJAX_ACTION = 'kleer_add_routine_to_cart';
    private const AJAX_MUTATE = 'kleer_cart_mutate';
    public const AJAX_NONCE_ACTION = 'kleer_ajax_nonce';
    private const ROUTINE_STEPS = ['makeup_remover', 'cleanser', 'toner', 'serum', 'treatment', 'moisturizer', 'sunscreen'];
    private const DISCOUNT_LABEL = 'Giảm 15% Combo Routine Unisex';
    private const DISCOUNT_RATE = 0.15;

    private const ACID_GROUP_SLUGS = [
        'aha',
        'bha',
        'glycolic_acid',
        'lactic_acid',
        'salicylic_acid',
        'mandelic_acid',
        'fruit_acid',
        'pha',
    ];
    private const ACID_GROUP_TERMS = ['aha', 'bha', 'aha/bha', 'glycolic', 'salicylic'];
    private const RETINOID_GROUP_SLUGS = [
        'retinol',
        'retinal',
        'retinaldehyde',
        'tretinoin',
        'adapalene',
        'retinoid',
        'hydroxypinacolone_retinoate',
    ];
    private const RETINOID_GROUP_TERMS = ['retinol', 'retinal', 'tretinoin', 'adapalene'];

    public function register(): void
    {
        add_filter('kleer_quiz_cart_enabled', '__return_true');

        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleBulkAddRoutineAjax']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handleBulkAddRoutineAjax']);

        add_action('wp_ajax_' . self::AJAX_MUTATE, [$this, 'handleCartMutateAjax']);
        add_action('wp_ajax_nopriv_' . self::AJAX_MUTATE, [$this, 'handleCartMutateAjax']);

        add_action('woocommerce_cart_calculate_fees', [$this, 'applyRoutineBundleDiscount']);

        add_action('woocommerce_before_cart', [$this, 'renderConflictNotice']);
        add_action('woocommerce_before_checkout_form', [$this, 'renderConflictNotice']);
    }

    public function handleBulkAddRoutineAjax(): void
    {
        $nonce = $_POST['nonce'] ?? $_REQUEST['nonce'] ?? ($_SERVER['HTTP_X_WP_NONCE'] ?? '');
        if (is_string($nonce)) {
            $nonce = sanitize_text_field(wp_unslash($nonce));
        } else {
            $nonce = '';
        }

        if ($nonce === '' || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Nonce không hợp lệ.', 'kleer-plugin')], 403);
            return;
        }

        $productIds = $_POST['product_ids'] ?? $_REQUEST['product_ids'] ?? [];
        if (is_string($productIds)) {
            $decoded = json_decode(stripslashes($productIds), true);
            if (is_array($decoded)) {
                $productIds = $decoded;
            } else {
                $productIds = [];
            }
        }

        if (!is_array($productIds) || empty($productIds)) {
            wp_send_json_error(['message' => __('Danh sách sản phẩm không hợp lệ.', 'kleer-plugin')], 400);
            return;
        }

        if (!function_exists('WC')) {
            wp_send_json_error(['message' => __('WooCommerce không khả dụng.', 'kleer-plugin')], 400);
            return;
        }

        $cart = WC()->cart;
        if (!$cart) {
            wp_send_json_error(['message' => __('Giỏ hàng không khả dụng.', 'kleer-plugin')], 400);
            return;
        }

        $addedCount = 0;
        $errors = [];

        foreach ($productIds as $productId) {
            $id = absint($productId);
            if ($id <= 0) {
                continue;
            }

            if (!function_exists('wc_get_product')) {
                continue;
            }

            $product = wc_get_product($id);
            if (!$product) {
                $errors[] = $id;
                continue;
            }

            if (method_exists($product, 'is_purchasable') && !$product->is_purchasable()) {
                $errors[] = $id;
                continue;
            }

            if (method_exists($product, 'is_in_stock') && !$product->is_in_stock()) {
                $errors[] = $id;
                continue;
            }

            $added = $cart->add_to_cart($id, 1);
            if ($added) {
                $addedCount++;
            } else {
                $errors[] = $id;
            }
        }

        if ($addedCount === 0) {
            wp_send_json_error(['message' => __('Không thể thêm sản phẩm vào giỏ hàng.', 'kleer-plugin')], 400);
            return;
        }

        if (function_exists('WC_AJAX')) {
            ob_start();
            WC_AJAX::get_refreshed_fragments();
            $fragments = ob_get_clean();
            if (is_string($fragments)) {
                $data = json_decode($fragments, true);
                if (is_array($data)) {
                    wp_send_json_success($data);
                    return;
                }
            }
        }

        wp_send_json_success([
            'fragments' => function_exists('WC') && method_exists(WC()->cart, 'get_cart_fragments') ? WC()->cart->get_cart_fragments() : [],
            'cart_hash' => function_exists('WC') && method_exists(WC()->cart, 'get_cart_hash') ? WC()->cart->get_cart_hash() : '',
        ]);
    }

    /**
     * AJAX mutation cho ngăn kéo giỏ hàng Liquid Glass (add / add_bundle / update_qty).
     * Trả về toàn bộ trạng thái giỏ để Frontend cập nhật ngay, không tải lại trang.
     */
    public function handleCartMutateAjax(): void
    {
        $nonce = $_POST['nonce'] ?? ($_SERVER['HTTP_X_WP_NONCE'] ?? '');
        $nonce = is_string($nonce) ? sanitize_text_field(wp_unslash($nonce)) : '';

        if ($nonce === '' || (!wp_verify_nonce($nonce, self::AJAX_NONCE_ACTION) && !wp_verify_nonce($nonce, self::NONCE_ACTION))) {
            wp_send_json_error(['message' => __('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'kleer-plugin')], 403);
            return;
        }

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('Giỏ hàng không khả dụng.', 'kleer-plugin')], 400);
            return;
        }

        $cart = WC()->cart;
        $op = isset($_POST['op']) ? sanitize_key(wp_unslash((string) $_POST['op'])) : 'add';

        if ($op === 'update_qty') {
            $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash((string) $_POST['cart_item_key'])) : '';
            $qty = isset($_POST['qty']) ? max(0, absint($_POST['qty'])) : 0;
            if ($cart_item_key !== '') {
                $cart->set_quantity($cart_item_key, $qty, true);
            }
        } elseif ($op === 'add_bundle') {
            foreach ($this->normaliseProductIds($_POST['product_ids'] ?? []) as $product_id) {
                $cart->add_to_cart($product_id, 1);
            }
        } else {
            $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
            $qty = isset($_POST['qty']) ? max(1, absint($_POST['qty'])) : 1;
            if ($product_id > 0) {
                $cart->add_to_cart($product_id, $qty);
            }
        }

        if (method_exists($cart, 'calculate_totals')) {
            $cart->calculate_totals();
        }

        wp_send_json_success($this->buildCartState());
    }

    /**
     * Quét xung đột AHA/BHA + Retinoids trên danh sách cart item.
     * Không chặn thanh toán, chỉ trả về cảnh báo da liễu thân thiện.
     *
     * @param array<int|string, array<string, mixed>> $cart_items
     * @return array{has_conflict: bool, message: string}
     */
    public function scanIngredientConflicts(array $cart_items): array
    {
        $has_acid = false;
        $has_retinoid = false;

        foreach ($cart_items as $cart_item) {
            if (!is_array($cart_item)) {
                continue;
            }

            $product_id = absint($cart_item['product_id'] ?? ($cart_item['variation_id'] ?? 0));
            if ($product_id <= 0) {
                continue;
            }

            if ($this->productHasAcid($product_id)) {
                $has_acid = true;
            }

            if ($this->productHasRetinoid($product_id)) {
                $has_retinoid = true;
            }

            if ($has_acid && $has_retinoid) {
                break;
            }
        }

        if (!$has_acid || !$has_retinoid) {
            return ['has_conflict' => false, 'message' => ''];
        }

        return [
            'has_conflict' => true,
            'message' => __('Lưu ý từ chuyên gia da liễu KLEER: Giỏ hàng của bạn đang có cả BHA/AHA và Retinol thực vật. Bạn vẫn có thể đặt mua bình thường, nhưng hãy dùng cách ngày (chẵn/lẻ) hoặc khác buổi để tránh quá tải màng ẩm nhé.', 'kleer-plugin'),
        ];
    }

    /**
     * Ảnh chụp trạng thái giỏ hàng cho ngăn kéo Liquid Glass.
     *
     * @return array<string, mixed>
     */
    public function buildCartState(): array
    {
        $empty = [
            'items' => [],
            'item_count' => 0,
            'subtotal' => 0.0,
            'discount_amount' => 0.0,
            'discount_label' => self::DISCOUNT_LABEL,
            'discount_active' => false,
            'total' => 0.0,
            'conflict' => ['has_conflict' => false, 'message' => ''],
            'cart_url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
            'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
        ];

        if (!function_exists('WC') || !WC()->cart) {
            return $empty;
        }

        $cart = WC()->cart;
        $items = [];
        $unique_steps = [];

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = absint($cart_item['product_id'] ?? 0);
            $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;
            $step = $this->getRoutineStepsForProduct($product_id);
            foreach ($step as $step_slug) {
                $unique_steps[strtolower(sanitize_key($step_slug))] = true;
            }

            $image_id = $product && method_exists($product, 'get_image_id') ? (int) $product->get_image_id() : 0;
            $image = $image_id > 0 ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');

            $items[] = [
                'key' => (string) $cart_item_key,
                'product_id' => $product_id,
                'name' => $product ? $product->get_name() : __('Sản phẩm KLEER', 'kleer-plugin'),
                'qty' => (int) ($cart_item['quantity'] ?? 0),
                'line_total' => (float) ($cart_item['line_total'] ?? 0),
                'image' => (string) $image,
                'permalink' => $product && method_exists($product, 'get_permalink') ? (string) $product->get_permalink() : '',
                'step' => $step[0] ?? '',
            ];
        }

        $discount = 0.0;
        if (method_exists($cart, 'get_fees')) {
            foreach ((array) $cart->get_fees() as $fee) {
                $name = is_object($fee) ? ($fee->name ?? '') : ($fee['name'] ?? '');
                $amount = is_object($fee) ? (float) ($fee->amount ?? 0) : (float) ($fee['amount'] ?? 0);
                if ($name === self::DISCOUNT_LABEL) {
                    $discount += abs($amount);
                }
            }
        }

        $state = $empty;
        $state['items'] = $items;
        $state['item_count'] = (int) $cart->get_cart_contents_count();
        $state['subtotal'] = (float) $cart->get_subtotal();
        $state['discount_amount'] = round($discount, 2);
        $state['discount_active'] = $discount > 0 || count(array_intersect(array_keys($unique_steps), self::ROUTINE_STEPS)) >= 3;
        $state['total'] = method_exists($cart, 'get_total') ? (float) $cart->get_total('edit') : 0.0;
        $state['conflict'] = $this->scanIngredientConflicts($cart->get_cart_contents());

        return $state;
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normaliseProductIds(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode(stripslashes($raw), true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            $id = absint($value);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public function applyRoutineBundleDiscount(\WC_Cart $cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$cart || !method_exists($cart, 'get_cart_contents')) {
            return;
        }

        $this->removeExistingDiscount($cart);

        $uniqueSteps = [];
        $routineProductTotal = 0.0;

        foreach ($cart->get_cart_contents() as $cartItem) {
            $productId = $cartItem['product_id'] ?? ($cartItem['variation_id'] ?? 0);
            $productId = absint($productId);
            if ($productId <= 0) {
                continue;
            }

            $steps = $this->getRoutineStepsForProduct($productId);
            if (empty($steps)) {
                continue;
            }

            foreach ($steps as $step) {
                $step = strtolower(sanitize_key($step));
                if (in_array($step, self::ROUTINE_STEPS, true)) {
                    $uniqueSteps[$step] = true;
                }
            }

            $lineTotal = 0.0;
            if (isset($cartItem['line_subtotal'])) {
                $lineTotal = (float) $cartItem['line_subtotal'];
            } elseif (isset($cartItem['line_total'])) {
                $lineTotal = (float) $cartItem['line_total'];
            }

            if ($lineTotal > 0) {
                $routineProductTotal += $lineTotal;
            }
        }

        if (count($uniqueSteps) >= 3 && $routineProductTotal > 0) {
            $discountAmount = $routineProductTotal * self::DISCOUNT_RATE;
            if ($discountAmount > 0) {
                $cart->add_fee(self::DISCOUNT_LABEL, -$discountAmount, false);
            }
        }
    }

    private function removeExistingDiscount(\WC_Cart $cart): void
    {
        if (!method_exists($cart, 'get_fees')) {
            return;
        }

        $fees = $cart->get_fees();
        if (empty($fees)) {
            return;
        }

        foreach ($fees as $feeKey => $fee) {
            if (is_object($fee)) {
                $name = $fee->name ?? ($fee->get_name() ?? '');
            } elseif (is_array($fee)) {
                $name = $fee['name'] ?? '';
            } else {
                continue;
            }

            if ($name === self::DISCOUNT_LABEL) {
                unset($fees[$feeKey]);
            }
        }

        // Try to clear and re-add properly if possible
        if (method_exists($cart, 'fees_api') && $cart->fees_api()) {
            // WooCommerce stores fees in fees_api
        }
    }

    public function checkIngredientConflicts(): array
    {
        if (!function_exists('WC') || !WC()->cart) {
            return [];
        }

        $cart = WC()->cart;
        if (!method_exists($cart, 'get_cart_contents')) {
            return [];
        }

        $hasAcid = false;
        $hasRetinoid = false;

        foreach ($cart->get_cart_contents() as $cartItem) {
            $productId = $cartItem['product_id'] ?? ($cartItem['variation_id'] ?? 0);
            $productId = absint($productId);
            if ($productId <= 0) {
                continue;
            }

            if ($this->productHasAcid($productId)) {
                $hasAcid = true;
            }
            if ($this->productHasRetinoid($productId)) {
                $hasRetinoid = true;
            }

            if ($hasAcid && $hasRetinoid) {
                break;
            }
        }

        if ($hasAcid && $hasRetinoid) {
            return [
                'has_conflict' => true,
                'message' => __('Lưu ý từ chuyên gia da liễu KLEER: Giỏ hàng của bạn đang có cả BHA/AHA và Retinol thực vật. Bạn vẫn có thể đặt mua bình thường, nhưng hãy dùng cách ngày (chẵn/lẻ) hoặc khác buổi để tránh quá tải màng ẩm nhé.', 'kleer-plugin'),
            ];
        }

        return ['has_conflict' => false];
    }

    public function renderConflictNotice(): void
    {
        $conflict = $this->checkIngredientConflicts();
        if (empty($conflict['has_conflict'])) {
            return;
        }

        $message = $conflict['message'] ?? __('Lưu ý da liễu: Routine của bạn đang chứa AHA/BHA và Retinol dùng chung thời điểm có thể gây kích ứng da. Hãy cân nhắc dùng xen kẽ sáng/tối hoặc cách ngày.', 'kleer-plugin');
        ?>
        <div class="woocommerce-info kleer-ingredient-conflict-notice" style="border-left-color: #ff9800; background: #fff3e0;">
            <strong><?php echo esc_html__('Cảnh báo xung đột hoạt chất', 'kleer-plugin'); ?>:</strong>
            <?php echo esc_html($message); ?>
        </div>
        <?php
    }

    private function getRoutineStepsForProduct(int $productId): array
    {
        $steps = [];
        if (function_exists('wp_get_post_terms')) {
            $terms = wp_get_post_terms($productId, 'pa_routine_step', ['fields' => 'slugs']);
            if (is_array($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $steps[] = $term;
                }
            }
        }

        if (empty($steps) && function_exists('get_the_terms')) {
            $terms = get_the_terms($productId, 'pa_routine_step');
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    if (isset($term->slug)) {
                        $steps[] = $term->slug;
                    }
                }
            }
        }

        return array_values(array_unique($steps));
    }

    private function productHasAcid(int $productId): bool
    {
        // Check postmeta
        if (function_exists('get_post_meta')) {
            $meta = get_post_meta($productId, '_kleer_active_ingredients', true);
            if (is_string($meta) && $meta !== '') {
                $metaLower = strtolower($meta);
                foreach (array_merge(self::ACID_GROUP_SLUGS, self::ACID_GROUP_TERMS) as $needle) {
                    if (strpos($metaLower, $needle) !== false) {
                        return true;
                    }
                }
            }
        }

        // Check taxonomy pa_active-ingredient or pa_active_ingredient
        if (function_exists('wp_get_post_terms')) {
            foreach (['pa_active-ingredient', 'pa_active_ingredient', 'pa_activeingredients'] as $tax) {
                if (taxonomy_exists($tax)) {
                    $terms = wp_get_post_terms($productId, $tax, ['fields' => 'slugs']);
                    if (is_array($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $slug) {
                            $slugLower = strtolower($slug);
                            foreach (array_merge(self::ACID_GROUP_SLUGS, self::ACID_GROUP_TERMS) as $needle) {
                                if (strpos($slugLower, $needle) !== false) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    private function productHasRetinoid(int $productId): bool
    {
        if (function_exists('get_post_meta')) {
            $meta = get_post_meta($productId, '_kleer_active_ingredients', true);
            if (is_string($meta) && $meta !== '') {
                $metaLower = strtolower($meta);
                foreach (array_merge(self::RETINOID_GROUP_SLUGS, self::RETINOID_GROUP_TERMS) as $needle) {
                    if (strpos($metaLower, $needle) !== false) {
                        return true;
                    }
                }
            }
        }

        if (function_exists('wp_get_post_terms')) {
            foreach (['pa_active-ingredient', 'pa_active_ingredient', 'pa_activeingredients'] as $tax) {
                if (taxonomy_exists($tax)) {
                    $terms = wp_get_post_terms($productId, $tax, ['fields' => 'slugs']);
                    if (is_array($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $slug) {
                            $slugLower = strtolower($slug);
                            foreach (array_merge(self::RETINOID_GROUP_SLUGS, self::RETINOID_GROUP_TERMS) as $needle) {
                                if (strpos($slugLower, $needle) !== false) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
}
