<?php
/**
 * Cong thanh toan VNPay Sandbox cho WooCommerce.
 *
 * Mo hinh: Redirect + Return URL (chi hien thi) + IPN (nguon DUY NHAT cap nhat don).
 * Dac ta: docs/flows/L04-checkout-payment/sequence.md
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Cong VNPay. TmnCode/HashSecret chi doc tu hang so trong wp-config.php:
 * KLEER_VNPAY_TMN_CODE, KLEER_VNPAY_HASH_SECRET (tuy chon KLEER_VNPAY_PAYMENT_URL).
 */
class VNPay_Gateway extends \WC_Payment_Gateway {

	public const GATEWAY_ID      = 'kleer_vnpay';
	public const DEFAULT_PAY_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

	private const META_TXN_REF    = '_kleer_vnpay_txn_ref';
	private const META_ATTEMPT    = '_kleer_vnpay_attempt';
	private const LOCK_OPTION_FMT = 'kleer_vnpay_lock_%d';
	private const LOCK_TTL        = 120;

	/**
	 * Bat log chi tiet.
	 *
	 * @var bool
	 */
	private bool $debug = false;

	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'VNPay (Sandbox)', 'kleer-payments' );
		$this->method_description = __( 'Thanh toan qua cong VNPay. TmnCode va HashSecret khai bao trong wp-config.php.', 'kleer-payments' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = (string) $this->get_option( 'title' );
		$this->description = (string) $this->get_option( 'description' );
		$this->enabled     = (string) $this->get_option( 'enabled' );
		$this->debug       = 'yes' === $this->get_option( 'debug' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_kleer_vnpay_ipn', array( $this, 'check_ipn_response' ) );
		add_action( 'woocommerce_api_kleer_vnpay_return', array( $this, 'handle_return' ) );
	}

	/**
	 * Cac truong cau hinh. Khong co o nhap TmnCode/HashSecret (xem wp-config.php).
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Bat/Tat', 'kleer-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Bat thanh toan VNPay', 'kleer-payments' ),
				'default' => 'no',
			),
			'title'              => array(
				'title'   => __( 'Tieu de', 'kleer-payments' ),
				'type'    => 'text',
				'default' => __( 'Thanh toan qua VNPay', 'kleer-payments' ),
			),
			'description'        => array(
				'title'   => __( 'Mo ta', 'kleer-payments' ),
				'type'    => 'textarea',
				'default' => __( 'Thanh toan bang the ATM/Visa/Master hoac VNPAY-QR.', 'kleer-payments' ),
			),
			'credentials_status' => array(
				'title'       => __( 'Thong tin ket noi', 'kleer-payments' ),
				'type'        => 'title',
				'description' => $this->has_credentials()
					? __( 'Da cau hinh trong wp-config.php.', 'kleer-payments' )
					: __( 'Chua cau hinh: khai bao KLEER_VNPAY_TMN_CODE va KLEER_VNPAY_HASH_SECRET trong wp-config.php.', 'kleer-payments' ),
			),
			'expire_minutes'     => array(
				'title'             => __( 'Han thanh toan (phut)', 'kleer-payments' ),
				'type'              => 'number',
				'default'           => '15',
				'custom_attributes' => array(
					'min'  => '5',
					'max'  => '60',
					'step' => '1',
				),
			),
			'debug'              => array(
				'title'   => __( 'Nhat ky', 'kleer-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ghi log chi tiet (WooCommerce > Status > Logs, nguon kleer-vnpay)', 'kleer-payments' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Chi hien o checkout khi da co credentials va tien te la VND.
	 */
	public function is_available(): bool {
		if ( ! parent::is_available() ) {
			return false;
		}

		if ( ! $this->has_credentials() ) {
			return false;
		}

		return 'VND' === get_woocommerce_currency();
	}

	/**
	 * Tao URL thanh toan va chuyen khach sang VNPay.
	 *
	 * Khong tru kho, khong payment_complete, khong empty_cart o day: chi IPN moi cap nhat don.
	 *
	 * @param int $order_id ID don.
	 * @return array<string, string>
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			wc_add_notice( __( 'Khong tim thay don hang.', 'kleer-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( $order->is_paid() || ! $this->has_credentials() ) {
			wc_add_notice( __( 'Khong the thanh toan don hang nay qua VNPay.', 'kleer-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$total = (int) round( (float) $order->get_total() );
		if ( $total <= 0 ) {
			wc_add_notice( __( 'So tien thanh toan khong hop le.', 'kleer-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( $order->has_status( 'failed' ) ) {
			$order->update_status( 'pending', __( 'Thanh toan lai qua VNPay.', 'kleer-payments' ) );
		}

		$attempt = (int) $order->get_meta( self::META_ATTEMPT ) + 1;
		$txn_ref = $order->get_id() . 'R' . $attempt;

		$tz      = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now     = new \DateTimeImmutable( 'now', $tz );
		$minutes = max( 5, min( 60, (int) $this->get_option( 'expire_minutes', 15 ) ) );

		$params = array(
			'vnp_Version'    => '2.1.0',
			'vnp_Command'    => 'pay',
			'vnp_TmnCode'    => $this->get_tmn_code(),
			'vnp_Amount'     => (string) ( $total * 100 ),
			'vnp_CreateDate' => $now->format( 'YmdHis' ),
			'vnp_ExpireDate' => $now->modify( '+' . $minutes . ' minutes' )->format( 'YmdHis' ),
			'vnp_CurrCode'   => 'VND',
			'vnp_IpAddr'     => $this->get_client_ip(),
			'vnp_Locale'     => 'vn',
			'vnp_OrderInfo'  => $this->build_order_info( $order ),
			'vnp_OrderType'  => 'other',
			'vnp_ReturnUrl'  => WC()->api_request_url( 'kleer_vnpay_return' ),
			'vnp_TxnRef'     => $txn_ref,
		);

		$url = VNPay_Signature::build_url( $this->get_payment_url(), $params, $this->get_hash_secret() );

		$order->update_meta_data( self::META_ATTEMPT, $attempt );
		$order->update_meta_data( self::META_TXN_REF, $txn_ref );
		$order->add_order_note(
			sprintf(
				/* translators: %s: ma tham chieu giao dich VNPay */
				__( 'Chuyen khach sang VNPay, vnp_TxnRef %s.', 'kleer-payments' ),
				$txn_ref
			)
		);
		$order->save();

		$this->log( 'Tao URL thanh toan: order ' . $order->get_id() . ', TxnRef ' . $txn_ref );

		return array(
			'result'   => 'success',
			'redirect' => $url,
		);
	}

	/**
	 * Endpoint IPN (server-to-server). Chi tra JSON {RspCode, Message}.
	 */
	public function check_ipn_response(): void {
		try {
			$result = $this->process_ipn( $this->collect_params() );
		} catch ( \Throwable $e ) {
			$this->log( 'IPN loi: ' . $e->getMessage(), 'error' );
			$result = array(
				'RspCode' => '99',
				'Message' => 'Unknown error',
			);
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $result );
		exit;
	}

	/**
	 * Xu ly IPN theo thu tu trong sequence.md nhom C.
	 *
	 * @param array<string, string> $params Tham so vnp_* nhan ve (chua sanitize).
	 * @return array{RspCode: string, Message: string}
	 */
	public function process_ipn( array $params ): array {
		if ( ! VNPay_Signature::verify( $params, $this->get_hash_secret() ) ) {
			$this->log( 'IPN sai chu ky, TxnRef ' . $this->safe_ref( $params ), 'warning' );
			return $this->rsp( '97', 'Invalid signature' );
		}

		$order = $this->find_order_by_txn_ref( (string) ( $params['vnp_TxnRef'] ?? '' ) );
		if ( ! $order ) {
			$this->log( 'IPN khong thay don, TxnRef ' . $this->safe_ref( $params ), 'warning' );
			return $this->rsp( '01', 'Order not found' );
		}

		$amount = (string) ( $params['vnp_Amount'] ?? '' );
		if ( ! ctype_digit( $amount ) || (int) $amount !== (int) round( (float) $order->get_total() ) * 100 ) {
			$this->log( 'IPN sai so tien, don ' . $order->get_id(), 'warning' );
			return $this->rsp( '04', 'Invalid amount' );
		}

		$lock_name = sprintf( self::LOCK_OPTION_FMT, $order->get_id() );
		if ( ! $this->acquire_lock( $lock_name ) ) {
			return $this->rsp( '02', 'Order already confirmed' );
		}

		try {
			$order = wc_get_order( $order->get_id() );
			if ( ! $order instanceof \WC_Order || ! $order->has_status( 'pending' ) ) {
				return $this->rsp( '02', 'Order already confirmed' );
			}

			$response_code  = (string) ( $params['vnp_ResponseCode'] ?? '' );
			$txn_status     = (string) ( $params['vnp_TransactionStatus'] ?? '' );
			$transaction_no = (string) ( $params['vnp_TransactionNo'] ?? '' );

			$order->update_meta_data( '_kleer_vnpay_transaction_no', $transaction_no );
			$order->update_meta_data( '_kleer_vnpay_response_code', $response_code );
			$order->update_meta_data( '_kleer_vnpay_bank_code', (string) ( $params['vnp_BankCode'] ?? '' ) );
			$order->update_meta_data( '_kleer_vnpay_pay_date', (string) ( $params['vnp_PayDate'] ?? '' ) );

			if ( '00' === $response_code && '00' === $txn_status ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: ma giao dich VNPay */
						__( 'VNPay xac nhan thanh toan thanh cong. Ma giao dich: %s.', 'kleer-payments' ),
						$transaction_no
					)
				);
				$order->payment_complete( $transaction_no );

				$order = wc_get_order( $order->get_id() );
				if ( $order && ! $order->has_status( 'processing' ) ) {
					$order->add_order_note(
						sprintf(
							/* translators: %s: trang thai don */
							__( 'Luu y: sau thanh toan don o trang thai "%s" (khong phai Dang xu ly).', 'kleer-payments' ),
							$order->get_status()
						)
					);
				}
			} elseif ( '24' === $response_code ) {
				$order->update_status( 'cancelled', __( 'Khach huy giao dich tren VNPay (ma 24).', 'kleer-payments' ) );
			} else {
				$order->update_status(
					'failed',
					sprintf(
						/* translators: %s: ma loi VNPay */
						__( 'Thanh toan VNPay that bai (vnp_ResponseCode %s).', 'kleer-payments' ),
						$response_code
					)
				);
			}

			$this->log( 'IPN da xu ly: don ' . $order->get_id() . ', ResponseCode ' . $response_code );

			return $this->rsp( '00', 'Confirm Success' );
		} finally {
			$this->release_lock( $lock_name );
		}
	}

	/**
	 * Return URL: chi hien thi ket qua, KHONG ghi trang thai don.
	 */
	public function handle_return(): void {
		$params = $this->collect_params();

		if ( ! VNPay_Signature::verify( $params, $this->get_hash_secret() ) ) {
			wc_add_notice( __( 'Khong xac thuc duoc ket qua thanh toan VNPay.', 'kleer-payments' ), 'error' );
			$this->redirect( wc_get_cart_url() );
			return;
		}

		$order = $this->find_order_by_txn_ref( (string) ( $params['vnp_TxnRef'] ?? '' ) );
		if ( ! $order ) {
			wc_add_notice( __( 'Khong tim thay don hang.', 'kleer-payments' ), 'error' );
			$this->redirect( wc_get_cart_url() );
			return;
		}

		if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
			$this->empty_cart();
			$this->redirect( $order->get_checkout_order_received_url() );
			return;
		}

		if ( $order->has_status( 'cancelled' ) ) {
			wc_add_notice( __( 'Ban da huy giao dich VNPay. Vui long dat lai don neu van muon mua.', 'kleer-payments' ), 'notice' );
			$this->redirect( wc_get_cart_url() );
			return;
		}

		if ( $order->has_status( 'failed' ) ) {
			wc_add_notice( __( 'Thanh toan VNPay khong thanh cong. Ban co the thu thanh toan lai.', 'kleer-payments' ), 'error' );
			$this->redirect( $order->get_checkout_payment_url() );
			return;
		}

		wc_add_notice( __( 'Dang cho VNPay xac nhan thanh toan. Don hang se cap nhat trong giay lat.', 'kleer-payments' ), 'notice' );
		$this->redirect( $order->get_checkout_order_received_url() );
	}

	/* ---------------------------------------------------------------- */

	private function has_credentials(): bool {
		return '' !== $this->get_tmn_code() && '' !== $this->get_hash_secret();
	}

	private function get_tmn_code(): string {
		return defined( 'KLEER_VNPAY_TMN_CODE' ) ? (string) constant( 'KLEER_VNPAY_TMN_CODE' ) : '';
	}

	private function get_hash_secret(): string {
		return defined( 'KLEER_VNPAY_HASH_SECRET' ) ? (string) constant( 'KLEER_VNPAY_HASH_SECRET' ) : '';
	}

	private function get_payment_url(): string {
		return defined( 'KLEER_VNPAY_PAYMENT_URL' ) && '' !== (string) constant( 'KLEER_VNPAY_PAYMENT_URL' )
			? (string) constant( 'KLEER_VNPAY_PAYMENT_URL' )
			: self::DEFAULT_PAY_URL;
	}

	/**
	 * Lay cac tham so vnp_* dang chua sanitize (de tinh chu ky dung).
	 *
	 * @return array<string, string>
	 */
	private function collect_params(): array {
		$params = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- VNPay goi tu may chu, xac thuc bang chu ky HMAC.
		foreach ( wp_unslash( $_GET ) as $key => $value ) {
			if ( is_string( $key ) && 0 === strncmp( $key, 'vnp_', 4 ) && is_string( $value ) ) {
				$params[ $key ] = $value;
			}
		}

		return $params;
	}

	private function find_order_by_txn_ref( string $txn_ref ): ?\WC_Order {
		if ( '' === $txn_ref || strlen( $txn_ref ) > 100 ) {
			return null;
		}

		// meta_key/meta_value duoc ca kho luu tru CPT lan HPOS ho tro (meta_query thi CPT bo qua).
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => self::META_TXN_REF, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $txn_ref, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$order = $orders[0] ?? null;

		// Kiem tra lai de khong bao gio nhan nham don khi bo loc meta bi bo qua.
		if ( ! $order instanceof \WC_Order || (string) $order->get_meta( self::META_TXN_REF ) !== $txn_ref ) {
			return null;
		}

		return $order;
	}

	private function build_order_info( \WC_Order $order ): string {
		$info = remove_accents( 'Thanh toan don hang ' . $order->get_order_number() );
		$info = (string) preg_replace( '/[^A-Za-z0-9 ]/', '', $info );

		return substr( $info, 0, 255 );
	}

	private function get_client_ip(): string {
		$ip = class_exists( '\WC_Geolocation' ) ? \WC_Geolocation::get_ip_address() : '';

		return '' !== $ip && false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '127.0.0.1';
	}

	/**
	 * Khoa nguyen tu bang INSERT IGNORE vao bang options (option_name la UNIQUE),
	 * khong phu thuoc object cache Redis. Khoa qua LOCK_TTL giay duoc coi la bi ket.
	 */
	private function acquire_lock( string $lock_name ): bool {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
				$lock_name,
				time() - self::LOCK_TTL
			)
		);

		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
				$lock_name,
				(string) time()
			)
		);

		return 1 === (int) $inserted;
	}

	private function release_lock( string $lock_name ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->options, array( 'option_name' => $lock_name ) );
	}

	private function empty_cart(): void {
		if ( function_exists( 'wc_load_cart' ) && null === WC()->cart ) {
			wc_load_cart();
		}
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
	}

	private function redirect( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @return array{RspCode: string, Message: string}
	 */
	private function rsp( string $code, string $message ): array {
		return array(
			'RspCode' => $code,
			'Message' => $message,
		);
	}

	/**
	 * @param array<string, string> $params Tham so nhan ve.
	 */
	private function safe_ref( array $params ): string {
		return substr( (string) preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $params['vnp_TxnRef'] ?? '' ) ), 0, 40 );
	}

	/**
	 * Ghi log (khong bao gio ghi HashSecret hay du lieu the).
	 */
	private function log( string $message, string $level = 'info' ): void {
		if ( ! $this->debug && 'warning' !== $level && 'error' !== $level ) {
			return;
		}

		wc_get_logger()->log( $level, $message, array( 'source' => 'kleer-vnpay' ) );
	}
}
