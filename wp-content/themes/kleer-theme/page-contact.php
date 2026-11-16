<?php defined('ABSPATH') || exit; get_header(); ?>
<main class="container kleer-subpage">
<h1 class="apple-two-tone-heading">Liên hệ &amp; Hỗ trợ. <span>Tư vấn da liễu cá nhân hóa và cấp mã hoàn tiền nhanh Store Credit trong 7 ngày.</span></h1>
<?php
$notices = ['contact_sent' => 'Yêu cầu tư vấn đã được gửi. KLEER sẽ liên hệ qua thông tin bạn cung cấp.', 'contact_error' => 'Chưa gửi được yêu cầu. Kiểm tra thông tin hoặc thử lại sau ít phút.', 'credit_received' => 'Đã tiếp nhận yêu cầu. Nếu đơn hàng đủ điều kiện, mã Store Credit sẽ được gửi đến email đặt hàng.'];
$notice = isset($_GET['kleer_notice']) && is_string($_GET['kleer_notice']) ? sanitize_key($_GET['kleer_notice']) : '';
if (isset($notices[$notice])): ?><p class="contact-notice" role="status"><?php echo esc_html($notices[$notice]); ?></p><?php endif; ?>
<div class="contact-grid">
<section class="subpage-panel"><h2>Gửi yêu cầu tư vấn làn da</h2>
<form class="contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
<input type="hidden" name="action" value="kleer_contact"><?php wp_nonce_field('kleer_contact'); ?>
<label>Họ tên<input name="name" required maxlength="120" autocomplete="name"></label>
<label>Email / Số điện thoại<input name="reply" required maxlength="150" autocomplete="email"></label>
<label>Loại da hiện tại<select name="skin"><option>Chưa xác định</option><?php $terms = get_terms(['taxonomy' => 'pa_skin_type', 'hide_empty' => false]); if (!is_wp_error($terms)): foreach ($terms as $term): ?><option><?php echo esc_html($term->name); ?></option><?php endforeach; endif; ?></select></label>
<label>Nội dung cần tư vấn<textarea name="message" required maxlength="5000"></textarea></label>
<button class="btn" type="submit">Gửi yêu cầu tư vấn</button></form></section>
<section class="subpage-panel"><h2>Hoàn tiền nhanh 7 ngày</h2><p>Store Credit bằng 100% giá trị đơn gốc, dùng một lần với email đặt hàng. Áp dụng cho đơn đã hoàn tất trong 7 ngày, chưa hoàn tiền. Vui lòng cung cấp mã vận đơn trả hàng.</p>
<form class="contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
<input type="hidden" name="action" value="kleer_credit"><?php wp_nonce_field('kleer_credit'); ?>
<label>Mã đơn hàng<input name="order_id" type="number" min="1" required></label>
<label>Email đặt hàng<input name="email" type="email" required autocomplete="email"></label>
<label>Mã vận đơn trả hàng<input name="tracking" required minlength="4" maxlength="100"></label>
<button class="btn" type="submit">Yêu cầu Store Credit</button></form>
<p>Mã được gửi riêng đến email đặt hàng sau khi kiểm tra điều kiện.</p></section>
</div></main>
<?php get_footer(); ?>
