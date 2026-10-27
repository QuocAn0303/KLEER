<?php
/**
 * Cau hinh tai khoan ngan hang nhan tien + thong tin hien thi tren QR.
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Luu cau hinh trong WooCommerce Payment Settings.
 *
 * Dung dang gateway duoi dang mang de khong phai ke thua WC_Settings_API,
 * nho do file nay nap duoc an toan ke chua co WooCommerce.
 */
class Bank_Account {

	/**
	 * ID cua gateway trong WooCommerce.
	 */
	public const GATEWAY_ID = 'kleer_vietqr';

	/**
	 * Ten option luu cau hinh.
	 */
	public const OPTION_NAME = 'woocommerce_kleer_vietqr_settings';

	/**
	 * Doc mot truong cau hinh, tra ve gia tri mac dinh neu chua luu.
	 *
	 * @param string $key         Khoa cau hinh.
	 * @param mixed  $default     Gia tri mac dinh.
	 * @return mixed
	 */
	public function get( string $key, $default = '' ) {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( is_array( $settings ) && array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Doc toan bo cau hinh da luu.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		$settings = get_option( self::OPTION_NAME, array() );

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Kiem tra du dieu kien de co the sinh QR.
	 *
	 * Can so tai khoan (4-19 chu so) va Bank BIN (dung 6 chu so).
	 */
	public function is_configured(): bool {
		return 1 === preg_match( '/^[0-9]{4,19}$/', trim( (string) $this->get( 'account_number' ) ) )
			&& 1 === preg_match( '/^[0-9]{6}$/', trim( (string) $this->get( 'bank_bin' ) ) );
	}

	/**
	 * Thuoc tinh doc cho viec tao QR.
	 *
	 * @return array{account_number: string, bank_bin: string, account_holder: string, bank_name: string, transfer_prefix: string}
	 */
	public function qr_context(): array {
		return array(
			'account_number'  => trim( (string) $this->get( 'account_number' ) ),
			'bank_bin'        => trim( (string) $this->get( 'bank_bin' ) ),
			'account_holder'  => (string) $this->get( 'account_holder' ),
			'bank_name'       => (string) $this->get( 'bank_name' ),
			'transfer_prefix' => (string) $this->get( 'transfer_prefix', 'KLEER' ),
		);
	}

	/**
	 * Khai bao gateway cho trang WooCommerce -> Settings -> Payments.
	 *
	 * @return array<string, mixed>
	 */
	public function gateway_definition(): array {
		return array(
			'id'              => self::GATEWAY_ID,
			'method_title'    => __( 'KLEER VietQR (QR theo don hang)', 'kleer-payments' ),
			'method_description' => __(
				'Sinh ma QR chuyen khoan rieng cho tung don hang. Khach quet QR, chuyen khoan dung so tien va ghi dung noi dung, don hang duoc doi soat tu dong.',
				'kleer-payments'
			),
			'enabled'         => 'yes',
			'fields'          => $this->fields(),
		);
	}

	/**
	 * Cac truong form trong trang cai dat.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function fields(): array {
		return array(
			'enabled'          => array(
				'title'   => __( 'Bat/tat VietQR', 'kleer-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Hien QR tren trang cam on don', 'kleer-payments' ),
				'default' => 'yes',
			),
			'account_number'   => array(
				'title'       => __( 'So tai khoan nhan tien', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'Vi du: 0123456789', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'account_holder'   => array(
				'title'       => __( 'Ten chu tai khoan', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'Dung chinh xac chu ke khai ngan hang.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'bank_bin'         => array(
				'title'       => __( 'Bank BIN (6 so)', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( '6 so BIN cua ngan hang thu huong (NAPAS). Vi du: Vietcombank 970436, VietinBank 970415, BIDV 970418, MB 970422, Techcombank 970407', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'bank_name'        => array(
				'title'       => __( 'Ten ngan hang', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'Chi de hien thi cho khach, khong nam trong QR.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'transfer_prefix'  => array(
				'title'       => __( 'Tien to noi dung chuyen khoan', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'VD: KLEER. Noi dung se la "TIEN TO + ma don", toi da 25 ky tu.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => 'KLEER',
			),
		);
	}
}