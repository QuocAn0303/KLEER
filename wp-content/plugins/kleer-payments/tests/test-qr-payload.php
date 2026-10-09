<?php
/**
 * Kiem thu don lap cho lop QR_Payload.
 *
 * Chay khong can WordPress:
 *   docker run --rm -v "D:\KLEER:/app" -w /app --entrypoint php \
 *     wordpress:6.4-php8.1-apache wp-content/plugins/kleer-payments/tests/test-qr-payload.php
 */

define( 'ABSPATH', __DIR__ );

require_once __DIR__ . '/../includes/class-qr-payload.php';

use Kleer_Payments\QR_Payload;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( string $label, $actual, $expected ): void {
	if ( $actual === $expected ) {
		++$GLOBALS['pass'];
		echo "  [PASS] {$label}\n";

		return;
	}

	++$GLOBALS['fail'];
	echo "  [FAIL] {$label}\n";
	echo "         expected: " . var_export( $expected, true ) . "\n";
	echo "         actual:   " . var_export( $actual, true ) . "\n";
}

function check_true( string $label, $actual ): void {
	check( $label, (bool) $actual, true );
}

/**
 * Doc lai chuoi QR de kiem chung tung truong TLV.
 *
 * @return array<string, string> Map tag => gia tri.
 */
function parse_qr( string $payload ): array {
	$out = array();

	for ( $i = 0; $i < strlen( $payload ); ) {
		$tag    = substr( $payload, $i, 2 );
		$length = (int) substr( $payload, $i + 2, 2 );
		$value  = substr( $payload, $i + 4, $length );

		if ( '63' === $tag ) {
			break;
		}

		$out[ $tag ] = $value;
		$i          += 4 + $length;
	}

	return $out;
}

$qr = new QR_Payload();

echo "\n=== 1. Chuan hoa chuoi (bo dau, viet hoa) ===\n";
check( 'Hà Nội', $qr->sanitize( 'Hà Nội' ), 'HA NOI' );
check( 'Đặng Thùy Trâm', $qr->sanitize( 'Đặng Thùy Trâm' ), 'DANG THUY TRAM' );
check( 'KLEER - Cửa hàng', $qr->sanitize( 'KLEER - Cửa hàng' ), 'KLEER CUA HANG' );
check( 'gia re  15.000đ', $qr->sanitize( 'giá re  15.000đ' ), 'GIA RE 15 000D' );
check( 'chu thuong giu dau cach', $qr->sanitize( '  Hello   World  ' ), 'HELLO WORLD' );

echo "\n=== 2. CRC-16/XMODEM ===\n";
// Gia tri kiem chuan cua CRC-16/XMODEM voi chuoi "123456789" la 0x31C3.
check( 'check value 123456789', $qr->crc16( '123456789' ), '31C3' );
check( 'chuoi rong', $qr->crc16( '' ), '0000' );

echo "\n=== 3. Cat ngan ===\n";
check( 'cat theo gioi han', $qr->truncate( 'ABCDEFG', 3 ), 'ABC' );
check( 'khong cat khi ngan gon', $qr->truncate( 'AB', 25 ), 'AB' );
check( 'gioi han 0', $qr->truncate( 'ABC', 0 ), '' );

echo "\n=== 4. Noi dung chuyen khoan ===\n";
check( 'KLEER + don 12345', $qr->build_transfer_content( 'KLEER', '12345' ), 'KLEER 12345' );
check( 'bo dau trong tien to', $qr->build_transfer_content( 'Kléer', 'DH-001' ), 'KLEER DH 001' );
check( 'cat toi da 25 ky tu', strlen( $qr->build_transfer_content( 'KLEER', '123456789012345678901234567890' ) ), 25 );

echo "\n=== 5. Payload QR day du ===\n";
$payload = $qr->build(
	500000,
	'12345',
	'970436',
	'0208',
	'KLEER',
	'HA NOI',
	'000'
);

