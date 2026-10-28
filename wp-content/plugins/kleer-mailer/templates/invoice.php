<?php
/**
 * Template email hoa don (HTML, responsive).
 *
 * Dung bang + inline style de tuong thich Gmail/Outlook; media query cho man hinh nho.
 *
 * @var array<string, mixed> $d Du lieu tu Order_Email::build_data().
 */

defined( 'ABSPATH' ) || exit;

use Kleer_Mailer\Order_Email;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title><?php echo esc_html( $d['subject'] ); ?></title>
<style>
	body { margin: 0; padding: 0; background: #f4f4f2; }
	img { border: 0; display: block; }
	@media only screen and (max-width: 600px) {
		.kl-wrap { width: 100% !important; }
		.kl-pad { padding: 16px !important; }
		.kl-col { display: block !important; width: 100% !important; box-sizing: border-box; }
		.kl-qr { margin: 12px auto 0 !important; }
		.kl-h1 { font-size: 20px !important; }
	}
</style>
</head>
<body style="margin:0;padding:0;background:#f4f4f2;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
	<?php echo esc_html( sprintf( 'Đơn hàng #%s - %s', $d['number'], Order_Email::money( $d['total'] ) ) ); ?>
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f2;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" class="kl-wrap" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:8px;font-family:Arial,Helvetica,sans-serif;color:#222222;">

	<tr><td class="kl-pad" style="padding:24px 32px;border-bottom:1px solid #eeeeee;">
		<div style="font-size:22px;font-weight:bold;letter-spacing:2px;"><?php echo esc_html( $d['store'] ); ?></div>
	</td></tr>

	<tr><td class="kl-pad" style="padding:24px 32px 8px 32px;">
		<h1 class="kl-h1" style="margin:0 0 8px 0;font-size:24px;line-height:1.3;"><?php echo esc_html( $d['heading'] ); ?></h1>
		<p style="margin:0;font-size:15px;line-height:1.5;color:#555555;">
			<?php echo esc_html( 'Xin chào ' . ( '' !== $d['customer'] ? $d['customer'] : 'bạn' ) . ', ' . mb_strtolower( mb_substr( $d['intro'], 0, 1 ) ) . mb_substr( $d['intro'], 1 ) ); ?>
		</p>
	</td></tr>

	<tr><td class="kl-pad" style="padding:16px 32px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
		<tr>
			<td class="kl-col" valign="top" style="font-size:14px;line-height:1.6;">
				<strong>Hóa đơn #<?php echo esc_html( $d['number'] ); ?></strong><br>
				Ngày đặt: <?php echo esc_html( $d['date'] ); ?><br>
				Thanh toán: <?php echo esc_html( $d['payment'] ); ?><br>
				Email: <?php echo esc_html( $d['email'] ); ?>
				<?php if ( '' !== $d['phone'] ) : ?><br>Điện thoại: <?php echo esc_html( $d['phone'] ); ?><?php endif; ?>
			</td>
			<?php if ( '' !== $d['qr_png'] ) : ?>
			<td class="kl-col" valign="top" align="right" width="130">
				<img class="kl-qr" src="cid:<?php echo esc_attr( $d['qr_cid'] ); ?>" width="120" height="120" alt="Mã QR tra cứu đơn hàng #<?php echo esc_attr( $d['number'] ); ?>" style="width:120px;height:120px;">
				<div style="font-size:11px;color:#777777;text-align:center;width:120px;margin-top:4px;">Quét để tra cứu đơn</div>
			</td>
			<?php endif; ?>
		</tr>
		</table>
	</td></tr>

	<tr><td class="kl-pad" style="padding:8px 32px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
			<tr style="background:#fafafa;">
				<th align="left" style="padding:10px 8px;border-bottom:1px solid #dddddd;">Sản phẩm</th>
				<th align="center" style="padding:10px 8px;border-bottom:1px solid #dddddd;">SL</th>
				<th align="right" style="padding:10px 8px;border-bottom:1px solid #dddddd;">Thành tiền</th>
			</tr>
			<?php foreach ( $d['items'] as $it ) : ?>
			<tr>
				<td style="padding:10px 8px;border-bottom:1px solid #eeeeee;"><?php echo esc_html( $it['name'] ); ?></td>
				<td align="center" style="padding:10px 8px;border-bottom:1px solid #eeeeee;"><?php echo esc_html( (string) $it['qty'] ); ?></td>
				<td align="right" style="padding:10px 8px;border-bottom:1px solid #eeeeee;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $it['total'] ) ); ?></td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="2" align="right" style="padding:8px;color:#555555;">Tạm tính</td>
				<td align="right" style="padding:8px;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $d['subtotal'] ) ); ?></td>
			</tr>
			<?php if ( $d['shipping'] > 0 ) : ?>
			<tr>
				<td colspan="2" align="right" style="padding:8px;color:#555555;">Vận chuyển</td>
				<td align="right" style="padding:8px;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $d['shipping'] ) ); ?></td>
			</tr>
			<?php endif; ?>
			<?php if ( $d['discount'] > 0 ) : ?>
			<tr>
				<td colspan="2" align="right" style="padding:8px;color:#555555;">Giảm giá</td>
				<td align="right" style="padding:8px;white-space:nowrap;">-<?php echo esc_html( Order_Email::money( $d['discount'] ) ); ?></td>
			</tr>
			<?php endif; ?>
			<tr>
				<td colspan="2" align="right" style="padding:12px 8px;font-weight:bold;font-size:16px;border-top:2px solid #222222;">Tổng cộng</td>
				<td align="right" style="padding:12px 8px;font-weight:bold;font-size:16px;border-top:2px solid #222222;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $d['total'] ) ); ?></td>
			</tr>
		</table>
	</td></tr>

	<?php if ( ! empty( $d['bank'] ) ) : ?>
	<tr><td class="kl-pad" style="padding:8px 32px;">
		<div style="background:#fff8e5;border:1px solid #f0d98a;border-radius:6px;padding:14px;font-size:14px;line-height:1.6;">
			<strong>Thông tin chuyển khoản</strong><br>
			Ngân hàng: <?php echo esc_html( $d['bank']['bank'] ); ?><br>
			Số tài khoản: <strong><?php echo esc_html( $d['bank']['account'] ); ?></strong><br>
			Chủ tài khoản: <?php echo esc_html( $d['bank']['holder'] ); ?><br>
			Số tiền: <strong><?php echo esc_html( Order_Email::money( $d['total'] ) ); ?></strong><br>
			Nội dung: <strong><?php echo esc_html( $d['bank']['content'] ); ?></strong>
		</div>
	</td></tr>
	<?php endif; ?>

	<?php if ( '' !== $d['address'] ) : ?>
	<tr><td class="kl-pad" style="padding:8px 32px;font-size:14px;line-height:1.6;">
		<strong>Địa chỉ giao hàng</strong><br>
		<?php echo wp_kses( $d['address'], array( 'br' => array() ) ); ?>
	</td></tr>
	<?php endif; ?>

	<tr><td class="kl-pad" align="center" style="padding:20px 32px 8px 32px;">
		<a href="<?php echo esc_url( $d['lookup_url'] ); ?>" style="display:inline-block;background:#222222;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:14px;">Xem đơn hàng</a>
	</td></tr>

	<tr><td class="kl-pad" style="padding:16px 32px 24px 32px;font-size:12px;line-height:1.5;color:#888888;border-top:1px solid #eeeeee;">
		Bạn nhận được email này vì đã đặt hàng tại <?php echo esc_html( $d['store'] ); ?>. Nếu cần hỗ trợ, hãy trả lời trực tiếp email này.
	</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
