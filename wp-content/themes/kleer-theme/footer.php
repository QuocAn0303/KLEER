<!-- FOOTER DÙNG CHUNG -->
<footer class="footer">
    <div class="container">
        <div class="footer__top">
            <div class="footer__brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="footer__logo">KLEER</a>
                <p class="footer__desc">
                    Công thức dưỡng da tối giản được kiểm nghiệm lâm sàng, tối ưu hóa từ chiết xuất rừng tự nhiên mang lại sự khỏe mạnh vượt thời gian.
                </p>
            </div>

            <div class="footer__nav-groups">
                <div class="footer__col">
                    <h4 class="footer__col-title">SẢN PHẨM</h4>
                    <ul class="footer__links">
                        <li><a href="#">Sữa rửa mặt</a></li>
                        <li><a href="#">Serum trẻ hóa</a></li>
                        <li><a href="#">Kem khóa ẩm</a></li>
                        <li><a href="#">Toner thảo mộc</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4 class="footer__col-title">HỖ TRỢ</h4>
                    <ul class="footer__links">
                        <li><a href="#">Chính sách giao hàng</a></li>
                        <li><a href="#">Đổi trả dễ dàng</a></li>
                        <li><a href="#">Tư vấn loại da</a></li>
                        <li><a href="#">Liên hệ hỗ trợ</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4 class="footer__col-title">VỀ KLEER</h4>
                    <ul class="footer__links">
                        <li><a href="#">Câu chuyện thương hiệu</a></li>
                        <li><a href="#">Cam kết bền vững</a></li>
                        <li><a href="#">Nghiên cứu lâm sàng</a></li>
                        <li><a href="#">Tuyển dụng</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="footer__divider">

        <div class="footer__bottom">
            <p class="footer__copyright">© <?php echo date('Y'); ?> KLEER Skincare. Tất cả quyền được bảo lưu.</p>
            <div class="footer__legal-links">
                <a href="#">Tiêu chuẩn riêng tư</a>
                <a href="#">Điều khoản sử dụng công thức</a>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?> <!-- Bắt buộc để WordPress nạp script footer -->
</body>
</html>