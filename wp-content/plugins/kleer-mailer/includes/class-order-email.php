<?php
/**
 * Tu dong gui email hoa don HTML (responsive, co ma QR tra cuu) khi don hang duoc ghi nhan.
 *
 * Hook chinh: woocommerce_order_status_processing (thanh toan thanh cong / COD hop le).
 * Hook phu:   woocommerce_order_status_on_hold (don chuyen khoan cho doi soat) de khach
 *             van nhan xac nhan ngay khi dat hang.
 *
 * @package Kleer_Mailer
 */

namespace Kleer_Mailer;

defined( 'ABSPATH' ) || exit;

class Order_Email {

	private const CID = 'kleer-order-qr';

	/**
	 * So anh san pham toi da nhung vao mot email (giu email nhe, tranh bi Gmail cat o 102 KB).
	 */
	private const MAX_THUMBS = 6;

	/**
	 * Dung luong toi da cua mot anh thu nho (byte).
	 */
	private const MAX_THUMB_BYTES = 40000;

	/**
	 * Du lieu inline can gan vao email dang gui (do phpmailer_init xu ly).
	 *
	 * @var array{inline: array<int, array{data: string, cid: string, name: string, mime: string}>, alt: string}|null
	 */
	private static ?array $pending = null;

	public function register(): void {
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_processing' ), 20, 2 );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'on_hold' ), 20, 2 );
		add_action( 'phpmailer_init', array( $this, 'attach_inline' ), 20, 1 );

		// Tranh khach nhan 2 email trung (WooCommerce mac dinh + hoa don KLEER).
		if ( ! defined( 'KLEER_MAIL_KEEP_WC_EMAILS' ) || ! KLEER_MAIL_KEEP_WC_EMAILS ) {
			add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
			add_filter( 'woocommerce_email_enabled_customer_on_hold_order', '__return_false' );
		}
	}

	/**
	 * @param int            $order_id ID don.
	 * @param \WC_Order|null $order    Don hang (WooCommerce >= 5 truyen kem).
	 */
	public function on_processing( $order_id, $order = null ): void {
		$this->send( $order instanceof \WC_Order ? $order : wc_get_order( $order_id ), 'processing' );
	}

	/**
	 * @param int            $order_id ID don.
	 * @param \WC_Order|null $order    Don hang.
	 */
	public function on_hold( $order_id, $order = null ): void {
		$this->send( $order instanceof \WC_Order ? $order : wc_get_order( $order_id ), 'on-hold' );
	}

	/**
	 * Gui email cho mot don. Moi trang thai chi gui mot lan (meta danh dau).
	 *
	 * @return bool true neu da gui thanh cong.
	 */
	public function send( $order, string $stage ): bool {
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$meta_key = '_kleer_invoice_email_' . $stage;
		if ( '' !== (string) $order->get_meta( $meta_key ) ) {
			return false;
		}

		$to = $order->get_billing_email();
		if ( ! is_email( $to ) ) {
			$order->add_order_note( 'KLEER Mailer: khong gui email vi dia chi email khach khong hop le.' );

			return false;
		}

		$data = $this->build_data( $order, $stage );
		$html = $this->render( $data );
		$text = $this->plain_text( $data );

		self::$pending = array(
			'inline' => $data['inline'],
			'alt'    => $text,
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $data['subject'], $html, $headers );

		self::$pending = null;

		if ( $sent ) {
			$order->update_meta_data( $meta_key, gmdate( 'c' ) );
			$order->add_order_note( sprintf( 'KLEER Mailer: da gui email xac nhan don hang toi %s.', $to ) );
		} else {
			$order->add_order_note( 'KLEER Mailer: gui email xac nhan that bai (xem WooCommerce > Status > Logs, nguon kleer-mailer).' );
		}
		$order->save();

		return (bool) $sent;
	}