check_true( 'bat dau bang 000201', 0 === strpos( $payload, '000201' ) );
check_true( 'co tag 01 dynamic (010212)', false !== strpos( $payload, '010212' ) );
check_true( 'ket thuc bang CRC hex', (bool) preg_match( '/[0-9A-F]{4}$/', $payload ) );

$fields = parse_qr( $payload );
check( 'tag 00 - payload format', $fields['00'], '01' );
check( 'tag 01 - initiation method', $fields['01'], '12' );
check( 'tag 52 - MCC', $fields['52'], '5992' );
check( 'tag 53 - currency VND', $fields['53'], '704' );
check( 'tag 54 - so tien', $fields['54'], '500000' );
check( 'tag 58 - quoc gia', $fields['58'], 'VN' );
check( 'tag 59 - ten merchant', $fields['59'], 'KLEER' );
check( 'tag 60 - thanh pho', $fields['60'], 'HA NOI' );

echo "\n=== 6. Kiem chung CRC gan nhat ===\n";
$body    = substr( $payload, 0, -4 );
$appended = substr( $payload, -4 );
check( 'CRC khop phan duoi cung', $qr->crc16( $body ), $appended );

echo "\n=== 7. Ma don nam trong Additional Data (62) ===\n";
check_true( 'tag 62 ton tai', isset( $fields['62'] ) );
check_true( 'ma don nam trong 62', false !== strpos( $fields['62'], '12345' ) );

echo "\n=== 8. Merchant Account (26) ===\n";
$merchant = parse_qr( '2600' . $fields['26'] );
check( '26/00 - GUID VietQR', $merchant['00'], '9704360000' );
check( '26/01 - bank BIN', $merchant['01'], '970436' );
check( '26/02 - ma vung', $merchant['02'], '0208' );
check( '26/03 - loai tai khoan', $merchant['03'], '000' );

echo "\n=== 9. Da chuan hoa duoc truyen vao QR ===\n";
$dirty = $qr->build(
	150000,
	'ĐH-7788',
	'970436',
	'0208',
	'Cửa Hàng Kléer',
	'Hồ Chí Minh',
	'000'
);
$dirty_fields = parse_qr( $dirty );
check( 'ten khong dau, viet hoa', $dirty_fields['59'], 'CUA HANG KLEER' );
check( 'thanh pho khong dau', $dirty_fields['60'], 'HO CHI MINH' );
check_true( 'ma don trong QR da bo dau', false !== strpos( $dirty_fields['62'], 'DH 7788' ) );

echo "\n=== 10. So tien khong hop le phai bi tu choi ===\n";
foreach ( array( 0, -1, -500000 ) as $bad ) {
	$threw = false;
	try {
		$qr->build( $bad, '1', '970436', '0208', 'KLEER', 'HA NOI' );
	} catch ( InvalidArgumentException $e ) {
		$threw = true;
	}
	check( 'tu choi so tien ' . $bad, $threw, true );
}

$threw = false;
try {
	$qr->build( 1000, '1', '', '0208', 'KLEER', 'HA NOI' );
} catch ( InvalidArgumentException $e ) {
	$threw = true;
}
check( 'tu choi khi thieu bank BIN', $threw, true );

echo "\n=== 11. Do dai truong gioi han ===\n";
$long = $qr->build(
	1000,
	'1',
	'970436',
	'0208',
	'Ten Qua Dai Rat Lau Khong Gioi Han Nao',
	'Thanh Pho Qua Dai Khong Gioi Han Gi Ca',
	'000'
);
$long_fields = parse_qr( $long );
check( 'ten merchant <= 25', strlen( $long_fields['59'] ) <= 25, true );
check( 'thanh pho <= 15', strlen( $long_fields['60'] ) <= 15, true );

printf( "\n===============================\nPASS: %d   FAIL: %d\n===============================\n", $GLOBALS['pass'], $GLOBALS['fail'] );

exit( $GLOBALS['fail'] > 0 ? 1 : 0 );