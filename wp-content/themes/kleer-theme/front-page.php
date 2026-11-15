<?php
/* Template Name: Trang Chủ KLEER */
get_header();

$shop_url  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$quiz_url  = home_url('/quiz/');
$theme_uri = get_template_directory_uri();

$cards = [];
if (function_exists('wc_get_products') && class_exists('\\Kleer\\Support\\ProductCardPresenter')) {
    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => 12,
        'visibility' => 'visible',
        'orderby' => 'meta_value_num',
        'meta_key' => 'total_sales',
        'order'   => 'DESC',
    ]);

    if (is_array($products)) {
        foreach ($products as $product) {
            if ($product instanceof WC_Product) {
                $cards[] = \Kleer\Support\ProductCardPresenter::present($product);
            }
        }
    }
}

/**
 * Render một thẻ sản phẩm Hai Tông Màu chuẩn Apple Store.
 */
$render_card = static function (array $card, string $class = 'rf-recommcard-33') use ($shop_url): void {
    $id = (int) ($card['id'] ?? 0);
    $name = (string) ($card['name'] ?? 'Sản phẩm KLEER');
    $permalink = (string) ($card['permalink'] ?? $shop_url);
    $skin_types = array_map('strval', (array) ($card['skinTypes'] ?? []));
    $concern_slugs = array_map('strval', (array) ($card['concernSlugs'] ?? []));
    $step_slug = (string) ($card['stepSlug'] ?? '');
    $price = (float) ($card['price'] ?? 0);
    $price_label = (string) ($card['priceFormatted'] ?? ('Từ ' . number_format($price, 0, ',', '.') . 'đ'));
    $image = (string) ($card['image'] ?? '');
    ?>
    <article
        class="<?php echo esc_attr($class); ?>"
        style="--card-top-bg:<?php echo esc_attr((string) ($card['cardTopBg'] ?? '#d4e6d8')); ?>;--card-bottom-bg:<?php echo esc_attr((string) ($card['cardBottomBg'] ?? '#73957c')); ?>;--card-accent:<?php echo esc_attr((string) ($card['cardAccent'] ?? '#1c4627')); ?>"
        data-product-id="<?php echo esc_attr((string) $id); ?>"
        data-name="<?php echo esc_attr($name); ?>"
        data-price="<?php echo esc_attr((string) $price); ?>"
        data-skin-types="<?php echo esc_attr(implode(' ', $skin_types)); ?>"
        data-step="<?php echo esc_attr($step_slug); ?>"
        data-concern="<?php echo esc_attr(implode(' ', $concern_slugs)); ?>"
    >
        <div class="apple-card__info">
            <span class="apple-card__eyebrow"><?php echo esc_html((string) ($card['bestsellerTag'] ?? 'CÔNG THỨC NỔI BẬT')); ?></span>
            <h3 class="apple-card__title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($name); ?></a>
            </h3>
            <div class="apple-card__meta-row">
                <span class="apple-card__skin-chip"><?php echo esc_html((string) ($card['skinLabel'] ?? 'Mọi loại da')); ?></span>
                <span class="apple-card__price-top"><?php echo esc_html($price_label); ?></span>
            </div>
            <p class="apple-card__sensory"><?php echo esc_html((string) ($card['sensory'] ?? '')); ?></p>
        </div>
        <div class="apple-card__stage">
            <?php if ($image !== '') : ?>
                <a href="<?php echo esc_url($permalink); ?>"><img class="apple-card__img" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr((string) ($card['imageAlt'] ?? $name)); ?>" loading="lazy"></a>
            <?php endif; ?>
            <span class="apple-card__inci-pill"><?php echo esc_html((string) ($card['inciBadge'] ?? 'Chiết xuất thực vật')); ?></span>
            <?php if ($id > 0) : ?>
                <button
                    type="button"
                    class="apple-card__circle-btn"
                    data-add-cart="<?php echo esc_attr((string) $id); ?>"
                    aria-label="<?php echo esc_attr(sprintf('Thêm %s vào Routine', $name)); ?>"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            <?php else : ?>
                <a class="apple-card__circle-btn" href="<?php echo esc_url($shop_url); ?>" aria-label="<?php echo esc_attr(sprintf('Xem %s', $name)); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </article>
    <?php
};

