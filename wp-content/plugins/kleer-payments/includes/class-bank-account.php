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
	 * Thieu mot trong ba truong nay thi QR se khong quet duoc.
	 */
	public function is_configured(): bool {
		return '' !== trim( (string) $this->get( 'account_number' ) )
			&& '' !== trim( (string) $this->get( 'bank_bin' ) )
			&& '' !== trim( (string) $this->get( 'bin_location' ) );
	}

	/**
	 * Thuoc tinh doc cho viec tao QR.
	 *
	 * @return array{account_number: string, bank_bin: string, bin_location: string, account_holder: string, bank_name: string, merchant_name: string, city: string, account_type: string, transfer_prefix: string}
	 */
	public function qr_context(): array {
		return array(
			'account_number' => (string) $this->get( 'account_number' ),
			'bank_bin'       => (string) $this->get( 'bank_bin' ),
			'bin_location'   => (string) $this->get( 'bin_location' ),
			'account_holder' => (string) $this->get( 'account_holder' ),
			'bank_name'      => (string) $this->get( 'bank_name' ),
			'merchant_name'  => (string) $this->get( 'merchant_name', 'KLEER' ),
			'city'           => (string) $this->get( 'city', 'HA NOI' ),
			'account_type'   => (string) $this->get( 'account_type', '000' ),
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
	private function fields(): array {
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
				'description' => __( '6 so dau cua ngan hang. Vi du VietinBank: 970436', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'bin_location'     => array(
				'title'       => __( 'Ma vung ngan hang (4 so)', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( '4 so ma vung. Vi du: 0208', 'kleer-payments' ),
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
			'merchant_name'    => array(
				'title'       => __( 'Ten hien thi trong QR', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'Toi da 25 ky tu, khong dau, viet hoa.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => 'KLEER',
			),
			'city'             => array(
				'title'       => __( 'Thanh pho trong QR', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'Toi da 15 ky tu, khong dau, viet hoa.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => 'HA NOI',
			),
			'transfer_prefix'  => array(
				'title'       => __( 'Tien to noi dung chuyen khoan', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( 'VD: KLEER. Noi dung se la "TIEN TO + ma don", toi da 25 ky tu.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => 'KLEER',
			),
			'account_type'     => array(
				'title'       => __( 'Loai tai khoan', 'kleer-payments' ),
				'type'        => 'text',
				'description' => __( '000 = tai khoan thanh toan, 001 = tiet kiem.', 'kleer-payments' ),
				'desc_tip'    => true,
				'default'     => '000',
			),
		);
	}
}