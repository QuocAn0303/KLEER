<?php
/**
 * Hien thi QR VietQR tren trang cam on don.
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Ve khoi QR chuyen khoan cho don hang ma khach chon BACS.
 *
 * Chi ve o trang Thank You. COD khong can QR vi khach tra tien mat.
 */
class ThankYou_QR {

	/**
	 * ID cua co che thanh toan chuyen khoan ngan hang cua WooCommerce.
	 */
	public const GATEWAY_BACS = 'bacs';

	/**
	 * Dich vu cau hinh tai khoan ngan hang.
	 *
	 * @var Bank_Account
	 */
	private Bank_Account $bank_account;

	/**
	 * Bo sinh payload QR.
	 *
	 * @var QR_Payload
	 */
	private QR_Payload $qr_payload;

	public function __construct() {
		$this->bank_account = new Bank_Account();
		$this->qr_payload   = new QR_Payload();
	}

	/**
	 * Hook vao hai kieu cam on don cua WooCommerce.
	 *
	 * WooCommerce Blocks (mac dinh tu 8.3) KHONG chay hook
	 * `woocommerce_thankyou`; no render bang block
	 * `woocommerce/order-confirmation`, nen phai cham vao `render_block`.
	 * Checkout cu (shortcode) van dung hook cu. Ho tro ca hai de khong
	 * phu thuoc vao cach shop cau hinh checkout.
	 */
	public function register(): void {
		add_action( 'woocommerce_thankyou', array( $this, 'render' ), 10, 1 );
		add_filter( 'render_block', array( $this, 'render_in_block' ), 10, 2 );
	}

	/**
	 * ID don da ve QR, tranh in trung khi ca hai hook cung chay.
	 *
	 * @var int
	 */
	private static $rendered_order = 0;

	/**
	 * Diem vao cho block checkout (WooCommerce Blocks).
	 *
	 * @param string               $html  HTML cua block sau khi render.
	 * @param array<string, mixed> $block Du lieu block.
	 * @return string
	 */
	public function render_in_block( $html, $block ) {
		$name = is_array( $block ) && isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		if ( 'woocommerce/order-confirmation' !== $name ) {
			return $html;
		}

		$order_id = (int) get_query_var( 'order-received' );

		if ( $order_id <= 0 ) {
			return $html;
		}

		ob_start();
		$this->render( $order_id );

		return $html . (string) ob_get_clean();
	}

