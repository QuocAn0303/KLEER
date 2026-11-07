<?php
/* Template Name: Trang Chủ KLEER */
get_header(); 
?>

<main>
    <!-- 1. HERO BANNER -->
    <section class="hero" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/hero-bg.jpg');">
        <div class="hero__container container">
            <div class="hero__content">
                <p class="hero__subtitle">NƯỚC CẤT THỰC VẬT & LÂM SÀNG</p>
                <h1 class="hero__title">Làn da thuần khiết,<br>từ thiên nhiên<br>sâu thẳm.</h1>
                <p class="hero__desc">Công thức chăm sóc da unisex vượt thời gian, kết hợp giữa tinh túy của các loại thực vật rừng ôn đới và nghiên cứu sinh học lạnh tân tiến nhất.</p>
                <div class="hero__btn-group">
                    <a href="#" class="btn btn--white">KHÁM PHÁ NGAY</a>
                    <a href="#" class="btn btn--transparent">TÌM HIỂU KHOA HỌC</a>
                </div>
            </div>

            <div class="hero__showcase">
                <div class="hero__card hero__card--large">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-product-1.jpg" alt="Sản phẩm Kleer 1">
                </div>
                <div class="hero__col-small">
                    <div class="hero__card hero__card--small">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-product-2.jpg" alt="Sản phẩm Kleer 2">
                    </div>
                    <div class="hero__info-box">
                        <div class="hero__info-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M20.5 3.5C20.5 3.5 13.5 3.5 8.5 8.5C3.5 13.5 2.5 21.5 2.5 21.5C2.5 21.5 10.5 20.5 15.5 15.5C20.5 10.5 20.5 3.5 20.5 3.5Z" stroke="#B8D0BB" stroke-width="2"/>
                            </svg>
                            <h4>Khoa học chiết sinh học</h4>
                        </div>
                        <p>Giữ nguyên cấu trúc phân tử của thực vật hoạt tính.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. TRIẾT LÝ CỦA CHÚNG TÔI -->
    <section class="philosophy-sec ombre-bg-1">
        <div class="container philosophy__inner">
            <div class="philosophy__image-col">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/philosophy-img.jpg" alt="Triết lý Kleer" class="philosophy__img">
            </div>
            <div class="philosophy__content-col">
                <h2 class="philosophy__title">Triết lý của chúng tôi</h2>
                <p class="philosophy__lead">KLEER tin vào sức mạnh chữa lành thầm lặng của rừng xanh, kết tinh trong các giải pháp chăm sóc da tinh khiết nhất.</p>
                <hr class="philosophy__divider">
                <div class="philosophy__block">
                    <h3>Nguồn gốc tự nhiên & Minh bạch</h3>
                    <p>Từng thành phần trong chai thủy tinh mờ đều được thu mua một cách nhân văn và kiểm định nghiêm ngặt tại các phòng thí nghiệm chuyên sâu.</p>
                </div>
                <div class="philosophy__block">
                    <h3>Bền vững & Tối giản</h3>
                    <p>Chai thủy tinh của KLEER có thể tái chế vô hạn cùng hệ thống nhãn giấy hữu cơ tự phân hủy.</p>
                </div>
                <a href="#" class="btn btn--outline philosophy__btn">KHÁM PHÁ HÀNH TRÌNH</a>
            </div>
        </div>
    </section>

    <!-- 3. SẢN PHẨM NỔI BẬT (Đã tích hợp sẵn WooCommerce Query động) -->
    <section class="products-sec ombre-bg-2">
        <div class="container">
            <div class="products__header">
                <div class="products__title-group">
                    <h2 class="products__title">Sản phẩm nổi bật</h2>
                    <p class="products__subtitle">Hệ thống công thức điều trị tối giản, định hình sự phục hồi sinh học tự nhiên.</p>
                    <hr class="products__divider">
                </div>
                <a href="<?php echo function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : '#'; ?>" class="btn btn--outline products__btn-more">XEM TẤT CẢ SẢN PHẨM</a>
            </div>

            <div class="products__grid">
                <?php
                if (class_exists('WooCommerce')) {
                    $args = array(
                        'post_type'      => 'product',
                        'posts_per_page' => 4,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    );
                    $loop = new WP_Query($args);
                    if ($loop->have_posts()) :
                        while ($loop->have_posts()) : $loop->the_post();
                            global $product;
                            ?>
                            <div class="product-card">
                                <div class="product-card__img-wrap">
                                    <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                                </div>
                                <div class="product-card__body">
                                    <h3 class="product-card__title"><?php the_title(); ?></h3>
                                    <div class="product-card__price"><?php echo $product->get_price_html(); ?></div>
                                    <p class="product-card__desc"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                    <a href="?add-to-cart=<?php echo $product->get_id(); ?>" class="btn btn--white product-card__btn">THÊM VÀO GIỎ</a>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                }
                ?>
            </div>
        </div>
    </section>

    <!-- 4. BANNER CAM KẾT THƯƠNG HIỆU -->
    <section class="brand-banner" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/brand-banner-bg.jpg');">
        <div class="container">
            <div class="brand-banner__grid">
                <div class="brand-banner__item">
                    <h3 class="brand-banner__title">Thuần chay 100%</h3>
                    <p class="brand-banner__desc">Không chứa bất kỳ dẫn xuất từ động vật nào. Toàn bộ chuỗi cung ứng được kiểm nghiệm đạt chuẩn thuần chay quốc tế.</p>
                </div>
                <div class="brand-banner__item">
                    <h3 class="brand-banner__title">Không thử nghiệm động vật</h3>
                    <p class="brand-banner__desc">Tất cả các nghiên cứu kích ứng và đánh giá lâm sàng đều được thực hiện trên các mô tế bào nhân tạo tiên tiến.</p>
                </div>
                <div class="brand-banner__item">
                    <h3 class="brand-banner__title">Thành phần 100% tự nhiên</h3>
                    <p class="brand-banner__desc">Không hương liệu tổng hợp, paraben hay silicone. Chỉ sử dụng gốc bảo quản thực vật và tinh dầu tự nhiên.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. THÀNH PHẦN TINH TÚY -->
    <section class="ingredients-sec ombre-bg-3">
        <div class="container">
            <div class="ingredients__header">
                <h2 class="ingredients__title">Thành phần tinh túy</h2>
                <p class="ingredients__subtitle">Chúng tôi tuyển chọn thủ công các nguồn dược liệu bền vững có kiểm chứng lâm sàng tối ưu.</p>
                <hr class="ingredients__divider">
            </div>

            <div class="ingredients__grid">
                <div class="ingredient-card">
                    <div class="ingredient-card__img-wrap">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/ingredient-1.jpg" alt="Trà xanh cổ thụ">
                    </div>
                    <div class="ingredient-card__body">
                        <h3 class="ingredient-card__title">Trà xanh cổ thụ</h3>
                        <p class="ingredient-card__desc">Thu hoạch tại các vùng núi cao phía Bắc Việt Nam, mang hàm lượng chất chống oxy hóa Polyphenol cực đại để bảo vệ các liên kết collagen.</p>
                    </div>
                </div>
                <div class="ingredient-card">
                    <div class="ingredient-card__img-wrap">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/ingredient-2.jpg" alt="Vitamin C sinh học">
                    </div>
                    <div class="ingredient-card__body">
                        <h3 class="ingredient-card__title">Vitamin C sinh học</h3>
                        <p class="ingredient-card__desc">Phái sinh ổn định cao từ chiết xuất mận hoang dã Kakadu, làm sáng da, mờ thâm nám và tăng sinh elastin một cách bền vững.</p>
                    </div>
                </div>
                <div class="ingredient-card">
                    <div class="ingredient-card__img-wrap">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/ingredient-3.jpg" alt="Nha đam lên men">
                    </div>
                    <div class="ingredient-card__body">
                        <h3 class="ingredient-card__title">Nha đam lên men</h3>
                        <p class="ingredient-card__desc">Ứng dụng công nghệ lên men lạnh giúp tăng cường lượng khoáng chất và axit amin, thẩm sâu hơn gấp 3 lần so với chiết xuất thông thường.</p>
                    </div>
                </div>
                <div class="ingredient-card">
                    <div class="ingredient-card__img-wrap">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/ingredient-4.jpg" alt="Tinh dầu Jojoba vàng">
                    </div>
                    <div class="ingredient-card__body">
                        <h3 class="ingredient-card__title">Tinh dầu Jojoba vàng</h3>
                        <p class="ingredient-card__desc">Ép lạnh nguyên chất từ hạt jojoba bán hoang mạc, có cấu trúc tương thích gần như tuyệt đối với bã nhờn tự nhiên của người.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. TRẢI NGHIỆM TỪ KHÁCH HÀNG -->
    <section class="testimonials-sec ombre-bg-4">
        <div class="container">
            <div class="testimonials__header">
                <h2 class="testimonials__title">Trải nghiệm từ khách hàng</h2>
                <p class="testimonials__subtitle">Chia sẻ thực tế từ cộng đồng KLEER sau khi chuyển đổi sang liệu trình sinh học tối giản.</p>
                <hr class="testimonials__divider">
            </div>

            <div class="testimonials__grid">
                <div class="testimonial-card">
                    <div class="testimonial-card__stars">★★★★★</div>
                    <p class="testimonial-card__quote">"Hương đất và kết cấu ẩm mịn đã thay đổi hoàn toàn thói quen skincare ban đêm của tôi. Làn da thức dậy thực sự dịu mát, bớt hẳn các vết ửng đỏ sau một ngày dài bụi bặm."</p>
                    <div class="testimonial-card__author">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/avatar-1.jpg" alt="Minh Anh" class="testimonial-card__avatar">
                        <div class="testimonial-card__info">
                            <h4 class="testimonial-card__name">Minh Anh</h4>
                            <span class="testimonial-card__location">Hà Nội, Việt Nam</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-card__stars">★★★★★</div>
                    <p class="testimonial-card__quote">"Tôi thích sự tối giản về mặt thiết kế lẫn danh sách thành phần. Không hề có cảm giác bóng nhờn, tía tô giúp da tôi thông thoáng hơn hẳn giữa khí hậu nóng ẩm miền Nam."</p>
                    <div class="testimonial-card__author">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/avatar-2.jpg" alt="Trần Hải" class="testimonial-card__avatar">
                        <div class="testimonial-card__info">
                            <h4 class="testimonial-card__name">Trần Hải</h4>
                            <span class="testimonial-card__location">TP. Hồ Chí Minh</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. CHẨN ĐOÁN DA / SMART QUIZ -->
    <section class="quiz-sec ombre-bg-1">
        <div class="container">
            <div class="quiz__inner">
                <div class="quiz__content">
                    <span class="quiz__tag">CHẨN ĐOÁN DA · 5 CÂU HỎI</span>
                    <h2 class="quiz__title">Làn da của bạn đang cần gì?</h2>
                    <p class="quiz__desc">Trả lời 5 câu hỏi ngắn để nhận phác đồ chăm sóc da unisex tối giản, khoa học, kèm danh sách sản phẩm gợi ý.</p>
                    <div class="quiz__btn-group">
                        <a href="#" class="btn btn--white">CHẨN ĐOÁN DA NGAY</a>
                        <a href="#" class="btn btn--outline">TÌM HIỂU KHOA HỌC</a>
                    </div>
                </div>
                <div class="quiz__card">
                    <h3 class="quiz__card-title">Bạn sẽ nhận được</h3>
                    <ul class="quiz__list">
                        <li>Loại da và nguyên nhân được phân tích ngắn gọn</li>
                        <li>Routine 2, 3 hoặc 4 bước theo mong muốn</li>
                        <li>Thêm trọn bộ vào giỏ chỉ với một chạm</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. ĐĂNG KÝ NHẬN THÔNG TIN -->
    <section class="newsletter-sec" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/newsletter-bg.jpg');">
        <div class="container">
            <div class="newsletter__content">
                <h2 class="newsletter__title">Đăng ký nhận thông tin từ KLEER</h2>
                <p class="newsletter__desc">Nhận ngay cẩm nang chăm sóc da tối giản từ chuyên gia và 15% ưu đãi cho đơn hàng đầu tiên của bạn tại KLEER.</p>
                <form class="newsletter__form" action="#" method="POST" onsubmit="event.preventDefault();">
                    <input type="email" class="newsletter__input" placeholder="tên@domain.com" required>
                    <button type="submit" class="btn newsletter__btn">ĐĂNG KÝ</button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>