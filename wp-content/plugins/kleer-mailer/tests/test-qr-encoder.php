<?php
/**
 * Test QR_Encoder (PHP thuan, can GD).
 * Chay: php wp-content/plugins/kleer-mailer/tests/test-qr-encoder.php [thu-muc-xuat-png]
 * Neu truyen thu muc, ghi PNG de giai ma doc lap (xem tests/decode-qr-png.py).
 */
define( 'KLEER_MAILER_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/class-qr-encoder.php';

use Kleer_Mailer\QR_Encoder;

$out  = $argv[1] ?? '';
$pass = 0;
$fail = 0;
function check( string $n, bool $ok ): void {
	global $pass, $fail;
	$ok ? ++$pass : ++$fail;
	echo ( $ok ? 'PASS  ' : 'FAIL  ' ) . $n . "\n";
}

$sizes = array( 1, 14, 15, 26, 27, 42, 43, 62, 63, 84, 90, 106, 122, 152, 180, 213 );
foreach ( $sizes as $n ) {
	$data = substr( str_repeat( 'http://localhost/?page_id=9&order-received=22&key=wc_order_AbC123xyz', 4 ), 0, $n );
	$m    = QR_Encoder::matrix( $data );
	$sz   = count( $m );
	check( "len $n: ma tran vuong hop le (kich thuoc $sz)", 0 === ( $sz - 17 ) % 4 && $sz >= 21 );
	check( "len $n: o finder goc tren trai toi", true === $m[0][0] && true === $m[3][3] && false === $m[1][1] );
	if ( '' !== $out ) {
		@mkdir( $out, 0777, true );
		file_put_contents( "$out/qr_$n.png", QR_Encoder::png( $data ) );
		file_put_contents( "$out/qr_$n.txt", $data );
	}
}

$threw = false;
try {
	QR_Encoder::matrix( str_repeat( 'x', 214 ) );
} catch ( InvalidArgumentException $e ) {
	$threw = true;
}
check( 'tu choi du lieu > 213 byte', $threw );

$png = QR_Encoder::png( 'abc' );
check( 'PNG hop le (signature)', "\x89PNG" === substr( $png, 0, 4 ) );

echo "\n$pass PASS, $fail FAIL\n";
exit( $fail ? 1 : 0 );
