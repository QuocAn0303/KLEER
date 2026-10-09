<?php
/**
 * Do hieu nang WordPress. Dung 1 lan de chan doan, xoa sau khi dung xong.
 *
 * Chay: php _prof.php <ini|disk|shortinit|full>
 */

$mode = isset( $argv[1] ) ? $argv[1] : 'full';
$root = __DIR__;

// ---------------------------------------------------------------- ini
if ( 'ini' === $mode ) {
	printf( "PHP %s\n", PHP_VERSION );
	printf( "Zend OPcache loaded : %s\n", extension_loaded( 'Zend OPcache' ) ? 'yes' : 'NO' );
	echo "\n";
	foreach (
		array(
			'opcache.enable',
			'opcache.enable_cli',
			'opcache.memory_consumption',
			'opcache.max_accelerated_files',
			'opcache.validate_timestamps',
			'realpath_cache_size',
			'realpath_cache_ttl',
			'memory_limit',
			'max_execution_time',
		) as $key
	) {
		printf( "%-30s %s\n", $key, ini_get( $key ) ?: '(unset)' );
	}
	exit;
}

// ---------------------------------------------------------------- disk
if ( 'disk' === $mode ) {
	echo "=== So file PHP trong tung thu muc ===\n";
	foreach ( array( 'wp-includes', 'wp-admin', 'wp-content' ) as $dir ) {
		$path = $root . '/' . $dir;
		$count = 0;
		if ( is_dir( $path ) ) {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ( $it as $file ) {
				if ( $file->isFile() ) {
					++$count;
				}
			}
		}
		printf( "%-14s %6d file\n", $dir, $count );
	}

	// Gom du danh file de do toc do file_exists().
	$files = array();
	$path  = $root . '/wp-includes';
	if ( is_dir( $path ) ) {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach ( $it as $file ) {
			if ( $file->isFile() ) {
				$files[] = $file->getPathname();
				if ( count( $files ) >= 3000 ) {
					break;
				}
			}
		}
	}
	$n = count( $files );

	$t = microtime( true );
	foreach ( $files as $f ) {
		@file_exists( $f );
	}
	$bind = microtime( true ) - $t;

	// Cung so luong file, nhung nam tren dia chua dung bi bind mount.
	$tmp = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$p = '/tmp/_sb_' . $i;
		@file_put_contents( $p, 'x' );
		$tmp[] = $p;
	}
	$t = microtime( true );
	foreach ( $tmp as $f ) {
		@file_exists( $f );
	}
	$local = microtime( true ) - $t;
	foreach ( $tmp as $f ) {
		@unlink( $f );
	}

	echo "\n=== Toc do file_exists() tren " . $n . " file ===\n";
	printf( "  bind mount (host Windows) : %7.3fs   %6.3f ms/file\n", $bind, $bind / $n * 1000 );
	printf( "  container-local /tmp      : %7.3fs   %6.3f ms/file\n", $local, $local / $n * 1000 );
	printf( "  => bind mount cham hon %.1f lan\n", $local > 0 ? $bind / $local : 0 );
	exit;
}

// ---------------------------------------------------------------- bootstrap
if ( 'shortinit' === $mode ) {
	define( 'SHORTINIT', true );
}

define( 'SAVEQUERIES', true );

$t0 = microtime( true );
define( 'WP_USE_THEMES', true );
require $root . '/wp-load.php';
$elapsed = microtime( true ) - $t0;

printf(
	"mode=%-10s bootstrap=%6.2fs  queries=%d  peak_mem=%.1fMB\n",
	$mode,
	$elapsed,
	$GLOBALS['wpdb']->num_queries,
	memory_get_peak_usage( true ) / 1048576
);

if ( ! empty( $GLOBALS['wpdb']->queries ) ) {
	$queries = $GLOBALS['wpdb']->queries;
	usort(
		$queries,
		static function ( $a, $b ) {
			return $b[1] <=> $a[1];
		}
	);

	$total = 0.0;
	foreach ( $queries as $row ) {
		$total += $row[1];
	}
	printf( "tong thoi gian truy van: %.2fs\n", $total );

	echo "--- 10 truy van cham nhat ---\n";
	foreach ( array_slice( $queries, 0, 10 ) as $row ) {
		$sql = preg_replace( '/\s+/', ' ', $row[0] );
		printf( "%8.3fs  %s\n", $row[1], substr( $sql, 0, 105 ) );
	}
}