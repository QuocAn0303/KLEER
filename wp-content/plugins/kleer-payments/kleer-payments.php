<?php
/**
 * Plugin Name:       KLEER Payments
 * Plugin URI:        https://github.com/QuocAn0303/KLEER
 * Description:       COD + Chuyen khoan ngan hang (BACS) kem ma QR VietQR sinh tu dong theo tung don hang.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            KLEER Team
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kleer-payments
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KLEER_PAYMENTS_VERSION' ) ) {
	define( 'KLEER_PAYMENTS_VERSION', '1.0.0' );
}
if ( ! defined( 'KLEER_PAYMENTS_FILE' ) ) {
	define( 'KLEER_PAYMENTS_FILE', __FILE__ );
}
if ( ! defined( 'KLEER_PAYMENTS_DIR' ) ) {
	define( 'KLEER_PAYMENTS_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'KLEER_PAYMENTS_URL' ) ) {
	define( 'KLEER_PAYMENTS_URL', plugin_dir_url( __FILE__ ) );
}

require_once KLEER_PAYMENTS_DIR . 'includes/class-qr-payload.php';
require_once KLEER_PAYMENTS_DIR . 'includes/class-bank-account.php';
require_once KLEER_PAYMENTS_DIR . 'includes/class-gateways.php';
require_once KLEER_PAYMENTS_DIR . 'includes/class-thankyou-qr.php';

/**
 * Kich hoat mot lan: bat COD va BACS voi nhan tieng Viet.
 *
 * Chi chay khi admin bam "Activate", khong chay trong vong lap thuong,
 * de admin van toan quyen tu chinh sau do.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		Kleer_Payments\Gateways::apply_gateway_defaults();
	}
);

/**
 * Bo khoi dong plugin. Chi nap cac thanh phan WooCommerce khi WooCommerce san sang.
 */
final class Kleer_Payments {

	/**
	 * Hang doi voi container.
	 */
	private static ?self $instance = null;

	/**
	 * Danh sach thanh phan da khoi tao.
	 *
	 * @var array<int, string>
	 */
	private array $components = array();

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Hook vao plugins_loaded. WooCommerce co the chua san sang, nen phai cho.
	 */
	public static function boot(): void {
		add_action( 'plugins_loaded', array( self::instance(), 'init' ), 20 );
	}

	/**
	 * Khoi tao cac thanh phan nghiep vu.
	 */
	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woo_notice' ) );

			return;
		}

		$this->components[] = new Kleer_Payments\Gateways();
		$this->components[] = new Kleer_Payments\ThankYou_QR();

		foreach ( $this->components as $component ) {
			if ( method_exists( $component, 'register' ) ) {
				$component->register();
			}
		}
	}

	/**
	 * Canh bao khi thieu WooCommerce.
	 */
	public function render_missing_woo_notice(): void {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'KLEER Payments can WooCommerce de hoat dong. Hay kich hoat WooCommerce truoc.', 'kleer-payments' );
		echo '</p></div>';
	}
}

Kleer_Payments::boot();