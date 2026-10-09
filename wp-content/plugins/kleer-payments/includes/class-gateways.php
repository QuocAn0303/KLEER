<?php
/**
 * Khai bao va kich hoat cac phuong thuc thanh toan truyen thong.
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Quan ly COD (cod) va chuyen khoan ngan hang (bacs).
 *
 * COD va BACS la gateway co san cua WooCommerce, khong can tich hop API gi.
 * Plugin chi dat nhan ten tieng Viet va bat chung cho luon nhin thay duoc.
 */
class Gateways {

	/**
	 * Dich vu cau hinh tai khoan ngan hang.
	 *
	 * @var Bank_Account
	 */
	private Bank_Account $bank_account;

	public function __construct() {
		$this->bank_account = new Bank_Account();
	}

	/**
	 * Dang ky cac hook cau hinh.
	 */
	public function register(): void {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );
		add_action( 'admin_notices', array( $this, 'render_config_notice' ) );
	}

	/**
	 * Them gateway VietQR vao danh sach phuong thuc thanh toan.
	 *
	 * Gateway nay chi giu pham vi hien thi cau hinh; phan QR that su duoc
	 * ve tren trang cam on don boi class ThankYou_QR.
	 *
	 * @param array<int, mixed> $gateways Danh sach gateway hien tai.
	 * @return array<int, mixed>
	 */
	public function register_gateways( $gateways ) {
		if ( ! is_array( $gateways ) ) {
			return $gateways;
		}

		$gateways[] = $this->bank_account->gateway_definition();

		return $gateways;
	}

	/**
	 * Dat mac dinh cho COD va BACS. Chi chay mot lan khi kich hoat plugin.
	 *
	 * Khong ghi de trong vong lap thuong de admin van toan quyen tu chinh.
	 */
	public static function apply_gateway_defaults(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'      => 'yes',
				'title'        => __( 'Thanh toan khi nhan hang (COD)', 'kleer-payments' ),
				'description'  => __( 'Ban thanh toan tien mat cho nhan vien giao hang. Vui long chuan bi tien mat.', 'kleer-payments' ),
				'instructions' => __( 'Khach tra tien mat khi nhan hang.', 'kleer-payments' ),
				'enable_for_methods' => array(),
				'enable_for_virtual'  => 'no',
			)
		);

		update_option(
			'woocommerce_bacs_settings',
			array(
				'enabled'      => 'yes',
				'title'        => __( 'Chuyen khoan ngan hang (QR VietQR)', 'kleer-payments' ),
				'description'  => __( 'Chuyen khoan qua so tai khoan cua KLEER. QR se hien o trang cam on don.', 'kleer-payments' ),
				'instructions' => '',
			)
		);
	}

	/**
	 * Canh bao trong trang quan tri neu chua cau hinh tai khoan.
	 */
	public function render_config_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || false === strpos( (string) $screen->id, 'woocommerce_page_wc-settings' ) ) {
			return;
		}

		if ( $this->bank_account->is_configured() ) {
			return;
		}

		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . Bank_Account::GATEWAY_ID );

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'KLEER Payments: chua cau hinh so tai khoan, Bank BIN va ma vung nen QR chua the sinh.', 'kleer-payments' );
		echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Cau hinh ngay', 'kleer-payments' ) . '</a>';
		echo '</p></div>';
	}
}