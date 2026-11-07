<?php
/**
 * Template email hoa don KLEER (HTML, responsive, tuong thich Outlook desktop + Gmail mobile).
 *
 * Nguyen tac tuong thich:
 *  - Bo cuc bang (table), thuoc tinh width/bgcolor/align + inline style (Outlook dung engine Word).
 *  - Nut "Xem don hang" co ban VML cho Outlook desktop, the <a> cho client con lai.
 *  - Media query chi de xep cot tren man hinh <= 600px; khong phu thuoc flex/grid/position.
 *  - Mau trung tinh (den / kem / xam da) cho phong cach Unisex.
 *
 * @var array<string, mixed> $d Du lieu tu Order_Email::build_data().
 */

defined( 'ABSPATH' ) || exit;

use Kleer_Mailer\Order_Email;

$ink   = '#111111';
$bone  = '#f3f1ec';
$stone = '#8a8780';
$line  = '#e4e1da';
$ok    = '#3f5a47';
$wait  = '#8a6a1f';
$tone  = 'ok' === $d['status_tone'] ? $ok : $wait;
$font  = "'Helvetica Neue',Helvetica,Arial,sans-serif";
$label = 'font-size:11px;letter-spacing:2px;text-transform:uppercase;color:' . $stone . ';';
?>
<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title><?php echo esc_html( $d['subject'] ); ?></title>
<!--[if gte mso 9]>
<xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
<![endif]-->
<style>
	body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
	table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
	img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
	a { color: <?php echo esc_html( $ink ); ?>; }
	@media only screen and (max-width: 600px) {
		.kl-wrap { width: 100% !important; }
		.kl-px { padding-left: 20px !important; padding-right: 20px !important; }
		.kl-col { display: block !important; width: 100% !important; box-sizing: border-box; }
		.kl-col-gap { padding-top: 20px !important; }
		.kl-center { text-align: center !important; }
		.kl-h1 { font-size: 24px !important; line-height: 30px !important; }
		.kl-btn a { display: block !important; }
	}
</style>
</head>
<body style="margin:0;padding:0;background-color:<?php echo esc_attr( $bone ); ?>;">

<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
	<?php echo esc_html( sprintf( 'Đơn hàng #%s · %s · %s', $d['number'], Order_Email::money( $d['total'] ), $d['status_label'] ) ); ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="<?php echo esc_attr( $bone ); ?>" style="background-color:<?php echo esc_attr( $bone ); ?>;">
<tr><td align="center" style="padding:24px 12px;">

