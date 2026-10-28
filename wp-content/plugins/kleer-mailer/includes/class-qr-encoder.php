<?php
/**
 * Bo ma hoa QR Code (ISO/IEC 18004) toi gian, thuan PHP.
 *
 * Chi ho tro: che do Byte, muc sua loi M, phien ban 1-10 (toi da 213 byte).
 * Khong phu thuoc WordPress; xuat PNG bang GD. Dung de nhung QR tra cuu don
 * vao email (email khong chay duoc JavaScript, SVG hay bi chan).
 *
 * @package Kleer_Mailer
 */

namespace Kleer_Mailer;

if ( ! defined( 'ABSPATH' ) && ! defined( 'KLEER_MAILER_TESTING' ) ) {
	exit;
}

final class QR_Encoder {

	/**
	 * Muc M: version => [ec codewords moi block, so block nhom 1, data cw nhom 1, so block nhom 2, data cw nhom 2].
	 */
	private const EC_M = array(
		1  => array( 10, 1, 16, 0, 0 ),
		2  => array( 16, 1, 28, 0, 0 ),
		3  => array( 26, 1, 44, 0, 0 ),
		4  => array( 18, 2, 32, 0, 0 ),
		5  => array( 24, 2, 43, 0, 0 ),
		6  => array( 16, 4, 27, 0, 0 ),
		7  => array( 18, 4, 31, 0, 0 ),
		8  => array( 22, 2, 38, 2, 39 ),
		9  => array( 22, 3, 36, 2, 37 ),
		10 => array( 26, 4, 43, 1, 44 ),
	);

	private const ALIGN = array(
		1  => array(),
		2  => array( 6, 18 ),
		3  => array( 6, 22 ),
		4  => array( 6, 26 ),
		5  => array( 6, 30 ),
		6  => array( 6, 34 ),
		7  => array( 6, 22, 38 ),
		8  => array( 6, 24, 42 ),
		9  => array( 6, 26, 46 ),
		10 => array( 6, 28, 50 ),
	);

	/**
	 * So byte toi da cua mot QR muc M, phien ban 10.
	 */
	public const MAX_BYTES = 213;

	/**
	 * Tao ma tran QR.
	 *
	 * @param string $data Du lieu (byte).
	 * @return array<int, array<int, bool>> Ma tran [hang][cot], true = o toi.
	 *
	 * @throws \InvalidArgumentException Khi du lieu qua dai.
	 */
	public static function matrix( string $data ): array {
		$bytes   = array_values( unpack( 'C*', $data ) ?: array() );
		$version = self::pick_version( count( $bytes ) );

		$codewords = self::interleave( self::data_codewords( $bytes, $version ), $version );

		return self::build_matrix( $codewords, $version );
	}

