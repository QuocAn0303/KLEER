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
                        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Sữa rửa mặt</a></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Serum trẻ hóa</a></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Kem khóa ẩm</a></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Toner thảo mộc</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4 class="footer__col-title">HỖ TRỢ</h4>
                    <ul class="footer__links">
                        <li><a href="<?php echo esc_url(home_url('/chinh-sach-giao-hang/')); ?>">Chính sách giao hàng</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Đổi trả dễ dàng</a></li>
                        <li><a href="<?php echo esc_url(home_url('/quiz/')); ?>">Tư vấn loại da</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Liên hệ hỗ trợ</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4 class="footer__col-title">VỀ KLEER</h4>
                    <ul class="footer__links">
                        <li><a href="<?php echo esc_url(home_url('/about/')); ?>">Câu chuyện thương hiệu</a></li>
                        <li><a href="<?php echo esc_url(home_url('/about/')); ?>">Cam kết bền vững</a></li>
                        <li><a href="<?php echo esc_url(home_url('/about/')); ?>">Nghiên cứu lâm sàng</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Tuyển dụng</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="footer__divider">

        <div class="footer__bottom">
            <p class="footer__copyright">© <?php echo esc_html(gmdate('Y')); ?> KLEER Skincare. Tất cả quyền được bảo lưu.</p>
            <div class="footer__legal-links">
                <a href="<?php echo esc_url(home_url('/chinh-sach-bao-mat/')); ?>">Tiêu chuẩn riêng tư</a>
                <a href="<?php echo esc_url(home_url('/dieu-khoan/')); ?>">Điều khoản sử dụng công thức</a>
            </div>
        </div>
    </div>
</footer>

<!-- NGĂN KÉO GIỎ HÀNG LIQUID GLASS -->
<div class="cart-drawer" id="kleer-cart-drawer" aria-hidden="true">
    <div class="cart-drawer__backdrop" data-cart-close></div>
    <aside class="cart-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="kleer-cart-title">
        <div class="cart-drawer__head">
            <h2 class="cart-drawer__title" id="kleer-cart-title">Giỏ hàng của bạn</h2>
            <button type="button" class="cart-drawer__close" data-cart-close aria-label="Đóng giỏ hàng">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="conflict-alert" id="kleer-cart-conflict" role="status" hidden></div>

        <div class="cart-drawer__items" id="kleer-cart-items">
            <p class="cart-drawer__empty">Giỏ hàng đang trống.</p>
        </div>

        <div class="cart-drawer__totals">
            <div class="cart-drawer__row">
                <span>Tạm tính</span>
                <span id="kleer-cart-subtotal">0đ</span>
            </div>
            <div class="cart-drawer__row cart-drawer__row--discount" id="kleer-cart-discount-row" hidden>
                <span id="kleer-cart-discount-label">Combo Routine 3+ bước (-15%)</span>
                <span id="kleer-cart-discount">-0đ</span>
            </div>
            <div class="cart-drawer__row cart-drawer__row--total">
                <span>Tổng cộng</span>
                <span id="kleer-cart-total">0đ</span>
            </div>
        </div>

        <a
            class="btn cart-drawer__checkout"
            id="kleer-cart-checkout"
            href="<?php echo esc_url(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')); ?>"
        >
            Tiến hành thanh toán
        </a>
    </aside>
</div>

<?php wp_footer(); ?>
</body>
</html>
