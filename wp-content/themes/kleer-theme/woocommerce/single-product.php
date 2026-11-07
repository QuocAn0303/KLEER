<?php
/**
 * The Template for displaying all single products
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header('shop');
?>

<?php while (have_posts()) : the_post(); ?>
<div class="kleer-pdp">
    <div class="container">
        <div class="kleer-pdp__breadcrumb">
            <?php woocommerce_breadcrumb(); ?>
        </div>

        <div class="kleer-pdp__layout">
            <div class="kleer-pdp__gallery">
                <?php do_action('woocommerce_before_single_product_summary'); ?>
            </div>

            <div class="kleer-pdp__summary">
                <h1 class="kleer-pdp__title"><?php the_title(); ?></h1>

                <div class="kleer-pdp__taxonomies">
                    <?php
                    $taxonomies = ['skin_type', 'skin_concern', 'routine_step'];
                    foreach ($taxonomies as $taxonomy) {
                        if (!taxonomy_exists($taxonomy)) {
                            continue;
                        }
                        $terms = get_the_terms(get_the_ID(), $taxonomy);
                        if (empty($terms) || is_wp_error($terms)) {
                            continue;
                        }
                        echo '<div class="kleer-pdp__tax-group">';
                        foreach ($terms as $term) {
                            echo '<span class="kleer-pdp__tax-badge">' . esc_html($term->name) . '</span>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="kleer-pdp__price">
                    <?php woocommerce_template_single_price(); ?>
                </div>

                <div class="kleer-pdp__add-to-cart">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

                <div class="kleer-pdp__short-description">
                    <?php woocommerce_template_single_excerpt(); ?>
                </div>
            </div>
        </div>

        <div class="kleer-pdp__tabs">
            <?php woocommerce_output_product_data_tabs(); ?>
        </div>

        <div class="kleer-pdp__sections">
            <section class="kleer-pdp__section">
                <h2 class="kleer-pdp__section-title"><?php esc_html_e('Thành phần (Ingredients)', 'kleer-theme'); ?></h2>
                <div class="kleer-pdp__ingredients">
                    <?php
                    $ingredients = get_post_meta(get_the_ID(), '_kleer_ingredients', true);
                    if (empty($ingredients)) {
                        $ingredients = get_post_meta(get_the_ID(), 'ingredients', true);
                    }
                    if (!empty($ingredients)) {
                        echo wpautop(esc_html($ingredients));
                    } else {
                        echo '<p>' . esc_html__('Chưa có thông tin thành phần.', 'kleer-theme') . '</p>';
                    }
                    ?>
                </div>
            </section>

            <section class="kleer-pdp__section">
                <h2 class="kleer-pdp__section-title"><?php esc_html_e('Công dụng', 'kleer-theme'); ?></h2>
                <div class="kleer-pdp__usage">
                    <?php
                    $benefits = get_post_meta(get_the_ID(), '_kleer_benefits', true);
                    if (!empty($benefits)) {
                        echo wpautop(esc_html($benefits));
                    } else {
                        the_content();
                    }
                    ?>
                </div>
            </section>

            <section class="kleer-pdp__section">
                <h2 class="kleer-pdp__section-title"><?php esc_html_e('Hướng dẫn sử dụng', 'kleer-theme'); ?></h2>
                <div class="kleer-pdp__how-to-use">
                    <?php
                    $howToUse = get_post_meta(get_the_ID(), '_kleer_how_to_use', true);
                    if (empty($howToUse)) {
                        $howToUse = get_post_meta(get_the_ID(), 'how_to_use', true);
                    }
                    if (!empty($howToUse)) {
                        echo wpautop(esc_html($howToUse));
                    } else {
                        echo '<p>' . esc_html__('Xem hướng dẫn sử dụng trên bao bì sản phẩm hoặc theo phác đồ da cá nhân.', 'kleer-theme') . '</p>';
                    }
                    ?>
                </div>
            </section>
        </div>

        <?php do_action('woocommerce_after_single_product_summary'); ?>
    </div>
</div>
<?php endwhile; ?>

<?php
get_footer('shop');
?>