	/**
	 * Xuat PNG (chuoi nhi phan).
	 *
	 * @param string $data  Du lieu.
	 * @param int    $scale So pixel moi module.
	 * @param int    $quiet So module vung trang quanh QR (chuan: 4).
	 */
	public static function png( string $data, int $scale = 6, int $quiet = 4 ): string {
		$matrix = self::matrix( $data );
		$size   = count( $matrix );
		$dim    = ( $size + 2 * $quiet ) * $scale;

		$img   = imagecreate( $dim, $dim );
		$white = imagecolorallocate( $img, 255, 255, 255 );
		$black = imagecolorallocate( $img, 0, 0, 0 );
		imagefilledrectangle( $img, 0, 0, $dim, $dim, $white );

		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				if ( $matrix[ $y ][ $x ] ) {
					$px = ( $x + $quiet ) * $scale;
					$py = ( $y + $quiet ) * $scale;
					imagefilledrectangle( $img, $px, $py, $px + $scale - 1, $py + $scale - 1, $black );
				}
			}
		}

		ob_start();
		imagepng( $img );
		$png = (string) ob_get_clean();
		imagedestroy( $img );

		return $png;
	}

	private static function pick_version( int $length ): int {
		foreach ( self::EC_M as $version => $row ) {
			$capacity_bits = 8 * ( $row[1] * $row[2] + $row[3] * $row[4] );
			$count_bits    = $version <= 9 ? 8 : 16;
			if ( 4 + $count_bits + 8 * $length <= $capacity_bits ) {
				return $version;
			}
		}

		throw new \InvalidArgumentException( 'QR: du lieu qua dai (toi da ' . self::MAX_BYTES . ' byte).' );
	}

	/**
	 * @param array<int, int> $bytes Du lieu.
	 * @return array<int, int> Cac data codeword da dem.
	 */
	private static function data_codewords( array $bytes, int $version ): array {
		$row      = self::EC_M[ $version ];
		$capacity = $row[1] * $row[2] + $row[3] * $row[4];
		$bits     = array();

		self::append_bits( $bits, 0x4, 4 );
		self::append_bits( $bits, count( $bytes ), $version <= 9 ? 8 : 16 );
		foreach ( $bytes as $b ) {
			self::append_bits( $bits, $b, 8 );
		}

		self::append_bits( $bits, 0, min( 4, 8 * $capacity - count( $bits ) ) );
		while ( 0 !== count( $bits ) % 8 ) {
			$bits[] = 0;
		}

		$out = array();
		for ( $i = 0; $i < count( $bits ); $i += 8 ) {
			$v = 0;
			for ( $k = 0; $k < 8; $k++ ) {
				$v = ( $v << 1 ) | $bits[ $i + $k ];
			}
			$out[] = $v;
		}

		for ( $pad = 0xEC; count( $out ) < $capacity; $pad ^= 0xEC ^ 0x11 ) {
			$out[] = $pad;
		}

		return $out;
	}

	/**
	 * @param array<int, int> $bits Mang bit.
	 */
	private static function append_bits( array &$bits, int $value, int $count ): void {
		for ( $i = $count - 1; $i >= 0; $i-- ) {
			$bits[] = ( $value >> $i ) & 1;
		}
	}

	/**
	 * Chia block, tinh Reed-Solomon, xen ke.
	 *
	 * @param array<int, int> $data Data codewords.
	 * @return array<int, int>
	 */
	private static function interleave( array $data, int $version ): array {
		list( $ec_len, $g1, $d1, $g2, $d2 ) = self::EC_M[ $version ];

		$divisor = self::rs_divisor( $ec_len );
		$blocks  = array();
		$ecs     = array();
		$pos     = 0;

		for ( $i = 0; $i < $g1 + $g2; $i++ ) {
			$len      = $i < $g1 ? $d1 : $d2;
			$block    = array_slice( $data, $pos, $len );
			$pos     += $len;
			$blocks[] = $block;
			$ecs[]    = self::rs_remainder( $block, $divisor );
		}

		$result = array();
		$max    = max( $d1, $d2 );
		for ( $i = 0; $i < $max; $i++ ) {
			foreach ( $blocks as $block ) {
				if ( $i < count( $block ) ) {
					$result[] = $block[ $i ];
				}
			}
		}
		for ( $i = 0; $i < $ec_len; $i++ ) {
			foreach ( $ecs as $ec ) {
				$result[] = $ec[ $i ];
			}
		}

		return $result;
	}

	private static function gf_mul( int $x, int $y ): int {
		$z = 0;
		for ( $i = 7; $i >= 0; $i-- ) {
			$z  = ( $z << 1 ) ^ ( ( $z >> 7 ) * 0x11D );
			$z ^= ( ( $y >> $i ) & 1 ) * $x;
		}

		return $z & 0xFF;
	}

	/**
	 * @return array<int, int>
	 */
	private static function rs_divisor( int $degree ): array {
		$result            = array_fill( 0, $degree, 0 );
		$result[ $degree - 1 ] = 1;
		$root              = 1;

		for ( $i = 0; $i < $degree; $i++ ) {
			for ( $j = 0; $j < $degree; $j++ ) {
				$result[ $j ] = self::gf_mul( $result[ $j ], $root );
				if ( $j + 1 < $degree ) {
					$result[ $j ] ^= $result[ $j + 1 ];
				}
			}
			$root = self::gf_mul( $root, 2 );
		}

		return $result;
	}

	/**
	 * @param array<int, int> $data    Data.
	 * @param array<int, int> $divisor Da thuc sinh.
	 * @return array<int, int>
	 */
	private static function rs_remainder( array $data, array $divisor ): array {
		$result = array_fill( 0, count( $divisor ), 0 );

		foreach ( $data as $b ) {
			$factor = $b ^ array_shift( $result );
			$result[] = 0;
			foreach ( $divisor as $i => $coef ) {
				$result[ $i ] ^= self::gf_mul( $coef, $factor );
			}
		}

		return $result;
	}

	/**
	 * @param array<int, int> $codewords Du lieu da xen ke.
	 * @return array<int, array<int, bool>>
	 */
	private static function build_matrix( array $codewords, int $version ): array {
		$size = 17 + 4 * $version;
		$mod  = array_fill( 0, $size, array_fill( 0, $size, false ) );
		$fn   = array_fill( 0, $size, array_fill( 0, $size, false ) );

		$set = static function ( int $x, int $y, bool $dark ) use ( &$mod, &$fn, $size ): void {
			if ( $x >= 0 && $x < $size && $y >= 0 && $y < $size ) {
				$mod[ $y ][ $x ] = $dark;
				$fn[ $y ][ $x ]  = true;
			}
		};

		// Timing.
		for ( $i = 0; $i < $size; $i++ ) {
			$set( 6, $i, 0 === $i % 2 );
			$set( $i, 6, 0 === $i % 2 );
		}

		// Finder + separator.
		foreach ( array( array( 3, 3 ), array( $size - 4, 3 ), array( 3, $size - 4 ) ) as $c ) {
			for ( $dy = -4; $dy <= 4; $dy++ ) {
				for ( $dx = -4; $dx <= 4; $dx++ ) {
					$dist = max( abs( $dx ), abs( $dy ) );
					$set( $c[0] + $dx, $c[1] + $dy, 2 !== $dist && 4 !== $dist );
				}
			}
		}

		// Alignment.
		$pos = self::ALIGN[ $version ];
		$n   = count( $pos );
		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = 0; $j < $n; $j++ ) {
				if ( ( 0 === $i && 0 === $j ) || ( 0 === $i && $n - 1 === $j ) || ( $n - 1 === $i && 0 === $j ) ) {
					continue;
				}
				for ( $dy = -2; $dy <= 2; $dy++ ) {
					for ( $dx = -2; $dx <= 2; $dx++ ) {
						$set( $pos[ $i ] + $dx, $pos[ $j ] + $dy, 1 !== max( abs( $dx ), abs( $dy ) ) );
					}
				}
			}
		}

		// Giu cho vung format/version (ghi gia tri that sau khi chon mask).
		self::draw_format( $set, $size, 0, 0 );
		if ( $version >= 7 ) {
			self::draw_version( $set, $size, $version );
		}

		// Dat codeword theo duong zigzag.
		$total = count( $codewords ) * 8;
		$i     = 0;
		for ( $right = $size - 1; $right >= 1; $right -= 2 ) {
			if ( 6 === $right ) {
				$right = 5;
			}
			for ( $vert = 0; $vert < $size; $vert++ ) {
				for ( $j = 0; $j < 2; $j++ ) {
					$x      = $right - $j;
					$upward = 0 === ( ( $right + 1 ) & 2 );
					$y      = $upward ? $size - 1 - $vert : $vert;
					if ( ! $fn[ $y ][ $x ] && $i < $total ) {
						$mod[ $y ][ $x ] = 1 === ( ( $codewords[ $i >> 3 ] >> ( 7 - ( $i & 7 ) ) ) & 1 );
						++$i;
					}
				}
			}
		}

		// Chon mask co diem phat thap nhat.
		$best_mask    = 0;
		$best_penalty = PHP_INT_MAX;
		for ( $mask = 0; $mask < 8; $mask++ ) {
			$trial = self::apply_mask( $mod, $fn, $size, $mask );
			$pen   = self::penalty( $trial, $size );
			if ( $pen < $best_penalty ) {
				$best_penalty = $pen;
				$best_mask    = $mask;
			}
		}

		$mod = self::apply_mask( $mod, $fn, $size, $best_mask );

		$set2 = static function ( int $x, int $y, bool $dark ) use ( &$mod, $size ): void {
			if ( $x >= 0 && $x < $size && $y >= 0 && $y < $size ) {
				$mod[ $y ][ $x ] = $dark;
			}
		};
		self::draw_format( $set2, $size, 0, $best_mask );

		return $mod;
	}

	private static function draw_format( callable $set, int $size, int $ec_bits, int $mask ): void {
		$data = ( $ec_bits << 3 ) | $mask; // Muc M = 0.
		$rem  = $data;
		for ( $i = 0; $i < 10; $i++ ) {
			$rem = ( $rem << 1 ) ^ ( ( $rem >> 9 ) * 0x537 );
		}
		$bits = ( ( $data << 10 ) | $rem ) ^ 0x5412;

		$bit = static function ( int $i ) use ( $bits ): bool {
			return 1 === ( ( $bits >> $i ) & 1 );
		};

		for ( $i = 0; $i <= 5; $i++ ) {
			$set( 8, $i, $bit( $i ) );
		}
		$set( 8, 7, $bit( 6 ) );
		$set( 8, 8, $bit( 7 ) );
		$set( 7, 8, $bit( 8 ) );
		for ( $i = 9; $i < 15; $i++ ) {
			$set( 14 - $i, 8, $bit( $i ) );
		}

		for ( $i = 0; $i < 8; $i++ ) {
			$set( $size - 1 - $i, 8, $bit( $i ) );
		}
		for ( $i = 8; $i < 15; $i++ ) {
			$set( 8, $size - 15 + $i, $bit( $i ) );
		}
		$set( 8, $size - 8, true );
	}

	private static function draw_version( callable $set, int $size, int $version ): void {
		$rem = $version;
		for ( $i = 0; $i < 12; $i++ ) {
			$rem = ( $rem << 1 ) ^ ( ( $rem >> 11 ) * 0x1F25 );
		}
		$bits = ( $version << 12 ) | $rem;

		for ( $i = 0; $i < 18; $i++ ) {
			$dark = 1 === ( ( $bits >> $i ) & 1 );
			$a    = $size - 11 + $i % 3;
			$b    = intdiv( $i, 3 );
			$set( $a, $b, $dark );
			$set( $b, $a, $dark );
		}
	}

	/**
	 * @param array<int, array<int, bool>> $mod  Ma tran.
	 * @param array<int, array<int, bool>> $fn   Danh dau o chuc nang.
	 * @return array<int, array<int, bool>>
	 */
	private static function apply_mask( array $mod, array $fn, int $size, int $mask ): array {
		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				if ( $fn[ $y ][ $x ] ) {
					continue;
				}
				switch ( $mask ) {
					case 0:
						$inv = 0 === ( $x + $y ) % 2;
						break;
					case 1:
						$inv = 0 === $y % 2;
						break;
					case 2:
						$inv = 0 === $x % 3;
						break;
					case 3:
						$inv = 0 === ( $x + $y ) % 3;
						break;
					case 4:
						$inv = 0 === ( intdiv( $x, 3 ) + intdiv( $y, 2 ) ) % 2;
						break;
					case 5:
						$inv = 0 === $x * $y % 2 + $x * $y % 3;
						break;
					case 6:
						$inv = 0 === ( $x * $y % 2 + $x * $y % 3 ) % 2;
						break;
					default:
						$inv = 0 === ( ( $x + $y ) % 2 + $x * $y % 3 ) % 2;
				}
				if ( $inv ) {
					$mod[ $y ][ $x ] = ! $mod[ $y ][ $x ];
				}
			}
		}

		return $mod;
	}

	/**
	 * Diem phat (rut gon: quy tac 1 = day cung mau, 2 = khoi 2x2, 4 = can bang).
	 *
	 * @param array<int, array<int, bool>> $m Ma tran.
	 */
	private static function penalty( array $m, int $size ): int {
		$score = 0;
		$dark  = 0;

		for ( $y = 0; $y < $size; $y++ ) {
			$run_r = 1;
			$run_c = 1;
			for ( $x = 0; $x < $size; $x++ ) {
				$dark += $m[ $y ][ $x ] ? 1 : 0;
				if ( $x > 0 ) {
					if ( $m[ $y ][ $x ] === $m[ $y ][ $x - 1 ] ) {
						++$run_r;
						$score += 5 === $run_r ? 3 : ( $run_r > 5 ? 1 : 0 );
					} else {
						$run_r = 1;
					}
					if ( $m[ $x ][ $y ] === $m[ $x - 1 ][ $y ] ) {
						++$run_c;
						$score += 5 === $run_c ? 3 : ( $run_c > 5 ? 1 : 0 );
					} else {
						$run_c = 1;
					}
				}
				if ( $x < $size - 1 && $y < $size - 1 ) {
					$c = $m[ $y ][ $x ];
					if ( $c === $m[ $y ][ $x + 1 ] && $c === $m[ $y + 1 ][ $x ] && $c === $m[ $y + 1 ][ $x + 1 ] ) {
						$score += 3;
					}
				}
			}
		}

		$total = $size * $size;
		$k     = (int) ( abs( $dark * 20 - $total * 10 ) / $total ) ;

		return $score + $k * 10;
	}
}