	/**
	 * Gan anh QR vao thu duoi dang inline (cid:) va them ban text thuan.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public function attach_inline( $phpmailer ): void {
		if ( null === self::$pending ) {
			return;
		}

		foreach ( self::$pending['inline'] as $img ) {
			$phpmailer->addStringEmbeddedImage( $img['data'], $img['cid'], $img['name'], 'base64', $img['mime'] );
		}

		// Ban text thuan: thu chi co HTML de bi cham diem spam hon thu multipart/alternative.
		$phpmailer->AltBody = self::$pending['alt'];
	}

	/**
	 * Gom du lieu hien thi.
	 *
	 * @return array<string, mixed>
	 */
	public function build_data( \WC_Order $order, string $stage ): array {
		$store   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$number  = $order->get_order_number();
		$lookup  = $order->get_checkout_order_received_url();
		$qr_png  = '';

		try {
			$qr_png = QR_Encoder::png( $lookup, 5 );
		} catch ( \Throwable $e ) {
			$qr_png = ''; // URL qua dai hoac thieu GD: gui thu khong co QR, van co duong link.
		}

		$inline = array();
		if ( '' !== $qr_png ) {
			$inline[] = array(
				'data' => $qr_png,
				'cid'  => self::CID,
				'name' => 'ma-qr-tra-cuu.png',
				'mime' => 'image/png',
			);
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$qty     = (int) $item->get_quantity();
			$total   = (float) $order->get_line_total( $item, true, true );

			$thumb_cid = '';
			if ( $product && count( $items ) < self::MAX_THUMBS ) {
				$thumb = $this->thumbnail( $product );
				if ( null !== $thumb ) {
					$thumb_cid = 'kleer-item-' . count( $items );
					$inline[]  = array(
						'data' => $thumb['data'],
						'cid'  => $thumb_cid,
						'name' => 'san-pham-' . count( $items ) . '.' . $thumb['ext'],
						'mime' => $thumb['mime'],
					);
				}
			}

			$items[] = array(
				'name'      => $item->get_name(),
				'sku'       => $product ? (string) $product->get_sku() : '',
				'variation' => $this->variation_text( $item ),
				'qty'       => $qty,
				'unit'      => $qty > 0 ? $total / $qty : $total,
				'total'     => $total,
				'thumb_cid' => $thumb_cid,
			);
		}

		$shipping_names = array();
		foreach ( $order->get_shipping_methods() as $method ) {
			$shipping_names[] = $method->get_name();
		}

		$on_hold = 'on-hold' === $stage;

		return array(
			'store'        => $store,
			'store_short'  => (string) strtok( $store, ' ' ),
			'stage'        => $stage,
			'subject'      => $on_hold
				? sprintf( '[%s] Đã nhận đơn hàng #%s - chờ thanh toán', $store, $number )
				: sprintf( '[%s] Xác nhận đơn hàng #%s', $store, $number ),
			'heading'      => $on_hold ? 'Chúng tôi đã nhận được đơn hàng của bạn' : 'Đơn hàng của bạn đã được xác nhận',
			'intro'        => $on_hold
				? 'Đơn hàng đang chờ chuyển khoản. Chúng tôi sẽ xử lý ngay khi xác nhận được khoản thanh toán.'
				: 'Cảm ơn bạn đã mua sắm. Chúng tôi đang chuẩn bị đơn hàng và sẽ báo khi giao cho đơn vị vận chuyển.',
			'number'       => $number,
			'date'         => wc_format_datetime( $order->get_date_created(), 'd/m/Y H:i' ),
			'customer'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'email'        => $order->get_billing_email(),
			'phone'        => $order->get_billing_phone(),
			'address'      => $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address(),
			'payment'      => $order->get_payment_method_title(),
			'items'        => $items,
			'subtotal'     => (float) $order->get_subtotal(),
			'shipping'     => (float) $order->get_shipping_total(),
			'discount'     => (float) $order->get_discount_total(),
			'total'        => (float) $order->get_total(),
			'lookup_url'   => $lookup,
			'qr_png'       => $qr_png,
			'qr_cid'       => self::CID,
			'inline'       => $inline,
			'status_label' => $on_hold ? 'Chờ thanh toán' : 'Đã xác nhận',
			'status_tone'  => $on_hold ? 'wait' : 'ok',
			'shipping_via' => implode( ', ', $shipping_names ),
			'note'         => (string) $order->get_customer_note(),
			'support'      => (string) get_option( 'admin_email' ),
			'bank'         => $this->bank_info( $order ),
		);
	}


