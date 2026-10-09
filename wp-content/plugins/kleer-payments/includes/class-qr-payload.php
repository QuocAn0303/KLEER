<?php
/**
 * Sinh chuoi QR chuyen khoan theo chuan EMVCo / NAPAS 247 (VietQR).
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

defined( 'ABSPATH' ) || exit;

/**
 * Lop thuan tuy: chi bien doi chuoi, khong dung WordPress, khong I/O.
 *
 * Viet vay de unit test duoc truc tiep va de chung minh tinh dung dan.
 */
class QR_Payload {

	/**
	 * GUID cua he thong VietQR / NAPAS.
	 */
	public const GUID = '9704360000';

	/**
	 * 01 = static, 11 = dynamic 1 lan, 12 = dynamic nhieu lan (mac dinh theo don hang).
	 */
	public const INITIATION_DYNAMIC = '12';

	/**
	 * VND theo ISO 4217.
	 */
	public const CURRENCY_VND = '704';

	/**
	 * MCC cua ban le / thuong mai dien tu.
	 */
	public const MCC = '5992';

	/**
	 * Ma quoc gia VN.
	 */
	public const COUNTRY_VN = 'VN';

	/**
	 * Gioi han do dai theo chuan EMVCo.
	 */
	public const MAX_MERCHANT_NAME = 25;
	public const MAX_CITY = 15;
	public const MAX_REFERENCE = 25;

	/**
	 * Do dai nguoi dung cho noi dung chuyen khoan.
	 *
	 * Ngan hang thuong chan o 25 ky tu; chuan VietQR khuyen nghi khong vuot 19-25.
	 */
	public const MAX_TRANSFER_CONTENT = 25;

	/**
	 * Nhom ky tu co dau can quy ve ASCII.
	 *
	 * Moi phan tu la mot *chuoi* ky tu co dau (ke ca chu thuong va chu hoa).
	 * Bảng tra duoc sinh tu day o ham build_map() nen khong lo sai so ky tu.
	 *
	 * @var array<string, string>
	 */
	private const ACCENT_GROUPS = array(
		'A' => 'àáạảãâầấậẩẫăằắặẳẵÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴ',
		'E' => 'èéẹẻẽêềếệểễÈÉẸẺẼÊỀẾỆỂỄ',
		'I' => 'ìíịỉĩÌÍỊỈĨ',
		'O' => 'òóọỏõôồốộổỗơờớợởỡÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠ',
		'U' => 'ùúụủũưừứựửữÙÚỤỦŨƯỪỨỰỬỮ',
		'Y' => 'ỳýỵỷỹỲÝỴỶỸ',
		'D' => 'đĐ₫',
	);

	/**
	 * Bang tra dau -> ASCII, dung lai nhieu lan.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $accent_map = null;

	/**
	 * Sinh bang tra tu ACCENT_GROUPS.
	 *
	 * Tach theo code point bang preg_split('//u') de khong vo duoi khi
	 * gap ky tu da byte nhieu (tieng Viet la UTF-8 nhieu byte).
	 *
	 * @return array<string, string>
	 */
	private function build_map(): array {
		$map = array();

		foreach ( self::ACCENT_GROUPS as $ascii => $accents ) {
			$chars = preg_split( '//u', $accents, -1, PREG_SPLIT_NO_EMPTY );

			if ( false === $chars ) {
				continue;
			}

			foreach ( $chars as $char ) {
				$map[ $char ] = $ascii;
			}
		}

		return $map;
	}