<!--[if mso]>
<table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td>
<![endif]-->
<table role="presentation" class="kl-wrap" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;font-family:<?php echo esc_attr( $font ); ?>;color:<?php echo esc_attr( $ink ); ?>;">

	<!-- Thuong hieu -->
	<tr><td class="kl-px" align="center" bgcolor="<?php echo esc_attr( $ink ); ?>" style="background-color:<?php echo esc_attr( $ink ); ?>;padding:28px 32px;">
		<div style="font-family:<?php echo esc_attr( $font ); ?>;font-size:26px;line-height:30px;font-weight:bold;letter-spacing:8px;color:#ffffff;"><?php echo esc_html( strtoupper( $d['store_short'] ) ); ?></div>
		<div style="font-family:<?php echo esc_attr( $font ); ?>;font-size:10px;line-height:16px;letter-spacing:3px;color:#b9b6ae;padding-top:6px;">SKINCARE CHO MỌI LÀN DA</div>
	</td></tr>

	<!-- Trang thai + tieu de -->
	<tr><td class="kl-px" style="padding:36px 40px 8px 40px;font-family:<?php echo esc_attr( $font ); ?>;">
		<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
			<td bgcolor="<?php echo esc_attr( $tone ); ?>" style="background-color:<?php echo esc_attr( $tone ); ?>;padding:5px 12px;font-family:<?php echo esc_attr( $font ); ?>;font-size:11px;line-height:14px;letter-spacing:2px;color:#ffffff;text-transform:uppercase;"><?php echo esc_html( $d['status_label'] ); ?></td>
		</tr></table>
		<h1 class="kl-h1" style="margin:18px 0 10px 0;font-family:<?php echo esc_attr( $font ); ?>;font-size:30px;line-height:36px;font-weight:bold;color:<?php echo esc_attr( $ink ); ?>;"><?php echo esc_html( $d['heading'] ); ?></h1>
		<p style="margin:0;font-family:<?php echo esc_attr( $font ); ?>;font-size:15px;line-height:24px;color:#4a4842;">
			<?php echo esc_html( 'Xin chào ' . ( '' !== $d['customer'] ? $d['customer'] : 'bạn' ) . ', ' . mb_strtolower( mb_substr( $d['intro'], 0, 1 ) ) . mb_substr( $d['intro'], 1 ) ); ?>
		</p>
	</td></tr>

	<!-- Thong tin don -->
	<tr><td class="kl-px" style="padding:24px 40px 8px 40px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid <?php echo esc_attr( $line ); ?>;border-bottom:1px solid <?php echo esc_attr( $line ); ?>;">
		<tr>
			<td class="kl-col" width="33%" valign="top" style="padding:16px 0;font-family:<?php echo esc_attr( $font ); ?>;">
				<div style="<?php echo esc_attr( $label ); ?>">Mã đơn</div>
				<div style="font-size:16px;line-height:24px;font-weight:bold;padding-top:4px;">#<?php echo esc_html( $d['number'] ); ?></div>
			</td>
			<td class="kl-col" width="33%" valign="top" style="padding:16px 0;font-family:<?php echo esc_attr( $font ); ?>;">
				<div style="<?php echo esc_attr( $label ); ?>">Ngày đặt</div>
				<div style="font-size:16px;line-height:24px;padding-top:4px;"><?php echo esc_html( $d['date'] ); ?></div>
			</td>
			<td class="kl-col" width="34%" valign="top" style="padding:16px 0;font-family:<?php echo esc_attr( $font ); ?>;">
				<div style="<?php echo esc_attr( $label ); ?>">Thanh toán</div>
				<div style="font-size:16px;line-height:24px;padding-top:4px;"><?php echo esc_html( $d['payment'] ); ?></div>
			</td>
		</tr>
		</table>
	</td></tr>

	<!-- San pham -->
	<tr><td class="kl-px" style="padding:24px 40px 0 40px;font-family:<?php echo esc_attr( $font ); ?>;">
		<div style="<?php echo esc_attr( $label ); ?>padding-bottom:8px;">Chi tiết đơn hàng</div>
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
		<?php foreach ( $d['items'] as $it ) : ?>
		<tr>
			<?php if ( '' !== $it['thumb_cid'] ) : ?>
			<td width="76" valign="top" style="padding:14px 14px 14px 0;border-bottom:1px solid <?php echo esc_attr( $line ); ?>;">
				<img src="cid:<?php echo esc_attr( $it['thumb_cid'] ); ?>" width="62" height="62" alt="<?php echo esc_attr( $it['name'] ); ?>" style="display:block;width:62px;height:62px;background-color:<?php echo esc_attr( $bone ); ?>;">
			</td>
			<?php endif; ?>
			<td valign="top" style="padding:14px 8px 14px 0;border-bottom:1px solid <?php echo esc_attr( $line ); ?>;font-family:<?php echo esc_attr( $font ); ?>;">
				<div style="font-size:15px;line-height:22px;font-weight:bold;"><?php echo esc_html( $it['name'] ); ?></div>
				<?php if ( '' !== $it['variation'] ) : ?>
				<div style="font-size:12px;line-height:18px;color:<?php echo esc_attr( $stone ); ?>;"><?php echo esc_html( $it['variation'] ); ?></div>
				<?php endif; ?>
				<?php if ( '' !== $it['sku'] ) : ?>
				<div style="font-size:12px;line-height:18px;color:<?php echo esc_attr( $stone ); ?>;">SKU: <?php echo esc_html( $it['sku'] ); ?></div>
				<?php endif; ?>
				<div style="font-size:12px;line-height:18px;color:<?php echo esc_attr( $stone ); ?>;"><?php echo esc_html( Order_Email::money( $it['unit'] ) . ' × ' . $it['qty'] ); ?></div>
			</td>
			<td align="right" valign="top" nowrap="nowrap" style="padding:14px 0;border-bottom:1px solid <?php echo esc_attr( $line ); ?>;font-family:<?php echo esc_attr( $font ); ?>;font-size:15px;line-height:22px;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $it['total'] ) ); ?></td>
		</tr>
		<?php endforeach; ?>
		</table>
	</td></tr>

	<!-- Tong tien -->
	<tr><td class="kl-px" style="padding:12px 40px 8px 40px;font-family:<?php echo esc_attr( $font ); ?>;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;line-height:22px;">
			<tr>
				<td style="padding:4px 0;color:#4a4842;">Tạm tính</td>
				<td align="right" style="padding:4px 0;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $d['subtotal'] ) ); ?></td>
			</tr>
			<?php if ( $d['discount'] > 0 ) : ?>
			<tr>
				<td style="padding:4px 0;color:#4a4842;">Giảm giá</td>
				<td align="right" style="padding:4px 0;white-space:nowrap;">-<?php echo esc_html( Order_Email::money( $d['discount'] ) ); ?></td>
			</tr>
			<?php endif; ?>
			<tr>
				<td style="padding:4px 0;color:#4a4842;">Vận chuyển<?php echo '' !== $d['shipping_via'] ? ' (' . esc_html( $d['shipping_via'] ) . ')' : ''; ?></td>
				<td align="right" style="padding:4px 0;white-space:nowrap;"><?php echo $d['shipping'] > 0 ? esc_html( Order_Email::money( $d['shipping'] ) ) : 'Miễn phí'; ?></td>
			</tr>
			<tr>
				<td style="padding:14px 0 0 0;border-top:2px solid <?php echo esc_attr( $ink ); ?>;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Tổng cộng</td>
				<td align="right" style="padding:14px 0 0 0;border-top:2px solid <?php echo esc_attr( $ink ); ?>;font-size:24px;line-height:28px;font-weight:bold;white-space:nowrap;"><?php echo esc_html( Order_Email::money( $d['total'] ) ); ?></td>
			</tr>
		</table>
	</td></tr>

	<?php if ( ! empty( $d['bank'] ) ) : ?>
	<!-- Chuyen khoan -->
	<tr><td class="kl-px" style="padding:20px 40px 0 40px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="<?php echo esc_attr( $bone ); ?>" style="background-color:<?php echo esc_attr( $bone ); ?>;">
		<tr><td style="padding:18px 20px;font-family:<?php echo esc_attr( $font ); ?>;font-size:14px;line-height:22px;">
			<div style="<?php echo esc_attr( $label ); ?>padding-bottom:6px;">Thông tin chuyển khoản</div>
			<?php echo esc_html( $d['bank']['bank'] ); ?><br>
			Số tài khoản: <strong><?php echo esc_html( $d['bank']['account'] ); ?></strong><br>
			Chủ tài khoản: <?php echo esc_html( $d['bank']['holder'] ); ?><br>
			Số tiền: <strong><?php echo esc_html( Order_Email::money( $d['total'] ) ); ?></strong><br>
			Nội dung: <strong><?php echo esc_html( $d['bank']['content'] ); ?></strong>
		</td></tr>
		</table>
	</td></tr>
	<?php endif; ?>

	<?php if ( '' !== $d['note'] ) : ?>
	<tr><td class="kl-px" style="padding:20px 40px 0 40px;font-family:<?php echo esc_attr( $font ); ?>;font-size:14px;line-height:22px;">
		<div style="<?php echo esc_attr( $label ); ?>padding-bottom:4px;">Ghi chú của bạn</div>
		<?php echo esc_html( $d['note'] ); ?>
	</td></tr>
	<?php endif; ?>

	<!-- Giao den + QR -->
	<tr><td class="kl-px" style="padding:28px 40px 8px 40px;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid <?php echo esc_attr( $line ); ?>;">
		<tr>
			<td class="kl-col" width="60%" valign="top" style="padding:24px 16px 0 0;font-family:<?php echo esc_attr( $font ); ?>;font-size:14px;line-height:22px;">
				<div style="<?php echo esc_attr( $label ); ?>padding-bottom:6px;">Giao đến</div>
				<?php echo wp_kses( $d['address'], array( 'br' => array() ) ); ?>
				<?php if ( '' !== $d['phone'] ) : ?><br>ĐT: <?php echo esc_html( $d['phone'] ); ?><?php endif; ?>
			</td>
			<?php if ( '' !== $d['qr_png'] ) : ?>
			<td class="kl-col kl-col-gap kl-center" width="40%" valign="top" align="right" style="padding:24px 0 0 0;font-family:<?php echo esc_attr( $font ); ?>;">
				<div style="<?php echo esc_attr( $label ); ?>padding-bottom:8px;">Tra cứu đơn</div>
				<img src="cid:<?php echo esc_attr( $d['qr_cid'] ); ?>" width="120" height="120" alt="Mã QR tra cứu đơn hàng #<?php echo esc_attr( $d['number'] ); ?>" style="display:inline-block;width:120px;height:120px;">
				<div style="font-size:11px;line-height:16px;color:<?php echo esc_attr( $stone ); ?>;padding-top:6px;">Quét mã để xem trạng thái đơn</div>
			</td>
			<?php endif; ?>
		</tr>
		</table>
	</td></tr>

	<!-- Nut -->
	<tr><td class="kl-px kl-btn" align="center" style="padding:28px 40px 12px 40px;">
		<!--[if mso]>
		<v:rect xmlns:v="urn:schemas-microsoft-com:vml" href="<?php echo esc_url( $d['lookup_url'] ); ?>" style="height:48px;width:240px;v-text-anchor:middle;" stroke="f" fillcolor="<?php echo esc_attr( $ink ); ?>">
			<w:anchorlock xmlns:w="urn:schemas-microsoft-com:office:word"/>
			<center style="color:#ffffff;font-family:Arial,sans-serif;font-size:13px;font-weight:bold;letter-spacing:2px;">XEM ĐƠN HÀNG</center>
		</v:rect>
		<![endif]-->
		<!--[if !mso]><!-->
		<a href="<?php echo esc_url( $d['lookup_url'] ); ?>" style="display:inline-block;background-color:<?php echo esc_attr( $ink ); ?>;color:#ffffff;font-family:<?php echo esc_attr( $font ); ?>;font-size:13px;line-height:48px;font-weight:bold;letter-spacing:2px;text-align:center;text-decoration:none;text-transform:uppercase;padding:0 36px;">Xem đơn hàng</a>
		<!--<![endif]-->
	</td></tr>

	<!-- Chan trang -->
	<tr><td class="kl-px" align="center" bgcolor="<?php echo esc_attr( $bone ); ?>" style="background-color:<?php echo esc_attr( $bone ); ?>;padding:24px 40px;font-family:<?php echo esc_attr( $font ); ?>;font-size:12px;line-height:19px;color:<?php echo esc_attr( $stone ); ?>;">
		Cần hỗ trợ? Trả lời trực tiếp email này<?php echo '' !== $d['support'] ? ' hoặc liên hệ ' . esc_html( $d['support'] ) : ''; ?>.<br>
		Bạn nhận email này vì đã đặt hàng tại <?php echo esc_html( $d['store'] ); ?>.
	</td></tr>

</table>
<!--[if mso]>
</td></tr></table>
<![endif]-->

</td></tr>
</table>
</body>
</html>