if ($cards && !array_filter($products, static fn ($p) => (int) $p->get_total_sales() > 0)) {
    $products = wc_get_products(['status' => 'publish', 'limit' => 12, 'visibility' => 'visible', 'orderby' => 'date', 'order' => 'DESC']);
    usort($products, static fn ($a, $b) => ($b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0) <=> ($a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0));
    $cards = array_map(static fn ($p) => \Kleer\Support\ProductCardPresenter::present($p), $products);
}
$shelf_cards = array_slice($cards, 0, 8);
$catalog_cards = [];
$selected_ids = [];
$steps = get_terms(['taxonomy' => 'pa_routine_step', 'hide_empty' => true]);
if (function_exists('wc_get_products') && class_exists('\\Kleer\\Support\\ProductCardPresenter') && !is_wp_error($steps)) {
    foreach ($steps as $step) {
        $ids = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'tax_query' => [['taxonomy' => 'pa_routine_step', 'field' => 'term_id', 'terms' => $step->term_id]]]);
        $candidate = $ids ? wc_get_product($ids[0]) : false;
        if ($candidate && $candidate->is_visible() && !in_array($candidate->get_id(), $selected_ids, true)) {
            $catalog_cards[] = \Kleer\Support\ProductCardPresenter::present($candidate);
            $selected_ids[] = $candidate->get_id();
        }
        if (count($catalog_cards) >= 6) { break; }
    }
}
foreach ($cards as $card) {
    if (count($catalog_cards) >= 6) { break; }
    if (!in_array($card['id'], $selected_ids, true)) { $catalog_cards[] = $card; $selected_ids[] = $card['id']; }
}
?>
<main class="home">
    <!-- 1. HERO -->
    <section class="hero" style="background-image: url('<?php echo esc_url($theme_uri . '/assets/images/hero-bg.jpg'); ?>');">
        <div class="hero__container container">
            <div class="hero__content">
                <p class="hero__subtitle">NƯỚC CẤT THỰC VẬT &amp; LÂM SÀNG</p>
                <h1 class="hero__title">Làn da thuần khiết,<br>từ thiên nhiên<br>sâu thẳm.</h1>
                <p class="hero__desc">Công thức chăm sóc da unisex vượt thời gian, kết hợp giữa tinh túy của các loại thực vật rừng ôn đới và nghiên cứu sinh học lạnh tân tiến nhất.</p>
                <div class="hero__btn-group">
                    <a href="<?php echo esc_url($shop_url); ?>" class="btn btn--white">KHÁM PHÁ NGAY</a>
                    <a href="#quiz-sanctuary-sec" class="btn btn--transparent">CHẨN ĐOÁN DA</a>
                </div>
            </div>
            <div class="hero__showcase">
                <div class="hero__card hero__card--large">
                    <img src="<?php echo esc_url($cards[0]['image'] ?? wc_placeholder_img_src('large')); ?>" alt="Sản phẩm KLEER">
                </div>
                <div class="hero__col-small">
                    <div class="hero__card hero__card--small">
                        <img src="<?php echo esc_url($cards[1]['image'] ?? wc_placeholder_img_src('large')); ?>" alt="Sản phẩm KLEER">
                    </div>
                    <div class="hero__info-box">
                        <div class="hero__info-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20.5 3.5C20.5 3.5 13.5 3.5 8.5 8.5C3.5 13.5 2.5 21.5 2.5 21.5C2.5 21.5 10.5 20.5 15.5 15.5C20.5 10.5 20.5 3.5 20.5 3.5Z" stroke="#B8D0BB" stroke-width="2"/>
                                <path d="M2.5 21.5L11.5 12.5" stroke="#B8D0BB" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <h3>Khoa học chiết sinh học</h3>
                        </div>
                        <p>Giữ nguyên cấu trúc phân tử của thực vật hoạt tính.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. TIÊU ĐỀ HAI TÔNG + THANH LỌC LOẠI DA -->
    <section class="store-intro">
        <div class="container">
            <div class="store-intro__row">
                <h2 class="apple-two-tone-heading">
                    Chăm sóc da đúng cách.<br>
                    <span>Bắt đầu từ làn da của chính bạn.</span>
                </h2>
                <div class="store-intro__specialist glass-light">
                    <span class="specialist-badge" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M20.5 3.5C20.5 3.5 13.5 3.5 8.5 8.5C3.5 13.5 2.5 21.5 2.5 21.5C2.5 21.5 10.5 20.5 15.5 15.5C20.5 10.5 20.5 3.5 20.5 3.5Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M2.5 21.5L11.5 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <p>Tư vấn bởi chuyên gia da liễu KLEER.<br>Không cần đăng nhập.</p>
                </div>
            </div>

            <div class="chapternav-bar" role="group" aria-label="Lọc nhanh theo loại da">
                <button type="button" class="chapternav-pill active" data-skin-filter="all">Tất cả</button>
                <?php $skin_terms = get_terms(['taxonomy' => 'pa_skin_type', 'hide_empty' => true]); if (!is_wp_error($skin_terms)): foreach ($skin_terms as $skin_term): ?>
                <button type="button" class="chapternav-pill" data-skin-filter="<?php echo esc_attr($skin_term->slug); ?>"><?php echo esc_html($skin_term->name); ?></button>
                <?php endforeach; endif; ?>
