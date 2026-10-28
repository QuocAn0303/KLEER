<?php
/**
 * Tao don mau (co anh san pham, SKU, bien the, giam gia, van chuyen, chuyen khoan) va gui email hoa don
 * toi Mailpit qua API de xem/kiem tra tuong thich.
 *
 * Chay: docker exec kleer-php php wp-cli.phar --allow-root eval-file wp-content/plugins/kleer-mailer/tests/preview-invoice.php [stage]
 *   stage: processing (mac dinh) | on-hold
 * Sau do mo http://localhost:8025 . Khong gui email that, chi can Mailpit dang chay.
 */

defined( 'ABSPATH' ) || exit;

$stage = $args[0] ?? 'processing'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

$mk_product = static function ( string $name, string $sku, string $price, string $hex ): WC_Product_Simple {
	$existing = wc_get_product_id_by_sku( $sku );
	if ( $existing ) {
		return wc_get_product( $existing );
	}

	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_sku( $sku );
	$p->set_regular_price( $price );
	$p->set_status( 'publish' );
	$p->save();

	// Anh minh hoa 300x300 sinh bang GD (khong dung anh ban quyen).
	$im = imagecreatetruecolor( 300, 300 );
	sscanf( $hex, '%02x%02x%02x', $r, $g, $b );
	imagefill( $im, 0, 0, imagecolorallocate( $im, $r, $g, $b ) );
	imagefilledellipse( $im, 150, 170, 120, 190, imagecolorallocate( $im, 255, 255, 255 ) );
	ob_start();
	imagejpeg( $im, null, 80 );
	$jpg = (string) ob_get_clean();

	$up = wp_upload_bits( sanitize_title( $sku ) . '.jpg', null, $jpg );
	if ( empty( $up['error'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$att = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => $name, 'post_status' => 'inherit' ), $up['file'] );
		wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $up['file'] ) );
		$p->set_image_id( $att );
		$p->save();
	}

	return $p;
};

$a = $mk_product( 'Serum Niacinamide 10% Làm Sáng Da', 'KL-SRM-NIA10', '320000', 'c9c3b4' );
$b = $mk_product( 'Kem Chống Nắng Dịu Nhẹ SPF50+', 'KL-SUN-GEN50', '245000', 'b9c4b8' );

$order = wc_create_order();
$i1    = $order->add_product( $a, 1 );
$i2    = $order->add_product( $b, 2 );
foreach ( array( $i1, $i2 ) as $id ) {
	$it = $order->get_item( $id );
	if ( $id === $i1 ) {
		$it->add_meta_data( 'Dung tích', '30ml' );
	}
	$it->save();
}

$ship = new WC_Order_Item_Shipping();
$ship->set_method_title( 'Giao hàng tiêu chuẩn' );
$ship->set_total( 30000 );
$order->add_item( $ship );

$order->set_billing_first_name( 'Nguyễn Minh' );
$order->set_billing_last_name( 'Anh' );
$order->set_billing_email( 'khach@example.com' );
$order->set_billing_phone( '0901234567' );
$order->set_billing_address_1( '12 Nguyễn Huệ' );
$order->set_billing_city( 'Quận 1, TP. Hồ Chí Minh' );
$order->set_billing_country( 'VN' );
$order->set_shipping_first_name( 'Nguyễn Minh' );
$order->set_shipping_last_name( 'Anh' );
$order->set_shipping_address_1( '12 Nguyễn Huệ' );
$order->set_shipping_city( 'Quận 1, TP. Hồ Chí Minh' );
$order->set_shipping_country( 'VN' );
$order->set_customer_note( 'Giao giờ hành chính, gọi trước 15 phút.' );
$order->set_payment_method( 'on-hold' === $stage ? 'bacs' : 'cod' );
$order->set_payment_method_title( 'on-hold' === $stage ? 'Chuyển khoản ngân hàng (QR VietQR)' : 'Thanh toán khi nhận hàng (COD)' );
$order->calculate_totals();
$order->save();

// Gui bang dung luong that cua plugin (Order_Email::send), nhung ep SMTP ve Mailpit de khong gui thu that.
add_action(
	'phpmailer_init',
	static function ( $m ): void {
		$m->isSMTP();
		$m->Host        = 'mailpit';
		$m->Port        = 1025;
		$m->SMTPAuth    = false;
		$m->Username    = '';
		$m->Password    = '';
		$m->SMTPSecure  = '';
		$m->SMTPAutoTLS = false;
	},
	99
);

$mail = new Kleer_Mailer\Order_Email();
$sent = $mail->send( $order, $stage );

echo 'don #', $order->get_id(), ' | stage ', $stage, ' | gui ', $sent ? 'OK' : 'THAT BAI', "
";
