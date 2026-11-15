<?php
/**
 * Tối ưu hóa trải nghiệm Giỏ hàng (Cart) và Thanh toán (Checkout) — Task L03-G5-02
 *
 * - Đảm bảo trang Cart & Checkout render qua Classic Shortcode ([woocommerce_cart], [woocommerce_checkout]).
 * - Tinh giản >= 40% trường nhập liệu mặc định trên Checkout qua filter woocommerce_checkout_fields
 *   (từ 12 trường mặc định rút xuống 6 trường cốt lõi: Họ và tên, SĐT, Email, Tỉnh/Thành, Quận/Huyện - Phường/Xã, Địa chỉ cụ thể + Ghi chú tùy chọn).
 * - Cố định quốc gia VN, tự động đồng bộ dữ liệu họ tên/địa chỉ khi đặt hàng và kiểm tra định dạng SĐT Việt Nam.
 *
 * @package KleerTheme
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Đảm bảo trang Cart và Checkout luôn sử dụng Classic Shortcode thay vì WooCommerce Block.
 */
add_action('init', 'kleer_ensure_classic_cart_checkout_pages', 20);
function kleer_ensure_classic_cart_checkout_pages(): void
{
    if (!function_exists('wc_get_page_id')) {
        return;
    }

    if (get_option('kleer_classic_cart_checkout_v2') === 'yes') {
        return;
    }

    $pages = [
        'cart'     => '[woocommerce_cart]',
        'checkout' => '[woocommerce_checkout]',
    ];

    foreach ($pages as $page_slug => $shortcode) {
        $page_id = wc_get_page_id($page_slug);
        if ($page_id <= 0) {
            $page_obj = get_page_by_path($page_slug);
            $page_id  = $page_obj instanceof WP_Post ? (int) $page_obj->ID : 0;
        }

        if ($page_id > 0) {
            $post = get_post($page_id);
            if ($post instanceof WP_Post && strpos($post->post_content, $shortcode) === false) {
                wp_update_post([
                    'ID'           => $page_id,
                    'post_content' => $shortcode,
                ]);
            }
        }
    }

    // Đảm bảo kích hoạt plugin kleer-payments và bật mặc định COD / VietQR (BACS) / VNPay Sandbox để luồng L03 -> L04 thông suốt
    $active_plugins = (array) get_option('active_plugins', []);
    $payment_plugin = 'kleer-payments/kleer-payments.php';
    if (defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . $payment_plugin) && !in_array($payment_plugin, $active_plugins, true)) {
        $active_plugins[] = $payment_plugin;
        update_option('active_plugins', $active_plugins);
    }

    if (class_exists('\Kleer_Payments\Gateways')) {
        \Kleer_Payments\Gateways::apply_gateway_defaults();
    } else {
        update_option('woocommerce_cod_settings', [
            'enabled'            => 'yes',
            'title'              => 'Thanh toán khi nhận hàng (COD)',
            'description'        => 'Bạn thanh toán tiền mặt cho nhân viên giao hàng khi nhận sản phẩm.',
            'instructions'       => 'Khách trả tiền mặt khi nhận hàng.',
            'enable_for_methods' => [],
            'enable_for_virtual' => 'yes',
        ]);

        update_option('woocommerce_bacs_settings', [
            'enabled'      => 'yes',
            'title'        => 'Chuyển khoản ngân hàng (QR VietQR)',
            'description'  => 'Chuyển khoản qua số tài khoản của KLEER. Mã QR VietQR sẽ hiển thị ở trang xác nhận đơn hàng.',
            'instructions' => '',
        ]);
    }

    $vnpay_settings = (array) get_option('woocommerce_kleer_vnpay_settings', []);
    if (($vnpay_settings['enabled'] ?? '') !== 'yes') {
        $vnpay_settings['enabled']     = 'yes';
        $vnpay_settings['title']       = $vnpay_settings['title'] ?? 'Thanh toán qua VNPay (Sandbox)';
        $vnpay_settings['description'] = $vnpay_settings['description'] ?? 'Thanh toán bằng thẻ ATM/Visa/Master hoặc VNPAY-QR.';
        update_option('woocommerce_kleer_vnpay_settings', $vnpay_settings);
    }

    update_option('kleer_classic_cart_checkout_v2', 'yes', true);
}

