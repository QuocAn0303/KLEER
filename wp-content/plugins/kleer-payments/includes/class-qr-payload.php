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
	 * AID cua NAPAS trong tag 38 (VietQR - chuyen nhanh NAPAS 247).
	 */
	public const GUID = 'A000000727';

	/**
	 * Dich vu chuyen nhanh den tai khoan.
	 */
	public const SERVICE_CODE = 'QRIBFTTA';

	/**
	 * 01 = static, 11 = dynamic 1 lan, 12 = dynamic nhieu lan (mac dinh theo don hang).
	 */
	public const INITIATION_DYNAMIC = '12';

	/**
	 * VND theo ISO 4217.
	 */
	public const CURRENCY_VND = '704';

	/**
	 * Ma quoc gia VN.
	 */
	public const COUNTRY_VN = 'VN';

	/**
	 * Gioi han do dai theo chuan EMVCo.
	 */

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
	 * CRC-16/CCITT-FALSE (ISO/IEC 13239): poly 0x1021, khoi tao 0xFFFF, khong reflect, khong xor cuoi.
	 *
	 * Dung theo dac ta NAPAS 247: CRC 4 ky tu hex viet hoa dinh ngay cuoi chuoi.
	 */
	public function crc16( string $data ): string {
		$crc = 0xFFFF;
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
	 * Tao chuoi VietQR (NAPAS 247, chuyen den tai khoan) cho mot don hang.
	 *
	 * Cau truc: 00 (format) 01 (12 = dong) 38 (AID A000000727 / 01: BIN + so tai khoan /
	 * 02: QRIBFTTA) 53 (704) 54 (so tien) 58 (VN) 62/08 (noi dung chuyen khoan) 63 (CRC).
	 *
	 * @param int    $amount_vnd       So tien VND, phai la so nguyen duong.
	 * @param string $bank_bin         6 so BIN cua ngan hang thu huong.
	 * @param string $account_number   So tai khoan thu huong (4-19 chu so).
	 * @param string $transfer_content Noi dung chuyen khoan (toi da 25 ky tu).
	 *
	 * @return string Chuoi QR hoan chinh, ket thuc bang CRC 4 ky tu hex.
	 *
	 * @throws \InvalidArgumentException Khi tham so khong hop le.
	 */
	public function build(
		int $amount_vnd,
		string $bank_bin,
		string $account_number,
		string $transfer_content
	): string {
		if ( $amount_vnd <= 0 ) {
			throw new \InvalidArgumentException(
				'QR VietQR: so tien phai la so nguyen duong (VND). Nhan duoc: ' . $amount_vnd
			);
		}

		$bin     = trim( $bank_bin );
		$account = trim( $account_number );

		if ( 1 !== preg_match( '/^[0-9]{6}$/', $bin ) ) {
			throw new \InvalidArgumentException( 'QR VietQR: Bank BIN phai gom dung 6 chu so.' );
		}

		if ( 1 !== preg_match( '/^[0-9]{4,19}$/', $account ) ) {
			throw new \InvalidArgumentException( 'QR VietQR: so tai khoan phai gom 4-19 chu so.' );
		}

		$content = $this->truncate( $this->sanitize( $transfer_content ), self::MAX_TRANSFER_CONTENT );

		// 38 - Merchant Account Information (NAPAS 247).
		$beneficiary = $this->tlv( '00', $bin ) . $this->tlv( '01', $account );
		$merchant    = $this->tlv( '00', self::GUID )
			. $this->tlv( '01', $beneficiary )
			. $this->tlv( '02', self::SERVICE_CODE );

		$payload = $this->tlv( '00', '01' )
			. $this->tlv( '01', self::INITIATION_DYNAMIC )
			. $this->tlv( '38', $merchant )
			. $this->tlv( '53', self::CURRENCY_VND )
			. $this->tlv( '54', (string) $amount_vnd )
			. $this->tlv( '58', self::COUNTRY_VN );

		// 62/08 - Noi dung chuyen khoan hien san tren app ngan hang.
		if ( '' !== $content ) {
			$payload .= $this->tlv( '62', $this->tlv( '08', $content ) );
		}

		$payload .= '6304';

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