	/**
	 * Chuoi nap thanh phan QR.
	 *
	 * Chi nap khi that su co ve QR, tranh tai mot bien cho moi don COD.
	 */
	private function enqueue_qr_library(): void {
		if ( wp_script_is( 'kleer-qr-renderer', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_script(
			'kleer-qrcode',
			KLEER_PAYMENTS_URL . 'assets/qrcode.min.js',
			array(),
			KLEER_PAYMENTS_VERSION,
			true
		);

		wp_enqueue_script(
			'kleer-qr-renderer',
			KLEER_PAYMENTS_URL . 'assets/kleer-qr.js',
			array( 'kleer-qrcode' ),
			KLEER_PAYMENTS_VERSION,
			true
		);
	}

	/**
	 * So tien can chuyen, lam tron ve don vi VND.
	 *
	 * QR khong co phan thap phan nen phai bo moi phan thuc.
	 */
	public function amount_in_vnd( WC_Order $order ): int {
		return (int) round( (float) $order->get_total() );
	}

	/**
	 * Kiem tra don co du dieu kien de ve QR khong.
	 *
	 * Chi don chuyen khoan ngan hang moi can QR; don COD khach tra tien mat.
	 * Phai kiem tra ca tai khoan ngan hang da duoc cau hinh chua.
	 */
	private function is_eligible_order( WC_Order $order ): bool {
		if ( self::GATEWAY_BACS !== $order->get_payment_method() ) {
			return false;
		}

		return $this->bank_account->is_configured();
	}

	/**
	 * Gom du lieu can ve: payload QR + so tien + thong tin ngan hang.
	 *
	 * Tu chay kiem tra dieu kien nen moi noi co the goi deu an toan:
	 * don COD, don chua co tien, hay tai khoan chua cau hinh deu tra null.
	 *
	 * @return array<string, mixed>|null
	 */
	public function build_display_data( WC_Order $order ): ?array {
		if ( ! $this->is_eligible_order( $order ) ) {
			return null;
		}

		$amount = $this->amount_in_vnd( $order );

		if ( $amount <= 0 ) {
			return null;
		}

		$context = $this->bank_account->qr_context();

		try {
			$payload = $this->qr_payload->build(
				$amount,
				$order->get_order_number(),
				$context['bank_bin'],
				$context['bin_location'],
				$context['merchant_name'],
				$context['city'],
				$context['account_type']
			);
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}

		$content = $this->qr_payload->build_transfer_content(
			$context['transfer_prefix'],
			$order->get_order_number()
		);

		return array(
			'order'      => $order,
			'amount'     => $amount,
			'payload'    => $payload,
			'content'    => $content,
			'bank'       => $context,
		);
	}

	/**
	 * Diem vao cua hook woocommerce_thankyou va render_block.
	 *
	 * @param int|string $order_id ID don hang.
	 */
	public function render( $order_id ): void {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Hook cu va hook block deu co the chay cho cung mot don.
		if ( self::$rendered_order === $order->get_id() ) {
			return;
		}

		$data = $this->build_display_data( $order );

		if ( null === $data ) {
			return;
		}

		self::$rendered_order = $order->get_id();

		$this->enqueue_qr_library();
		$this->render_html( $data );
	}

	/**
	 * In khoi thong tin chuyen khoan.
	 *
	 * @param array<string, mixed> $data Du lieu tu build_display_data.
	 */
	private function render_html( array $data ): void {
		$order   = $data['order'];
		$amount  = $data['amount'];
		$payload = $data['payload'];
		$content = $data['content'];
		$bank    = $data['bank'];

		$formatted = wp_strip_all_tags( wc_price( $amount ) );

		?>
		<section class="kleer-vietqr" aria-labelledby="kleer-vietqr-title">
			<h2 id="kleer-vietqr-title">
				<?php esc_html_e( 'Thanh toan bang QR VietQR', 'kleer-payments' ); ?>
			</h2>

			<p class="kleer-vietqr__lead">
				<?php
				printf(
					/* translators: %s: so tien can chuyen. */
					esc_html__( 'Quet ma QR duoi day va chuyen khoan dung so tien %s. He thong se doi soat don hang ngay khi nhan duoc tien.', 'kleer-payments' ),
					esc_html( $formatted )
				);
				?>
			</p>

			<div
				class="kleer-vietqr__code"
				data-kleer-qr="<?php echo esc_attr( $payload ); ?>"
				role="img"
				aria-label="<?php esc_attr_e( 'Ma QR chuyen khoan', 'kleer-payments' ); ?>"
			>
				<noscript>
					<?php esc_html_e( 'Hay bat JavaScript de hien ma QR, hoac chuyen khoan thu cong theo thong tin ben duoi.', 'kleer-payments' ); ?>
				</noscript>
			</div>

			<table class="kleer-vietqr__details">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'So tien', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( $formatted ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ngan hang', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( '' !== $bank['bank_name'] ? $bank['bank_name'] : __( 'Xem tren QR', 'kleer-payments' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'So tai khoan', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( $bank['account_number'] ); ?></td>
					</tr>
					<?php if ( '' !== $bank['account_holder'] ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Chu tai khoan', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( $bank['account_holder'] ); ?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Noi dung CK', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( $content ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ma don', 'kleer-payments' ); ?></th>
						<td><?php echo esc_html( $order->get_order_number() ); ?></td>
					</tr>
				</tbody>
			</table>

			<p class="kleer-vietqr__note">
				<?php esc_html_e( 'Giu nguyen noi dung chuyen khoan de he thong doi soat dung don. Don chi duoc xac nhan khi so tien khop.', 'kleer-payments' ); ?>
			</p>
		</section>
		<?php
	}
}