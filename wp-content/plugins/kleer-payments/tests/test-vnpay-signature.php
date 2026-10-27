<?php
/**
 * Test chu ky VNPay (PHP thuan, khong can WordPress).
 *
 * Chay: php wp-content/plugins/kleer-payments/tests/test-vnpay-signature.php
 */

define( 'KLEER_PAYMENTS_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/class-vnpay-signature.php';

use Kleer_Payments\VNPay_Signature;

$pass = 0;
$fail = 0;

function check( string $name, bool $ok ): void {
	global $pass, $fail;
	if ( $ok ) {
		++$pass;
		echo "PASS  $name\n";
	} else {
		++$fail;
		echo "FAIL  $name\n";
	}
}

$secret   = 'DUMMYSECRET0123456789ABCDEFGHIJKL';
$expected = 'fa470b1281d16f6dd459339662dc1cee3f6a2b0e6e59c817e6694de6fee9682690d5768c7bc847cde030663a516c4ac01f61638d806b84777d63664de2cae9ee';
$expected_data = 'vnp_Amount=5000000&vnp_Command=pay&vnp_CreateDate=20261028103000&vnp_CurrCode=VND&vnp_ExpireDate=20261028104500&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+don+hang+123&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2F%3Fwc-api%3Dkleer_vnpay_return&vnp_TmnCode=TESTTMN1&vnp_TxnRef=123R1&vnp_Version=2.1.0';

$params = array(
	'vnp_Version'    => '2.1.0',
	'vnp_Command'    => 'pay',
	'vnp_TmnCode'    => 'TESTTMN1',
	'vnp_Amount'     => '5000000',
	'vnp_CreateDate' => '20261028103000',
	'vnp_CurrCode'   => 'VND',
	'vnp_IpAddr'     => '127.0.0.1',
	'vnp_Locale'     => 'vn',
	'vnp_OrderInfo'  => 'Thanh toan don hang 123',
	'vnp_OrderType'  => 'other',
	'vnp_ReturnUrl'  => 'http://localhost/?wc-api=kleer_vnpay_return',
	'vnp_TxnRef'     => '123R1',
	'vnp_ExpireDate' => '20261028104500',
	'vnp_BankCode'   => '',
);

check( 'chuoi ky dung (bo rong, sort, urlencode)', VNPay_Signature::build_hash_data( $params ) === $expected_data );
check( 'vector co dinh HMAC-SHA512', VNPay_Signature::sign( $params, $secret ) === $expected );
check( 'chu ky dai 128 ky tu hex', 1 === preg_match( '/^[0-9a-f]{128}$/', VNPay_Signature::sign( $params, $secret ) ) );

$signed = $params + array( 'vnp_SecureHash' => $expected );
check( 'verify dung', VNPay_Signature::verify( $signed, $secret ) );

$tampered                 = $signed;
$tampered['vnp_Amount']   = '5000001';
check( 'verify sai khi doi so tien', ! VNPay_Signature::verify( $tampered, $secret ) );

$tampered                   = $signed;
$tampered['vnp_SecureHash'] = substr( $expected, 0, -1 ) . ( 'e' === substr( $expected, -1 ) ? 'f' : 'e' );
check( 'verify sai khi doi 1 ky tu cua hash', ! VNPay_Signature::verify( $tampered, $secret ) );

check( 'verify sai khi sai secret', ! VNPay_Signature::verify( $signed, 'WRONGSECRET' ) );
check( 'verify sai khi secret rong', ! VNPay_Signature::verify( $signed, '' ) );
check( 'verify sai khi thieu hash', ! VNPay_Signature::verify( $params, $secret ) );
check( 'verify sai khi hash la mang', ! VNPay_Signature::verify( $params + array( 'vnp_SecureHash' => array( 'x' ) ), $secret ) );
check( 'verify khong phan biet hoa/thuong cua hex', VNPay_Signature::verify( $params + array( 'vnp_SecureHash' => strtoupper( $expected ) ), $secret ) );

$reordered = array_reverse( $params, true );
check( 'thu tu dau vao khac van cung ket qua', VNPay_Signature::sign( $reordered, $secret ) === $expected );

$with_type = $params + array( 'vnp_SecureHashType' => 'HmacSHA512' );
check( 'bo qua vnp_SecureHashType', VNPay_Signature::sign( $with_type, $secret ) === $expected );

$with_other = $params + array( 'wc-api' => 'kleer_vnpay_ipn', 'foo' => 'bar' );
check( 'bo qua tham so khong phai vnp_*', VNPay_Signature::sign( $with_other, $secret ) === $expected );

check( 'hash_equals khac do dai khong bao loi', ! VNPay_Signature::verify( $params + array( 'vnp_SecureHash' => 'abc' ), $secret ) );

$url = VNPay_Signature::build_url( 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', $params, $secret );
check( 'build_url bat dau bang URL sandbox', 0 === strpos( $url, 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=5000000' ) );
check( 'build_url ket thuc bang vnp_SecureHash', substr( $url, -140 ) === '&vnp_SecureHash=' . $expected || str_ends_with( $url, '&vnp_SecureHash=' . $expected ) );

parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $parsed );
check( 'URL tao ra tu verify duoc', VNPay_Signature::verify( $parsed, $secret ) );

$vn = array( 'vnp_OrderInfo' => 'Thanh toan & dau cach', 'vnp_TxnRef' => '9R2', 'vnp_Amount' => '100' );
$h  = VNPay_Signature::sign( $vn, $secret );
parse_str( VNPay_Signature::build_hash_data( $vn ) . '&vnp_SecureHash=' . $h, $round );
check( 'khu ky tu dac biet round-trip qua query', VNPay_Signature::verify( $round, $secret ) );

echo "\n$pass PASS, $fail FAIL\n";
exit( $fail > 0 ? 1 : 0 );
