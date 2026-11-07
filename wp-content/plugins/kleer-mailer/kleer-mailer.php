<?php
/**
 * Plugin Name:       KLEER Mailer
 * Plugin URI:        https://github.com/QuocAn0303/KLEER
 * Description:       Cau hinh SMTP (Gmail App Password / SendGrid / Mailpit dev) va tu dong gui email hoa don HTML co ma QR tra cuu khi don hang chuyen sang Dang xu ly.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            KLEER Team
 * License:           GPL v2 or later
 * Text Domain:       kleer-mailer
 */

defined( 'ABSPATH' ) || exit;

define( 'KLEER_MAILER_VERSION', '1.0.0' );
define( 'KLEER_MAILER_DIR', plugin_dir_path( __FILE__ ) );

require_once KLEER_MAILER_DIR . 'includes/class-qr-encoder.php';
require_once KLEER_MAILER_DIR . 'includes/class-smtp-config.php';
require_once KLEER_MAILER_DIR . 'includes/class-order-email.php';

/**
 * SMTP khong can WooCommerce; email hoa don chi chay khi WooCommerce san sang.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		( new Kleer_Mailer\SMTP_Config() )->register();

		if ( class_exists( 'WooCommerce' ) ) {
			( new Kleer_Mailer\Order_Email() )->register();
		}
	},
	20
);
