<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header('shop');
?>

<div class="kleer-catalog">
    <div class="kleer-catalog__header">
        <div class="container">
            <?php
            if (apply_filters('woocommerce_show_page_title', true)) {
                ?>
                <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
                <?php
            }
            ?>
            <?php do_action('woocommerce_before_main_content'); ?>
        </div>
    </div>

    <div class="container kleer-catalog__layout">
        <aside class="kleer-catalog__sidebar">
            <div class="kleer-filter">
                <h2 class="kleer-filter__title"><?php esc_html_e('Bộ lọc sản phẩm', 'kleer-theme'); ?></h2>
                <form id="kleer-catalog-filter-form" class="kleer-filter__form" data-nonce="<?php echo esc_attr(wp_create_nonce('kleer_filter_products')); ?>">
                    <?php
                    $taxonomies = [
                        'skin_type' => __('Loại da', 'kleer-theme'),
                        'skin_concern' => __('Vấn đề da', 'kleer-theme'),
                        'routine_step' => __('Bước chăm sóc', 'kleer-theme'),
                    ];

                    foreach ($taxonomies as $taxonomy => $label) {
                        if (!taxonomy_exists($taxonomy)) {
                            continue;
                        }
                        $terms = get_terms([
                            'taxonomy' => $taxonomy,
                            'hide_empty' => false,
                        ]);
                        if (empty($terms) || is_wp_error($terms)) {
                            continue;
                        }
                        ?>
                        <div class="kleer-filter__group">
                            <h3 class="kleer-filter__group-title"><?php echo esc_html($label); ?></h3>
                            <div class="kleer-filter__options">
                                <?php foreach ($terms as $term) : ?>
                                    <label class="kleer-filter__option">
                                        <input type="checkbox" name="<?php echo esc_attr($taxonomy); ?>[]" value="<?php echo esc_attr($term->slug); ?>" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" />
                                        <span><?php echo esc_html($term->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <div class="kleer-filter__group">
                        <h3 class="kleer-filter__group-title"><?php esc_html_e('Khoảng giá', 'kleer-theme'); ?></h3>
                        <div class="kleer-filter__price">
                            <input type="number" name="min_price" id="kleer-min-price" placeholder="<?php esc_attr_e('Từ', 'kleer-theme'); ?>" min="0" />
                            <input type="number" name="max_price" id="kleer-max-price" placeholder="<?php esc_attr_e('Đến', 'kleer-theme'); ?>" min="0" />
                        </div>
                    </div>

                    <div class="kleer-filter__actions">
                        <button type="button" id="kleer-filter-clear" class="kleer-btn kleer-btn--secondary"><?php esc_html_e('Xóa bộ lọc', 'kleer-theme'); ?></button>
                    </div>
                </form>
            </div>
        </aside>

        <main class="kleer-catalog__main">
            <div class="kleer-catalog__toolbar">
                <?php do_action('woocommerce_before_shop_loop'); ?>
            </div>

            <div id="kleer-products-grid" class="kleer-products-grid">
                <?php
                if (woocommerce_product_loop()) {
                    woocommerce_product_loop_start();

                    if (wc_get_loop_prop('total')) {
                        while (have_posts()) {
                            the_post();
                            do_action('woocommerce_shop_loop');
                            wc_get_template_part('content', 'product');
                        }
                    }

                    woocommerce_product_loop_end();
                } else {
                    do_action('woocommerce_no_products_found');
                }
                ?>
            </div>

            <div id="kleer-products-empty" class="kleer-products-empty" style="display:none;">
                <p><?php esc_html_e('Không tìm thấy sản phẩm phù hợp với bộ lọc.', 'kleer-theme'); ?></p>
                <div class="kleer-products-empty__actions">
                    <button type="button" id="kleer-filter-clear-empty" class="kleer-btn"><?php esc_html_e('Xóa bộ lọc', 'kleer-theme'); ?></button>
                    <a href="<?php echo esc_url(home_url('/skin-quiz')); ?>" class="kleer-btn kleer-btn--secondary"><?php esc_html_e('Làm Smart Skin Quiz', 'kleer-theme'); ?></a>
                </div>
            </div>

            <div class="kleer-catalog__pagination">
                <?php do_action('woocommerce_after_shop_loop'); ?>
            </div>
        </main>
    </div>
</div>

<?php
do_action('woocommerce_after_main_content');
get_footer('shop');
?>
