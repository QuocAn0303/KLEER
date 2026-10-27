<?php
/**
 * Trang cau hinh tai khoan nhan tien VietQR trong WooCommerce > Settings > Payments.
 *
 * Day chi la "cong cau hinh": khong hien o checkout (is_available = false).
 * QR duoc ve o trang cam on cho don chuyen khoan (BACS) boi ThankYou_QR.
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Luu cau hinh vao option woocommerce_kleer_vietqr_settings (trung voi Bank_Account::OPTION_NAME).
 */
class VietQR_Settings extends \WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = Bank_Account::GATEWAY_ID;
		$this->has_fields         = false;
		$this->method_title       = __( 'KLEER VietQR (QR theo don hang)', 'kleer-payments' );
		$this->method_description = __( 'Cau hinh tai khoan nhan tien. QR chuyen khoan rieng cho tung don se hien o trang cam on khi khach chon Chuyen khoan ngan hang (BACS).', 'kleer-payments' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->method_title;
		$this->enabled = (string) $this->get_option( 'enabled', 'yes' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields(): void {
		$this->form_fields = ( new Bank_Account() )->fields();
	}

	/**
	 * Khong bao gio hien o trang thanh toan.
	 */
	public function is_available(): bool {
		return false;
	}
}