	/**
	 * Doc anh thu nho san pham tu o dia (nhung inline, khong phu thuoc URL cong khai).
	 *
	 * @return array{data: string, ext: string, mime: string}|null
	 */
	private function thumbnail( \WC_Product $product ): ?array {
		$image_id = (int) $product->get_image_id();
		if ( $image_id <= 0 ) {
			return null;
		}

		$full = get_attached_file( $image_id );
		if ( ! is_string( $full ) || '' === $full ) {
			return null;
		}

		$path  = $full;
		$sized = image_get_intermediate_size( $image_id, 'thumbnail' );
		if ( is_array( $sized ) && ! empty( $sized['file'] ) ) {
			$path = trailingslashit( dirname( $full ) ) . $sized['file'];
		}

		if ( ! is_readable( $path ) || filesize( $path ) > self::MAX_THUMB_BYTES ) {
			return null;
		}

		$type = wp_check_filetype( $path );
		if ( ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/gif' ), true ) ) {
			return null;
		}

		$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $data ) {
			return null;
		}

		return array(
			'data' => $data,
			'ext'  => (string) $type['ext'],
			'mime' => (string) $type['type'],
		);
	}

	/**
	 * Thuoc tinh bien the, vd. "Dung tich: 50ml, Loai da: Da dau".
	 */
	private function variation_text( \WC_Order_Item $item ): string {
		$parts = array();
		foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
			$parts[] = wp_strip_all_tags( $meta->display_key ) . ': ' . wp_strip_all_tags( (string) $meta->display_value );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Thong tin chuyen khoan (chi cho don BACS khi da cau hinh tai khoan).
	 *
	 * @return array<string, string>|null
	 */
	private function bank_info( \WC_Order $order ): ?array {
		if ( 'bacs' !== $order->get_payment_method() || ! class_exists( '\Kleer_Payments\Bank_Account' ) ) {
			return null;
		}

		$bank = new \Kleer_Payments\Bank_Account();
		if ( ! $bank->is_configured() ) {
			return null;
		}

		$ctx     = $bank->qr_context();
		$content = trim( $ctx['transfer_prefix'] . ' ' . $order->get_order_number() );

		return array(
			'bank'    => $ctx['bank_name'],
			'account' => $ctx['account_number'],
			'holder'  => $ctx['account_holder'],
			'content' => $content,
		);
	}

	/**
	 * Render HTML tu template.
	 *
	 * @param array<string, mixed> $d Du lieu.
	 */
	public function render( array $d ): string {
		ob_start();
		include KLEER_MAILER_DIR . 'templates/invoice.php';

		return (string) ob_get_clean();
	}

	/**
	 * Ban text thuan cua email.
	 *
	 * @param array<string, mixed> $d Du lieu.
	 */
	public function plain_text( array $d ): string {
		$lines   = array();
		$lines[] = $d['heading'];
		$lines[] = '';
		$lines[] = 'Mã đơn hàng: #' . $d['number'] . ' (' . $d['date'] . ')';
		$lines[] = 'Thanh toán: ' . $d['payment'];
		$lines[] = '';
		foreach ( $d['items'] as $it ) {
			$label = $it['name'] . ( '' !== $it['variation'] ? ' (' . $it['variation'] . ')' : '' ) . ( '' !== $it['sku'] ? ' [' . $it['sku'] . ']' : '' );
			$lines[] = sprintf( '- %s x %d: %s', $label, $it['qty'], self::money( $it['total'] ) );
		}
		$lines[] = '';
		$lines[] = 'Tạm tính: ' . self::money( $d['subtotal'] );
		if ( $d['shipping'] > 0 ) {
			$lines[] = 'Vận chuyển: ' . self::money( $d['shipping'] );
		}
		if ( $d['discount'] > 0 ) {
			$lines[] = 'Giảm giá: -' . self::money( $d['discount'] );
		}
		$lines[] = 'Tổng cộng: ' . self::money( $d['total'] );
		if ( ! empty( $d['bank'] ) ) {
			$lines[] = '';
			$lines[] = 'Chuyển khoản: ' . $d['bank']['bank'] . ' - STK ' . $d['bank']['account'] . ' - ' . $d['bank']['holder'];
			$lines[] = 'Nội dung chuyển khoản: ' . $d['bank']['content'];
		}
		$lines[] = '';
		$lines[] = 'Tra cứu đơn hàng: ' . $d['lookup_url'];

		return implode( "\n", $lines );
	}

	/**
	 * Dinh dang tien VND.
	 */
	public static function money( float $amount ): string {
		return number_format( round( $amount ), 0, ',', '.' ) . ' ₫';
	}
}