	/**
	 * Chuan hoa chuoi tho VietQR chap nhan.
	 *
	 * Bo dau, viet hoa, chi giu A-Z 0-9 va khoang trang, gon khoang trang lap.
	 */
	public function sanitize( string $value ): string {
		if ( null === $this->accent_map ) {
			$this->accent_map = $this->build_map();
		}

		// strtr với mảng chỉ khớp khoản tối đa, nên mỗi key phải là MỘT ký tự.
		$text = strtr( $value, $this->accent_map );

		// Sau khi bo dau, chuoi da thuan ASCII nen strtoupper la du.
		$text = strtoupper( $text );
		$text = preg_replace( '/[^A-Z0-9 ]+/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Cat ngan theo gioi han chuan EMVCo.
	 */
	public function truncate( string $value, int $max_length ): string {
		if ( $max_length <= 0 ) {
			return '';
		}

		return substr( $value, 0, $max_length );
	}

	/**
	 * CRC-16/XMODEM: poly 0x1021, khoi tao 0x0000, khong reflect, khong xor cuoi.
	 *
	 * VietQR yeu cau CRC 4 ky tu hex viet hoa dinh ngay cuoi chuoi.
	 */
	public function crc16( string $data ): string {
		$crc = 0x0000;
		$len = strlen( $data );

		for ( $i = 0; $i < $len; $i++ ) {
			$crc ^= ord( $data[ $i ] ) << 8;

			for ( $bit = 0; $bit < 8; $bit++ ) {
				if ( 0 !== ( $crc & 0x8000 ) ) {
					$crc = ( ( $crc << 1 ) ^ 0x1021 ) & 0xFFFF;
				} else {
					$crc = ( $crc << 1 ) & 0xFFFF;
				}
			}
		}

		return strtoupper( str_pad( dechex( $crc ), 4, '0', STR_PAD_LEFT ) );
	}

	/**
	 * Dong TLV chuan EMVCo: tag + do dai 2 chu so + gia tri.
	 */
	private function tlv( string $tag, string $value ): string {
		return $tag . str_pad( (string) strlen( $value ), 2, '0', STR_PAD_LEFT ) . $value;
	}

	/**
	 * Tao chuoi QR day du cho mot don hang.
	 *
	 * @param int    $amount_vnd So tien VND, phai la so nguyen duong.
	 * @param string $reference   Ma tham chieu (ma don hang).
	 * @param string $bank_bin    6 so BIN cua ngan hang.
	 * @param string $bin_location 4 so ma vung cua ngan hang.
	 * @param string $merchant_name Ten cua hang.
	 * @param string $city          Thanh pho.
	 * @param string $account_type  3 so, '000' la tai khoan thanh toan.
	 *
	 * @return string Chuoi QR hoan chinh, ket thuc bang CRC 4 ky tu hex.
	 */
	public function build(
		int $amount_vnd,
		string $reference,
		string $bank_bin,
		string $bin_location,
		string $merchant_name,
		string $city,
		string $account_type = '000'
	): string {
		if ( $amount_vnd <= 0 ) {
			throw new \InvalidArgumentException(
				'QR VietQR: so tien phai la so nguyen duong (VND). Nhan duoc: ' . $amount_vnd
			);
		}

		$bin      = $this->sanitize( $bank_bin );
		$location = $this->sanitize( $bin_location );
		$type     = $this->sanitize( $account_type );

		if ( '' === $bin || '' === $location || '' === $type ) {
			throw new \InvalidArgumentException( 'QR VietQR: thieu bank BIN, dia chi vung hoac loai tai khoan.' );
		}

		// 26 - Merchant Account Information
		$merchant_account = $this->tlv( '00', self::GUID )
			. $this->tlv( '01', $bin )
			. $this->tlv( '02', $location )
			. $this->tlv( '03', $type );

		// 62 - Additional Data, chua ma don de doi soat
		$additional_data = $this->tlv(
			'01',
			$this->truncate( $this->sanitize( $reference ), self::MAX_REFERENCE )
		);

		$payload = $this->tlv( '00', '01' )
			. $this->tlv( '01', self::INITIATION_DYNAMIC )
			. $this->tlv( '26', $merchant_account )
			. $this->tlv( '52', self::MCC )
			. $this->tlv( '53', self::CURRENCY_VND )
			. $this->tlv( '54', (string) $amount_vnd )
			. $this->tlv( '58', self::COUNTRY_VN )
			. $this->tlv( '59', $this->truncate( $this->sanitize( $merchant_name ), self::MAX_MERCHANT_NAME ) )
			. $this->tlv( '60', $this->truncate( $this->sanitize( $city ), self::MAX_CITY ) )
			. $this->tlv( '62', $additional_data )
			. '6304';

		return $payload . $this->crc16( $payload );
	}

	/**
	 * Tao noi dung chuyen khoan de doi soat theo ma don.
	 *
	 * Chi giu chuoi con lai de an toan khi ma don dai hon hau to.
	 */
	public function build_transfer_content( string $prefix, string $order_number ): string {
		$prefix_clean = $this->sanitize( $prefix );
		$order_clean  = $this->sanitize( $order_number );

		$parts = array_filter(
			array( $prefix_clean, $order_clean ),
			static function ( $part ) {
				return '' !== $part;
			}
		);

		return $this->truncate( implode( ' ', $parts ), self::MAX_TRANSFER_CONTENT );
	}
}