</div>
        </div>
    </section>

    <!-- 3. KỆ SẢN PHẨM NỔI BẬT / BÁN CHẠY -->
    <section id="shelf-sec" class="shelf-section">
        <div class="container">
            <div class="shelf-header">
                <div>
                    <h2 class="shelf-header__title">
                        Sản phẩm nổi bật.<br>
                        <span class="shelf-header__subtitle">Được yêu thích và lựa chọn nhiều nhất tại KLEER.</span>
                    </h2>
                </div>
                <div class="paddlenav" aria-label="Điều hướng kệ sản phẩm">
                    <button type="button" class="paddlenav-btn" id="shelf-prev" aria-label="Sản phẩm trước">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 5L8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" class="paddlenav-btn" id="shelf-next" aria-label="Sản phẩm tiếp theo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="rf-cards-scroller" id="shelf-scroller">
                <a class="rf-ccard-40 subpage-panel forest-panel" href="<?php echo esc_url($quiz_url); ?>"><h3>Làn da của bạn cần gì?</h3><p>Chẩn đoán da với 6 câu hỏi để khám phá Routine phù hợp.</p><span>Bắt đầu →</span></a>
                <?php foreach ($shelf_cards as $card) : ?>
                    <?php $render_card($card, 'rf-recommcard-33'); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 4. KHÔNG GIAN CHẨN ĐOÁN DA 6 CÂU -->
    <?php get_template_part('template-parts/quiz-sanctuary'); ?>

    <!-- 5. TRỌN BỘ LIỆU TRÌNH -->
    <section id="catalog-sec" class="catalog-section">
        <div class="container">
            <div class="catalog-header">
                <div>
                    <h2 class="catalog-header__title">Trọn bộ liệu trình.</h2>
                    <p class="catalog-header__subtitle">Làm sạch dịu nhẹ, cân bằng sâu và phục hồi màng ẩm tự nhiên.</p>
                </div>
            </div>

            <div class="catalog-toolbar">
                <label class="visuallyhidden" for="step-filter-select">Lọc theo bước Routine</label>
                <select id="step-filter-select" class="catalog-select">
                    <option value="">Mọi bước Routine</option>
                    <option value="makeup_remover">Tẩy trang</option>
                    <option value="cleanser">Làm sạch</option>
                    <option value="toner">Toner</option>
                    <option value="serum">Serum</option>
                    <option value="treatment">Đặc trị</option>
                    <option value="moisturizer">Dưỡng ẩm</option>
                    <option value="sunscreen">Chống nắng</option>
                </select>

                <label class="visuallyhidden" for="concern-filter-select">Lọc theo vấn đề da</label>
                <select id="concern-filter-select" class="catalog-select">
                    <option value="">Mọi vấn đề da</option>
                    <option value="acne">Mụn &amp; bít tắc</option>
                    <option value="pores">Lỗ chân lông</option>
                    <option value="dark_spots">Thâm &amp; không đều màu</option>
                    <option value="hydration">Thiếu ẩm</option>
                    <option value="aging">Lão hóa</option>
                    <option value="soothing">Kích ứng</option>
                </select>
            </div>

            <p><a href="<?php echo esc_url($shop_url); ?>">Xem tất cả sản phẩm tại Cửa hàng →</a></p>
            <div class="bento-grid" id="bento-catalog-grid">
                <?php foreach ($catalog_cards as $card) : ?>
                    <?php $render_card($card, 'product-card'); ?>
                <?php endforeach; ?>
                <div class="catalog-empty" id="catalog-empty" hidden>Không tìm thấy sản phẩm phù hợp với bộ lọc.</div>
            </div>
        </div>
    </section>

    <!-- 6. ĐÁNH GIÁ KHÁCH HÀNG -->
    <section class="testimonials-sec">
        <div class="container">
            <div class="testimonials__header">
                <h2 class="testimonials__title">Trải nghiệm từ khách hàng</h2>
                <p class="testimonials__subtitle">Chia sẻ thực tế từ cộng đồng KLEER sau khi chuyển sang liệu trình sinh học tối giản.</p>
            </div>
            <div class="testimonials__grid">
                <article class="testimonial-card">
                    <div class="testimonial-card__stars" aria-label="5 trên 5 sao">★★★★★</div>
                    <p class="testimonial-card__quote">"Hương đất và kết cấu ẩm mịn đã thay đổi hoàn toàn thói quen skincare ban đêm của tôi. Làn da thức dậy thực sự dịu mát, bớt hẳn các vết ửng đỏ sau một ngày dài bụi bặm."</p>
                    <div class="testimonial-card__author">
                        <div class="testimonial-card__avatar">MA</div>
                        <div>
                            <h3 class="testimonial-card__name">Minh Anh</h3>
                            <span class="testimonial-card__location">Hà Nội, Việt Nam</span>
                        </div>
                    </div>
                </article>
                <article class="testimonial-card">
                    <div class="testimonial-card__stars" aria-label="5 trên 5 sao">★★★★★</div>
                    <p class="testimonial-card__quote">"Tôi thích sự tối giản về mặt thiết kế lẫn danh sách thành phần. Không hề có cảm giác bóng nhờn, tía tô giúp da tôi thông thoáng hơn hẳn giữa khí hậu nóng ẩm miền Nam."</p>
                    <div class="testimonial-card__author">
                        <div class="testimonial-card__avatar">TH</div>
                        <div>
                            <h3 class="testimonial-card__name">Trần Hải</h3>
                            <span class="testimonial-card__location">TP. Hồ Chí Minh</span>
                        </div>
                    </div>
                </article>
                <article class="testimonial-card">
                    <div class="testimonial-card__stars" aria-label="5 trên 5 sao">★★★★★</div>
                    <p class="testimonial-card__quote">"Bài chẩn đoán da giúp tôi chọn đúng routine 3 bước cho làn da nhạy cảm. Sau sáu tuần, các nốt mẩn đỏ giảm rõ rệt và da không còn căng rát khi rửa mặt."</p>
                    <div class="testimonial-card__author">
                        <div class="testimonial-card__avatar">NL</div>
                        <div>
                            <h3 class="testimonial-card__name">Ngọc Linh</h3>
                            <span class="testimonial-card__location">Đà Nẵng, Việt Nam</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
