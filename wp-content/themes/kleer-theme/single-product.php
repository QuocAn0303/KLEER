<?php
/**
 * Template Name: Product Detail Page
 * Theme: KLEER Skincare
 */
get_header();

while ( have_posts() ) : the_post();
    global $product;
    $post_id = get_the_ID();
    
    // Ảnh chính & Thumbs
    $main_img_id  = $product->get_image_id();
    $main_img_url = $main_img_id ? wp_get_attachment_image_url($main_img_id, 'full') : get_template_directory_uri() . '/images/product-1.jpg';
    $attachment_ids = $product->get_gallery_image_ids();

    // Danh mục
    $terms = get_the_terms($post_id, 'product_cat');
    $cat_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : 'CHĂM SÓC DA MẶT';
?>

<main class="product-detail-page">
    <div class="container">
        
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a>
            <span class="breadcrumb__sep">&rsaquo;</span>
            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Sản phẩm</a>
            <span class="breadcrumb__sep">&rsaquo;</span>
            <span class="breadcrumb__current"><?php the_title(); ?></span>
        </nav>

        <div class="product-main-grid">
            
            <!-- Cột trái: Gallery (Gắn data-testid) -->
            <div class="product-gallery" data-testid="product-gallery">
                <div class="main-image-wrap">
                    <img id="mainProductImg" src="<?php echo esc_url($main_img_url); ?>" alt="<?php the_title_attribute(); ?>">
                </div>
                <div class="thumbnail-list">
                    <div class="thumb-item active" onclick="changeImage(this, '<?php echo esc_url($main_img_url); ?>')">
                        <img src="<?php echo esc_url($main_img_url); ?>" alt="Thumb Main">
                    </div>
                    <?php 
                    if ( $attachment_ids ) :
                        foreach ( array_slice($attachment_ids, 0, 3) as $attachment_id ) :
                            $thumb_url = wp_get_attachment_image_url($attachment_id, 'full');
                    ?>
                        <div class="thumb-item" onclick="changeImage(this, '<?php echo esc_url($thumb_url); ?>')">
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="Thumb">
                        </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            </div>

            <!-- Cột phải: Thông tin sản phẩm -->
            <div class="product-info">
                <span class="product-cat" data-testid="taxonomy-badge"><?php echo esc_html(strtoupper($cat_name)); ?></span>
                <h1 class="product-title"><?php the_title(); ?></h1>
                
                <div class="product-meta">
                    <span class="product-price"><?php echo $product->get_price_html(); ?></span>
                    <div class="product-rating">
                        <span class="stars">☆☆☆☆☆</span>
                        <span class="rating-text">(4.8/5 — 126 đánh giá)</span>
                    </div>
                </div>

                <div class="product-short-desc">
                    <?php echo apply_filters('woocommerce_short_description', $post->post_excerpt); ?>
                </div>

            

                <!-- Số lượng & Form Thêm vào giỏ -->
                <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
                    <div class="product-action-row">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                            <input type="text" id="qtyInput" name="quantity" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                        </div>
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="btn btn-add-cart">Thêm vào giỏ hàng</button>
                        <button type="button" class="btn btn-buy-now">Mua ngay</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- SẢN PHẨM LIÊN QUAN -->
    <section class="related-products-section">
        <div class="container">
            <h2 class="related-title">Sản phẩm liên quan</h2>
            <div class="related-grid">
                <?php
                $related_ids = wc_get_related_products($post_id, 4);
                if ( ! empty($related_ids) ) :
                    foreach ( $related_ids as $rel_id ) :
                        $rel_product = wc_get_product($rel_id);
                        $rel_img = get_the_post_thumbnail_url($rel_id, 'full') ?: get_template_directory_uri() . '/images/product-2.jpg';
                ?>
                    <article class="catalog-card">
                        <div class="catalog-card__img-wrap">
                            <a href="<?php echo get_permalink($rel_id); ?>">
                                <img src="<?php echo esc_url($rel_img); ?>" alt="<?php echo esc_attr($rel_product->get_name()); ?>">
                            </a>
                        </div>
                        <div class="catalog-card__body">
                            <span class="catalog-card__cat">SERUM & TINH CHẤT</span>
                            <h3 class="catalog-card__title">
                                <a href="<?php echo get_permalink($rel_id); ?>" style="color:inherit; text-decoration:none;"><?php echo esc_html($rel_product->get_name()); ?></a>
                            </h3>
                            <div class="catalog-card__stars">☆☆☆☆☆</div>
                            <div class="catalog-card__footer">
                                <span class="catalog-card__price"><?php echo $rel_product->get_price_html(); ?></span>
                                <a href="?add-to-cart=<?php echo $rel_id; ?>" class="btn catalog-card__btn">Thêm vào giỏ</a>
                            </div>
                        </div>
                    </article>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>
    </section>
</main>

<script>
    function changeImage(element, src) {
        document.getElementById('mainProductImg').src = src;
        document.querySelectorAll('.thumb-item').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
    }
    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        let current = parseInt(input.value) || 1;
        current += change;
        if (current < 1) current = 1;
        input.value = current;
    }
    function selectCapacity(btn) {
        document.querySelectorAll('.capacity-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    function openTab(evt, tabId) {
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(tb => tb.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
</script>

<?php
endwhile;
get_footer();
?>