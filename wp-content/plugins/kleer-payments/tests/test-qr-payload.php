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

echo "\n=== 2. CRC-16/CCITT-FALSE (init 0xFFFF) ===\n";
// Gia tri kiem chuan cua CRC-16/CCITT-FALSE voi chuoi "123456789" la 0x29B1.
check( 'check value 123456789', $qr->crc16( '123456789' ), '29B1' );
check( 'chuoi rong', $qr->crc16( '' ), 'FFFF' );
// Vector chinh thuc trong tai lieu NAPAS 247 (VietQR) va thu vien vietqr_gen.
check( 'vector NAPAS spec (F4E5)', $qr->crc16( '00020101021138570010A00000072701270006970403011200110123456780208QRIBFTTA53037045802VN6304' ), 'F4E5' );
check( 'vector vietqr_gen tinh (34A0)', $qr->crc16( '00020101021138540010A00000072701240006970407011096020919960208QRIBFTTA53037045802VN6304' ), '34A0' );

echo "\n=== 3. Cat ngan ===\n";
check( 'cat theo gioi han', $qr->truncate( 'ABCDEFG', 3 ), 'ABC' );
check( 'khong cat khi ngan gon', $qr->truncate( 'AB', 25 ), 'AB' );
check( 'gioi han 0', $qr->truncate( 'ABC', 0 ), '' );

echo "\n=== 4. Noi dung chuyen khoan ===\n";
check( 'KLEER + don 12345', $qr->build_transfer_content( 'KLEER', '12345' ), 'KLEER 12345' );
check( 'bo dau trong tien to', $qr->build_transfer_content( 'Kléer', 'DH-001' ), 'KLEER DH 001' );
check( 'cat toi da 25 ky tu', strlen( $qr->build_transfer_content( 'KLEER', '123456789012345678901234567890' ) ), 25 );

echo "\n=== 5. Payload QR day du (NAPAS 247, tag 38) ===\n";
$payload = $qr->build( 500000, '970436', '0123456789', 'KLEER 12345' );

// Gia tri doi chieu duoc sinh doc lap bang Python.
check(
	'khop vector tham chieu',
	$payload,
	'00020101021238540010A00000072701240006970436011001234567890208QRIBFTTA530370454065000005802VN62150811KLEER 123456304E52B'
);
check_true( 'bat dau bang 000201', 0 === strpos( $payload, '000201' ) );
check_true( 'co tag 01 dynamic (010212)', false !== strpos( $payload, '010212' ) );
check_true( 'ket thuc bang CRC hex', (bool) preg_match( '/6304[0-9A-F]{4}$/', $payload ) );

$fields = parse_qr( $payload );
check( 'tag 00 - payload format', $fields['00'], '01' );
check( 'tag 01 - initiation method', $fields['01'], '12' );
check( 'tag 53 - currency VND', $fields['53'], '704' );
check( 'tag 54 - so tien', $fields['54'], '500000' );
check( 'tag 58 - quoc gia', $fields['58'], 'VN' );
check_true( 'khong dung tag 26 cu', ! isset( $fields['26'] ) );

echo "\n=== 6. Kiem chung CRC gan nhat ===\n";
$body     = substr( $payload, 0, -4 );
$appended = substr( $payload, -4 );
check( 'CRC khop phan duoi cung', $qr->crc16( $body ), $appended );

echo "\n=== 7. Noi dung chuyen khoan nam trong Additional Data (62/08) ===\n";
check_true( 'tag 62 ton tai', isset( $fields['62'] ) );
$extra = parse_qr( '6200' . $fields['62'] );
check( '62/08 - noi dung CK', $extra['08'], 'KLEER 12345' );

echo "\n=== 8. Merchant Account (38) ===\n";
$merchant = parse_qr( '3800' . $fields['38'] );
check( '38/00 - AID NAPAS', $merchant['00'], 'A000000727' );
check( '38/02 - dich vu', $merchant['02'], 'QRIBFTTA' );
$beneficiary = parse_qr( '0100' . $merchant['01'] );
check( '38/01/00 - bank BIN', $beneficiary['00'], '970436' );
check( '38/01/01 - so tai khoan', $beneficiary['01'], '0123456789' );

echo "\n=== 9. Noi dung duoc chuan hoa truoc khi dua vao QR ===\n";
$dirty        = $qr->build( 150000, '970415', '9876543210', 'Đơn hàng Kléer #7788' );
$dirty_fields = parse_qr( $dirty );
$dirty_extra  = parse_qr( '6200' . $dirty_fields['62'] );
check( 'noi dung khong dau, viet hoa', $dirty_extra['08'], 'DON HANG KLEER 7788' );

$long       = $qr->build( 1000, '970422', '123456', str_repeat( 'A', 40 ) );
$long_extra = parse_qr( '6200' . parse_qr( $long )['62'] );
check( 'noi dung cat 25 ky tu', strlen( $long_extra['08'] ), 25 );

echo "\n=== 10. Tham so khong hop le phai bi tu choi ===\n";
$cases = array(
	'so tien 0'           => array( 0, '970436', '0123456789', 'X' ),
	'so tien am'          => array( -500000, '970436', '0123456789', 'X' ),
	'BIN 5 so'            => array( 1000, '97043', '0123456789', 'X' ),
	'BIN co chu'          => array( 1000, '97043A', '0123456789', 'X' ),
	'so tai khoan rong'   => array( 1000, '970436', '', 'X' ),
	'so tai khoan co chu' => array( 1000, '970436', '01234ABC', 'X' ),
	'so tai khoan 20 so'  => array( 1000, '970436', '12345678901234567890', 'X' ),
);
foreach ( $cases as $label => $args ) {
	$threw = false;
	try {
		$qr->build( ...$args );
	} catch ( InvalidArgumentException $e ) {
		$threw = true;
	}
	check( 'tu choi ' . $label, $threw, true );
}

printf( "\n===============================\nPASS: %d   FAIL: %d\n===============================\n", $GLOBALS['pass'], $GLOBALS['fail'] );

exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