/**
 * Fallback an toàn trên filter the_content: nếu nội dung trang Cart/Checkout vẫn chứa Block thì chuyển về Classic Shortcode.
 */
add_filter('the_content', 'kleer_force_classic_cart_checkout_content', 5);
function kleer_force_classic_cart_checkout_content(string $content): string
{
    if (function_exists('is_cart') && is_cart()) {
        if (strpos($content, 'wp:woocommerce/cart') !== false || strpos($content, 'wp-block-woocommerce-cart') !== false) {
            return '[woocommerce_cart]';
        }
    }

    if (function_exists('is_checkout') && is_checkout() && (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url())) {
        if (strpos($content, 'wp:woocommerce/checkout') !== false || strpos($content, 'wp-block-woocommerce-checkout') !== false) {
            return '[woocommerce_checkout]';
        }
    }

    return $content;
}

/**
 * 2. Cố định quốc gia mặc định là Việt Nam (VN) và tắt form địa chỉ giao hàng tách biệt dư thừa.
 */
add_filter('default_checkout_billing_country', 'kleer_default_checkout_country');
add_filter('default_checkout_shipping_country', 'kleer_default_checkout_country');
function kleer_default_checkout_country(): string
{
    return 'VN';
}

add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

/**
 * 3. Tinh gọn các trường nhập liệu trên trang Checkout (Giảm >= 40% số trường mặc định).
 *
 * Mặc định WooCommerce có 11 trường billing + 1 trường order_comments (tổng 12 trường).
 * Sau khi tinh giản: loại bỏ 6 trường dư thừa (billing_last_name, billing_company, billing_country,
 * billing_address_2, billing_postcode, shipping fields), chỉ giữ lại 5 trường bắt buộc cốt lõi + 1 ghi chú tùy chọn (giảm 50%).
 *
 * @param array<string, array<string, mixed>> $fields
 * @return array<string, array<string, mixed>>
 */
