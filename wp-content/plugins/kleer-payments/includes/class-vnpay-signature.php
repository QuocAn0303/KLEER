<?php
/**
 * Ky va kiem tra chu ky HMAC-SHA512 cho VNPay.
 *
 * Lop thuan PHP, KHONG goi ham WordPress de co the test ngoai WordPress.
 *
 * @package Kleer_Payments
 */

namespace Kleer_Payments;

if ( ! defined( 'ABSPATH' ) && ! defined( 'KLEER_PAYMENTS_TESTING' ) ) {
	exit;
}

/**
 * Quy tac ky cua VNPay (v2.1.0): lay tham so vnp_* (tru vnp_SecureHash va
 * vnp_SecureHashType), bo tham so rong, sap xep key a-z, noi
 * urlencode(key)=urlencode(value) bang "&", roi HMAC-SHA512 voi HashSecret.
 */
final class VNPay_Signature {

	/**
	 * Dung chuoi du lieu can ky.
	 *
	 * @param array<string, mixed> $params Tham so (co the lan tham so khac vnp_*).
	 */
	public static function build_hash_data( array $params ): string {
		$data = array();

		foreach ( $params as $key => $value ) {
			if ( ! is_string( $key ) || 0 !== strncmp( $key, 'vnp_', 4 ) ) {
				continue;
			}
			if ( 'vnp_SecureHash' === $key || 'vnp_SecureHashType' === $key ) {
				continue;
			}
			if ( ! is_scalar( $value ) || '' === (string) $value ) {
				continue;
			}
			$data[ $key ] = (string) $value;
		}

		ksort( $data, SORT_STRING );

		$pairs = array();
		foreach ( $data as $key => $value ) {
			$pairs[] = urlencode( $key ) . '=' . urlencode( $value );
		}

		return implode( '&', $pairs );
	}

	/**
	 * Tinh vnp_SecureHash (hex, 128 ky tu).
	 *
	 * @param array<string, mixed> $params Tham so.
	 * @param string               $secret vnp_HashSecret.
	 */
	public static function sign( array $params, string $secret ): string {
		return hash_hmac( 'sha512', self::build_hash_data( $params ), $secret );
	}

	/**
	 * Kiem tra chu ky trong $params['vnp_SecureHash'] (so sanh hang thoi gian).
	 *
	 * @param array<string, mixed> $params Tham so nhan ve.
	 * @param string               $secret vnp_HashSecret.
	 */
	public static function verify( array $params, string $secret ): bool {
		if ( '' === $secret ) {
			return false;
		}

		$provided = $params['vnp_SecureHash'] ?? '';
		if ( ! is_string( $provided ) || '' === $provided ) {
			return false;
		}

		return hash_equals( self::sign( $params, $secret ), strtolower( $provided ) );
	}

	/**
	 * Dung URL thanh toan day du kem chu ky.
	 *
	 * @param string               $base_url URL cong thanh toan (khong co query).
	 * @param array<string, mixed> $params   Tham so vnp_*.
	 * @param string               $secret   vnp_HashSecret.
	 */
	public static function build_url( string $base_url, array $params, string $secret ): string {
		return $base_url . '?' . self::build_hash_data( $params ) . '&vnp_SecureHash=' . self::sign( $params, $secret );
	}
}
