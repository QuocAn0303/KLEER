<?php
/**
 * Cau hinh SMTP cho wp_mail() thong qua phpmailer_init.
 *
 * Moi thong so (ke ca mat khau) doc tu HANG SO trong wp-config.php, khong luu vao database:
 *   KLEER_SMTP_PROVIDER  'gmail' | 'sendgrid' | 'custom' (mac dinh custom)
 *   KLEER_SMTP_HOST, KLEER_SMTP_PORT, KLEER_SMTP_SECURE ('tls' | 'ssl' | '')
 *   KLEER_SMTP_USER, KLEER_SMTP_PASS
 *   KLEER_MAIL_FROM, KLEER_MAIL_FROM_NAME
 *
 * @package Kleer_Mailer
 */

namespace Kleer_Mailer;

defined( 'ABSPATH' ) || exit;

class SMTP_Config {

	/**
	 * Cau hinh san cho tung nha cung cap (user/pass van lay tu hang so).
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private const PRESETS = array(
		'gmail'    => array(
			'host'   => 'smtp.gmail.com',
			'port'   => 587,
			'secure' => 'tls',
		),
		// SendGrid: user luon la chuoi "apikey", mat khau la API key.
		'sendgrid' => array(
			'host'   => 'smtp.sendgrid.net',
			'port'   => 587,
			'secure' => 'tls',
			'user'   => 'apikey',
		),
	);

	public function register(): void {
		add_action( 'phpmailer_init', array( $this, 'configure' ), 10, 1 );
		add_filter( 'wp_mail_from', array( $this, 'filter_from' ), 20 );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_from_name' ), 20 );
		add_action( 'wp_mail_failed', array( $this, 'log_failure' ) );
	}

	/**
	 * Gom cau hinh hieu luc (preset + hang so ghi de).
	 *
	 * @return array{host: string, port: int, secure: string, user: string, pass: string}
	 */
	public function settings(): array {
		$provider = defined( 'KLEER_SMTP_PROVIDER' ) ? strtolower( (string) KLEER_SMTP_PROVIDER ) : 'custom';
		$preset   = self::PRESETS[ $provider ] ?? array();

		$host   = defined( 'KLEER_SMTP_HOST' ) ? (string) KLEER_SMTP_HOST : (string) ( $preset['host'] ?? '' );
		$port   = defined( 'KLEER_SMTP_PORT' ) ? (int) KLEER_SMTP_PORT : (int) ( $preset['port'] ?? 587 );
		$secure = defined( 'KLEER_SMTP_SECURE' ) ? strtolower( (string) KLEER_SMTP_SECURE ) : (string) ( $preset['secure'] ?? '' );
		$user   = defined( 'KLEER_SMTP_USER' ) ? (string) KLEER_SMTP_USER : (string) ( $preset['user'] ?? '' );
		$pass   = defined( 'KLEER_SMTP_PASS' ) ? (string) KLEER_SMTP_PASS : '';

		if ( ! in_array( $secure, array( 'tls', 'ssl', '' ), true ) ) {
			$secure = 'tls';
		}

		return array(
			'host'   => $host,
			'port'   => $port > 0 ? $port : 587,
			'secure' => $secure,
			'user'   => $user,
			'pass'   => $pass,
		);
	}

	public function is_configured(): bool {
		return '' !== $this->settings()['host'];
	}

	/**
	 * Gan SMTP vao PHPMailer. Khong cau hinh host thi de nguyen mac dinh cua WordPress.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer Doi tuong PHPMailer cua wp_mail.
	 */
	public function configure( $phpmailer ): void {
		if ( ! $this->is_configured() ) {
			return;
		}

		$s = $this->settings();

		$phpmailer->isSMTP();
		$phpmailer->Host     = $s['host'];
		$phpmailer->Port     = $s['port'];
		$phpmailer->SMTPAuth = '' !== $s['user'];
		$phpmailer->Username = $s['user'];
		$phpmailer->Password = $s['pass'];
		$phpmailer->SMTPSecure  = $s['secure'];
		$phpmailer->SMTPAutoTLS = '' !== $s['secure'];
		$phpmailer->Timeout     = 15;
		$phpmailer->CharSet     = 'UTF-8';

		// Envelope sender trung voi From de SPF/DMARC thang hang (giam nguy co vao spam).
		if ( '' !== $phpmailer->From ) {
			$phpmailer->Sender = $phpmailer->From;

			// Message-ID cung ten mien voi From (mac dinh WordPress dung ten may chu, vd. @localhost).
			$at = strrpos( $phpmailer->From, '@' );
			if ( false !== $at ) {
				$phpmailer->MessageID = sprintf( '<%s@%s>', wp_generate_uuid4(), substr( $phpmailer->From, $at + 1 ) );
			}
		}
	}

	public function filter_from( $from ) {
		return defined( 'KLEER_MAIL_FROM' ) && '' !== (string) KLEER_MAIL_FROM ? (string) KLEER_MAIL_FROM : $from;
	}

	public function filter_from_name( $name ) {
		return defined( 'KLEER_MAIL_FROM_NAME' ) && '' !== (string) KLEER_MAIL_FROM_NAME ? (string) KLEER_MAIL_FROM_NAME : $name;
	}

	/**
	 * Ghi log loi gui thu. Khong bao gio ghi mat khau.
	 *
	 * @param \WP_Error $error Loi tu wp_mail.
	 */
	public function log_failure( $error ): void {
		if ( ! $error instanceof \WP_Error ) {
			return;
		}

		$message = 'KLEER Mailer: gui email that bai - ' . $error->get_error_message();

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $message, array( 'source' => 'kleer-mailer' ) );
		} else {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