add_filter('woocommerce_checkout_fields', 'kleer_simplify_checkout_fields', 9999);
function kleer_simplify_checkout_fields(array $fields): array
{
    // Loại bỏ các trường không phù hợp với khách mua mỹ phẩm nội địa
    $removed_billing_fields = [
        'billing_last_name',
        'billing_company',
        'billing_country',
        'billing_address_2',
        'billing_postcode',
    ];

    foreach ($removed_billing_fields as $field_key) {
        if (isset($fields['billing'][$field_key])) {
            unset($fields['billing'][$field_key]);
        }
    }

    // Dọn sạch các trường shipping trùng lặp để khách chỉ điền 1 lần duy nhất
    if (isset($fields['shipping'])) {
        $fields['shipping'] = [];
    }

    // 1. Họ và tên người nhận (Gộp Họ + Tên vào 1 trường duy nhất)
    $fields['billing']['billing_first_name'] = [
        'type'         => 'text',
        'label'        => __('Họ và tên người nhận', 'kleer-theme'),
        'placeholder'  => __('VD: Nguyễn Văn A', 'kleer-theme'),
        'required'     => true,
        'class'        => ['form-row-wide', 'kleer-checkout__field', 'kleer-checkout__field--fullname'],
        'autocomplete' => 'name',
        'priority'     => 10,
    ];

    // 2. Số điện thoại
    $fields['billing']['billing_phone'] = [
        'type'              => 'tel',
        'label'             => __('Số điện thoại liên hệ', 'kleer-theme'),
        'placeholder'       => __('VD: 0901234567', 'kleer-theme'),
        'required'          => true,
        'class'             => ['form-row-first', 'kleer-checkout__field', 'kleer-checkout__field--phone'],
        'autocomplete'      => 'tel',
        'validate'          => ['phone'],
        'priority'          => 20,
        'custom_attributes' => [
            'inputmode' => 'tel',
            'maxlength' => '15',
        ],
    ];

    // 3. Email nhận thông tin đơn hàng & routine
    $fields['billing']['billing_email'] = [
        'type'         => 'email',
        'label'        => __('Địa chỉ Email', 'kleer-theme'),
        'placeholder'  => __('VD: ban@gmail.com', 'kleer-theme'),
        'required'     => true,
        'class'        => ['form-row-last', 'kleer-checkout__field', 'kleer-checkout__field--email'],
        'autocomplete' => 'email',
        'validate'     => ['email'],
        'priority'     => 30,
    ];

    // 4. Tỉnh / Thành phố (34 Tỉnh/Thành phố sau sáp nhập)
    $fields['billing']['billing_state'] = [
        'type'         => 'text',
        'label'        => __('Tỉnh / Thành phố', 'kleer-theme'),
        'placeholder'  => __('Chọn nhanh bên trên hoặc nhập Tỉnh / Thành phố', 'kleer-theme'),
        'required'     => true,
        'class'        => ['form-row-first', 'kleer-checkout__field', 'kleer-checkout__field--state'],
        'autocomplete' => 'address-level1',
        'priority'     => 40,
    ];

    // 5. Phường / Xã / Đặc khu (Mô hình hành chính 2 cấp sau sáp nhập, bỏ cấp Quận/Huyện)
    $fields['billing']['billing_city'] = [
        'type'         => 'text',
        'label'        => __('Phường / Xã / Đặc khu', 'kleer-theme'),
        'placeholder'  => __('Chọn nhanh bên trên hoặc nhập Phường / Xã / Đặc khu', 'kleer-theme'),
        'required'     => true,
        'class'        => ['form-row-last', 'kleer-checkout__field', 'kleer-checkout__field--city'],
        'autocomplete' => 'address-level2',
        'priority'     => 50,
    ];

    // 6. Địa chỉ chi tiết (Số nhà, tên đường)
    $fields['billing']['billing_address_1'] = [
        'type'         => 'text',
        'label'        => __('Địa chỉ chi tiết (Số nhà, tên đường, tòa nhà)', 'kleer-theme'),
        'placeholder'  => __('VD: 123 Nguyễn Huệ, Phường Nha Trang, Tỉnh Khánh Hòa', 'kleer-theme'),
        'required'     => true,
        'class'        => ['form-row-wide', 'kleer-checkout__field', 'kleer-checkout__field--address'],
        'autocomplete' => 'street-address',
        'priority'     => 60,
    ];

    // Ghi chú đơn hàng (Tùy chọn)
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label']       = __('Ghi chú giao hàng (Tùy chọn)', 'kleer-theme');
        $fields['order']['order_comments']['placeholder'] = __('VD: Giao giờ hành chính, gọi trước khi giao 15 phút...', 'kleer-theme');
        $fields['order']['order_comments']['required']    = false;
        $fields['order']['order_comments']['class']       = ['form-row-wide', 'notes', 'kleer-checkout__field'];
        $fields['order']['order_comments']['custom_attributes'] = [
            'rows' => '2',
        ];
    }

    return $fields;
}

/**
 * 4. Render input ẩn billing_country=VN và khung chọn nhanh địa chỉ hành chính 2 cấp sau sáp nhập (Tỉnh/Thành phố -> Phường/Xã/Đặc khu).
 */
