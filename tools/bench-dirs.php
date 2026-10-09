<?php
/**
 * Do chi phi stat file cua tung thu muc plugin/theme de xac nhan nguyen nhan.
 * Dung 1 lan de chan doan, xoa sau khi dung xong.
 */

$root = __DIR__;

$targets = array(
	'wp-includes',
	'wp-content/plugins/woocommerce',
	'wp-content/plugins/akismet',
	'wp-content/themes/flatsome',
);

printf( "%-38s %8s %10s %12s\n", 'thu muc', 'so file', 'giay', 'ms/file' );
echo str_repeat( '-', 72 ) . "\n";

foreach ( $targets as $rel ) {
	$path = $root . '/' . $rel;

	if ( ! is_dir( $path ) ) {
		printf( "%-38s %8s\n", $rel, 'KHONG CO' );
		continue;
	}

	$files = array();
	$it    = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);
	foreach ( $it as $file ) {
		if ( $file->isFile() ) {
			$files[] = $file->getPathname();
			if ( count( $files ) >= 4000 ) {
				break;
			}
		}
	}

	$n = count( $files );
	if ( 0 === $n ) {
		continue;
	}

	$t = microtime( true );
	foreach ( $files as $f ) {
		@file_exists( $f );
	}
	$el = microtime( true ) - $t;

	printf( "%-38s %8d %10.3f %12.3f\n", $rel, $n, $el, $el / $n * 1000 );
}