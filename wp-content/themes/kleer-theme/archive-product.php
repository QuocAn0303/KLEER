<?php
/**
 * Template Name: Shop Catalog Page
 * Theme: KLEER Skincare
 */
get_header(); ?>

<main class="shop-catalog-page">
    <div class="container">
        
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a>
            <span class="breadcrumb__sep">&rsaquo;</span>
            <span class="breadcrumb__current">Sản phẩm</span>
        </nav>
        
        <h1 class="shop-title"><?php function_exists('woocommerce_page_title') ? woocommerce_page_title() : single_post_title(); ?></h1>

        <!-- Layout 2 Cột -->
        <div class="shop-layout">
            
            <!-- CỘT 1: SIDEBAR BỘ LỌC (Tích hợp Form AJAX) -->
            <aside class="shop-sidebar">
                <form class="product-filter-form">
                    
                    <!-- DANH MỤC -->
                    <div class="filter-group">
                        <h3 class="filter-title">DANH MỤC</h3>
                        <ul class="filter-list">
                            <?php
                            $categories = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
                            ]);
                            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                                foreach ( $categories as $cat ) :
                            ?>
                                <li>
                                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="filter-link">
                                        <?php echo esc_html($cat->name); ?> 
                                        <span>(<?php echo sprintf('%02d', $cat->count); ?>)</span>
                                    </a>
                                </li>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </ul>
                    </div>

                    <!-- SẮP XẾP THEO -->
                    <div class="filter-group">
                        <h3 class="filter-title">SẮP XẾP THEO</h3>
                        <select name="orderby" class="filter-select">
                            <option value="bestseller">Bán chạy nhất</option>
                            <option value="price-asc">Giá: Thấp đến Cao</option>
                            <option value="price-desc">Giá: Cao đến Thấp</option>
                            <option value="newest" selected>Mới nhất</option>
                        </select>
                    </div>

                    <!-- KHOẢNG GIÁ -->
                    <div class="filter-group">
                        <h3 class="filter-title">KHOẢNG GIÁ</h3>
                        <div class="price-range">
                            <input type="range" class="price-slider" name="max_price" min="100000" max="3000000" step="50000" value="2000000">
                            <div class="price-values">
                                <span>100.000đ</span>
                                <span>3.000.000đ</span>
                            </div>
                        </div>
                    </div>

                    <!-- LOẠI DA (Gắn data-taxonomy cho AJAX) -->
                    <div class="filter-group">
                        <h3 class="filter-title">LOẠI DA</h3>
                        <div class="checkbox-group">
                            <?php
                            $skin_types = get_terms([
                                'taxonomy'   => 'pa_skin_type',
                                'hide_empty' => false,
                            ]);
                            if ( ! empty( $skin_types ) && ! is_wp_error( $skin_types ) ) :
                                foreach ( $skin_types as $type ) :
                            ?>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="skin_type[]" value="<?php echo esc_attr($type->slug); ?>" data-taxonomy="pa_skin_type">
                                    <span class="checkmark"></span>
                                    <?php echo esc_html($type->name); ?>
                                </label>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="skin_type[]" value="da-dau" data-taxonomy="pa_skin_type" checked>
                                    <span class="checkmark"></span> Da dầu
                                </label>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="skin_type[]" value="da-kho" data-taxonomy="pa_skin_type">
                                    <span class="checkmark"></span> Da khô
                                </label>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="skin_type[]" value="da-hon-hop" data-taxonomy="pa_skin_type" checked>
                                    <span class="checkmark"></span> Da hỗn hợp
                                </label>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="skin_type[]" value="da-nhay-cam" data-taxonomy="pa_skin_type">
                                    <span class="checkmark"></span> Da nhạy cảm
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                </form>
            </aside>

            <!-- CỘT 2: LƯỚI SẢN PHẨM (Có id="kleer-products-grid" cho AJAX) -->
            <section class="shop-content">
                <div class="catalog-grid" id="kleer-products-grid">
                    <?php
                    if ( have_posts() ) :
                        while ( have_posts() ) : the_post();
                            global $product;
                            if ( empty($product) && function_exists('wc_get_product') ) {
                                $product = wc_get_product(get_the_ID());
                            }
                            $img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                            if ( ! $img_url ) {
                                $img_url = get_template_directory_uri() . '/assets/images/product-1.jpg';
                            }
                            $terms = get_the_terms(get_the_ID(), 'product_cat');
                            $cat_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : 'CHĂM SÓC DA MẶT';
                    ?>
                        <!-- CARD SẢN PHẨM (Có data-testid cho Tester) -->
                        <article class="catalog-card" data-testid="product-card">
                            <div class="catalog-card__img-wrap">
                                <a href="<?php the_permalink(); ?>">
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title_attribute(); ?>">
                                </a>
                            </div>
                            <div class="catalog-card__body">
                                <span class="catalog-card__cat" data-testid="taxonomy-badge"><?php echo esc_html(strtoupper($cat_name)); ?></span>
                                <h3 class="catalog-card__title">
                                    <a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;"><?php the_title(); ?></a>
                                </h3>
                                <div class="catalog-card__stars">☆☆☆☆☆</div>
                                <div class="catalog-card__footer">
                                    <span class="catalog-card__price"><?php echo $product ? $product->get_price_html() : '1.250.000đ'; ?></span>
                                    <a href="?add-to-cart=<?php echo get_the_ID(); ?>" class="btn catalog-card__btn">Thêm vào giỏ</a>
                                </div>
                            </div>
                        </article>
                    <?php
                        endwhile;
                    else :
                        echo '<p style="color: #fff;">Không tìm thấy sản phẩm phù hợp.</p>';
                    endif;
                    ?>
                </div>

                <!-- Phân trang -->
                <div class="catalog-pagination">
                    <?php
                    echo paginate_links([
                        'prev_text' => '&larr;',
                        'next_text' => '&rarr;',
                        'type'      => 'plain',
                    ]);
                    ?>
                </div>
            </section>

        </div>
    </div>
</main>

<?php get_footer(); ?>