add_action('woocommerce_before_checkout_billing_form', 'kleer_render_checkout_quick_tools');
function kleer_render_checkout_quick_tools(): void
{
    ?>
    <input type="hidden" name="billing_country" id="billing_country" value="VN" />

    <!-- Bộ chọn nhanh Địa chỉ hành chính 2 cấp sau sáp nhập (34 Tỉnh/Thành phố - Phường/Xã/Đặc khu) -->
    <div class="kleer-address-picker" id="kleer-address-picker">
        <div class="kleer-address-picker__header">
            <span class="kleer-address-picker__title">📍 Chọn nhanh địa chỉ hành chính</span>
        </div>
        <div class="kleer-address-picker__grid">
            <div class="kleer-address-picker__col">
                <label for="kleer-select-province" class="kleer-address-picker__label">1. Tỉnh / Thành phố (34 Tỉnh/Thành)</label>
                <select id="kleer-select-province" class="kleer-address-picker__select">
                    <option value="">-- Chọn Tỉnh / Thành phố --</option>
                </select>
            </div>
            <div class="kleer-address-picker__col">
                <label for="kleer-select-ward" class="kleer-address-picker__label">2. Phường / Xã / Đặc khu</label>
                <select id="kleer-select-ward" class="kleer-address-picker__select" disabled>
                    <option value="">-- Chọn Phường / Xã / Đặc khu --</option>
                </select>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 5. Chuẩn hóa dữ liệu gửi lên khi đặt hàng (woocommerce_checkout_posted_data):
 *    - Cố định billing_country = VN.
 *    - Tách Họ và Tên hợp lý vào billing_first_name / billing_last_name nếu cần cho chuẩn hóa đơn WooCommerce.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
add_filter('woocommerce_checkout_posted_data', 'kleer_normalize_checkout_posted_data');
function kleer_normalize_checkout_posted_data(array $data): array
{
    $data['billing_country']  = 'VN';
    $data['shipping_country'] = 'VN';

    $full_name = isset($data['billing_first_name']) ? trim(sanitize_text_field((string) $data['billing_first_name'])) : '';
    if ($full_name !== '' && empty($data['billing_last_name'])) {
        $parts = preg_split('/\s+/u', $full_name);
        if (is_array($parts) && count($parts) > 1) {
            $last_name                  = (string) array_pop($parts);
            $first_name                 = implode(' ', $parts);
            $data['billing_first_name'] = $first_name;
            $data['billing_last_name']  = $last_name;
        } else {
            $data['billing_last_name'] = '';
        }
    }

    // Đồng bộ sang thông tin giao hàng để các cổng thanh toán (VNPay, VietQR, COD) luôn có đủ trường
    $sync_keys = ['first_name', 'last_name', 'address_1', 'city', 'state', 'phone'];
    foreach ($sync_keys as $key) {
        $billing_key  = 'billing_' . $key;
        $shipping_key = 'shipping_' . $key;
        if (!empty($data[$billing_key]) && empty($data[$shipping_key])) {
            $data[$shipping_key] = $data[$billing_key];
        }
    }

    return $data;
}

/**
 * 6. Kiểm tra hợp lệ Số điện thoại Việt Nam phía Server khi submit Checkout.
 *
 * @param array<string, mixed> $data
 * @param WP_Error             $errors
 */
add_action('woocommerce_after_checkout_validation', 'kleer_validate_vietnam_checkout_fields', 10, 2);
function kleer_validate_vietnam_checkout_fields(array $data, WP_Error $errors): void
{
    $raw_phone = isset($data['billing_phone']) ? (string) $data['billing_phone'] : '';
    $clean_phone = preg_replace('/[\s\.\-\(\)]+/', '', $raw_phone);

    if ($clean_phone !== '' && !preg_match('/^(0|\+?84)(3|5|7|8|9)[0-9]{8}$|^02[0-9]{9}$/', (string) $clean_phone)) {
        $errors->add(
            'billing_phone_invalid_vn',
            __('Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam gồm 10 chữ số (VD: 0901234567).', 'kleer-theme')
        );
    }
}

/**
 * 7. Thanh tiến trình 3 bước (Progress Stepper) đồng bộ trên trang Cart và Checkout.
 */
add_action('woocommerce_before_cart', 'kleer_render_cart_checkout_stepper', 5);
add_action('woocommerce_before_checkout_form', 'kleer_render_cart_checkout_stepper', 5);
function kleer_render_cart_checkout_stepper(): void
{
    $is_checkout_page = function_exists('is_checkout') && is_checkout();
    $cart_url         = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $checkout_url     = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    ?>
    <nav class="kleer-checkout-steps" aria-label="Tiến trình mua hàng">
        <a href="<?php echo esc_url($cart_url); ?>" class="kleer-checkout-steps__item <?php echo !$is_checkout_page ? 'is-active' : 'is-completed'; ?>">
            <span class="kleer-checkout-steps__num"><?php echo !$is_checkout_page ? '1' : '✓'; ?></span>
            <span class="kleer-checkout-steps__label">Giỏ hàng &amp; Routine</span>
        </a>
        <span class="kleer-checkout-steps__divider" aria-hidden="true"></span>
        <a href="<?php echo esc_url($checkout_url); ?>" class="kleer-checkout-steps__item <?php echo $is_checkout_page ? 'is-active' : ''; ?>">
            <span class="kleer-checkout-steps__num">2</span>
            <span class="kleer-checkout-steps__label">Thông tin &amp; Thanh toán</span>
        </a>
        <span class="kleer-checkout-steps__divider" aria-hidden="true"></span>
        <span class="kleer-checkout-steps__item">
            <span class="kleer-checkout-steps__num">3</span>
            <span class="kleer-checkout-steps__label">Hoàn tất đơn hàng</span>
        </span>
    </nav>
    <?php
}

/**
 * 8. Banner gợi ý trạng thái ưu đãi Combo Routine 15% trên trang Giỏ hàng (đọc trực tiếp từ giỏ hàng, không can thiệp CartComboService).
 */
add_action('woocommerce_before_cart_table', 'kleer_render_routine_combo_progress_banner', 10);
function kleer_render_routine_combo_progress_banner(): void
{
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }

    $distinct_steps = [];
    foreach (WC()->cart->get_cart() as $item) {
        $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
        if ($product_id <= 0) {
            continue;
        }
        $terms = get_the_terms($product_id, 'pa_routine_step');
        if (is_array($terms)) {
            foreach ($terms as $term) {
                $distinct_steps[$term->slug] = $term->name;
            }
        }
    }

    $step_count = count($distinct_steps);
    $shop_url   = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    ?>
    <div class="kleer-combo-banner <?php echo $step_count >= 3 ? 'kleer-combo-banner--unlocked' : 'kleer-combo-banner--locked'; ?>">
        <div class="kleer-combo-banner__content">
            <?php if ($step_count >= 3) : ?>
                <span class="kleer-combo-banner__badge">🎉 Đã mở khóa ưu đãi -15%</span>
                <p class="kleer-combo-banner__text">
                    Giỏ hàng của bạn đã đủ <strong><?php echo esc_html((string) $step_count); ?> bước chu trình dưỡng da</strong>
                    (<?php echo esc_html(implode(' • ', array_values($distinct_steps))); ?>).
                    Chiết khấu <strong>Giảm 15% Combo Routine Unisex</strong> đã được tự động áp dụng ở phần Tổng đơn!
                </p>
            <?php else : ?>
                <span class="kleer-combo-banner__badge">✨ Ưu đãi Routine Bundle -15%</span>
                <p class="kleer-combo-banner__text">
                    Bạn đang có <strong><?php echo esc_html((string) $step_count); ?>/3 bước dưỡng da</strong> trong giỏ.
                    Chọn thêm <strong><?php echo esc_html((string) max(1, 3 - $step_count)); ?> bước khác nhau</strong>
                    (Làm sạch / Cân bằng / Đặc trị / Dưỡng ẩm / Chống nắng) để được giảm ngay <strong>15%</strong>!
                </p>
            <?php endif; ?>
        </div>
        <?php if ($step_count < 3) : ?>
            <a href="<?php echo esc_url($shop_url); ?>" class="kleer-combo-banner__cta">Chọn thêm sản phẩm →</a>
        <?php endif; ?>
    </div>
    <?php
}
