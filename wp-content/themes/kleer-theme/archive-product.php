<?php
defined('ABSPATH') || exit;
get_header();
$tax_query = ['relation' => 'AND'];
$filters = ['skin_type' => 'pa_skin_type', 'routine_step' => 'pa_routine_step', 'skin_concern' => 'pa_skin_concern', 'active_ingredient' => 'pa_active_ingredient'];
foreach ($filters as $key => $taxonomy) {
    $value = isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_title(wp_unslash($_GET[$key])) : '';
    if ($value !== '') { $tax_query[] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $value]; }
}
if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
    $term = get_queried_object();
    $tax_query[] = ['taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => $term->term_id];
}
if (function_exists('wc_get_product_visibility_term_ids')) {
    $visibility = wc_get_product_visibility_term_ids();
    $hidden = [$visibility['exclude-from-catalog']];
    if (get_option('woocommerce_hide_out_of_stock_items') === 'yes') { $hidden[] = $visibility['outofstock']; }
    $tax_query[] = ['taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => $hidden, 'operator' => 'NOT IN'];
}
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? sanitize_key($_GET['sort']) : 'popularity';
$ordering = ['popularity' => ['meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC'], 'price' => ['meta_key' => '_price', 'orderby' => 'meta_value_num', 'order' => 'ASC'], 'price-desc' => ['meta_key' => '_price', 'orderby' => 'meta_value_num', 'order' => 'DESC'], 'date' => ['orderby' => 'date', 'order' => 'DESC']];
$search = isset($_GET['q']) && is_string($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$page = max(1, isset($_GET['pg']) ? absint($_GET['pg']) : (int) get_query_var('paged'));
$query = new WP_Query(array_merge(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $page, 'tax_query' => $tax_query, 's' => $search], $ordering[$sort] ?? $ordering['popularity']));
?>
<main class="container kleer-subpage">
<h1 class="apple-two-tone-heading">Cửa hàng KLEER. <span>Khám phá trọn bộ công thức chăm sóc da theo từng bước sinh học.</span></h1>
<nav class="chapternav-bar" aria-label="Chọn nhanh loại da">
<a class="chapternav-pill" href="<?php echo esc_url(remove_query_arg(['skin_type', 'pg'])); ?>">Tất cả</a>
<?php $skin_terms = get_terms(['taxonomy' => 'pa_skin_type', 'hide_empty' => true]); if (!is_wp_error($skin_terms)): foreach ($skin_terms as $skin): ?>
<a class="chapternav-pill" href="<?php echo esc_url(add_query_arg('skin_type', $skin->slug, remove_query_arg('pg'))); ?>"><?php echo esc_html($skin->name); ?></a>
<?php endforeach; endif; ?>
</nav>
<form method="get" class="catalog-toolbar glass-light" data-shop-filters>
<?php foreach ($filters as $key => $taxonomy): $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]); ?>
<label><?php echo esc_html(['skin_type' => 'Loại da', 'routine_step' => 'Bước Routine', 'skin_concern' => 'Vấn đề da', 'active_ingredient' => 'Hoạt chất'][$key]); ?>
<select name="<?php echo esc_attr($key); ?>"><option value="">Tất cả</option>
<?php if (!is_wp_error($terms)): foreach ($terms as $term): ?>
<option value="<?php echo esc_attr($term->slug); ?>" <?php selected(isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_title(wp_unslash($_GET[$key])) : '', $term->slug); ?>><?php echo esc_html($term->name); ?></option>
<?php endforeach; endif; ?></select></label>
<?php endforeach; ?>
<label>Sắp xếp<select name="sort"><?php foreach (['popularity' => 'Phổ biến nhất', 'price' => 'Giá thấp đến cao', 'price-desc' => 'Giá cao đến thấp', 'date' => 'Mới nhất'] as $value => $label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($sort, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
<?php if ($search): ?><input type="hidden" name="q" value="<?php echo esc_attr($search); ?>"><?php endif; ?>
<button type="submit" class="btn">Lọc sản phẩm</button>
</form>
<p role="status"><?php echo esc_html(sprintf('Hiển thị %d / %d công thức phù hợp', $query->post_count, $query->found_posts)); ?></p>
<div class="bento-grid shop-product-grid">
<?php while ($query->have_posts()): $query->the_post(); $product = wc_get_product(get_the_ID()); if ($product) { \KleerTheme\product_card($product); } endwhile; wp_reset_postdata(); ?>
</div>
<?php if (!$query->found_posts): ?><p>Chưa có sản phẩm phù hợp. Hãy thử thay đổi bộ lọc.</p><?php endif; ?>
<nav class="catalog-pagination" aria-label="Phân trang sản phẩm"><?php echo wp_kses_post(paginate_links(['base' => add_query_arg('pg', '%#%', remove_query_arg('pg')), 'format' => '', 'current' => $page, 'total' => $query->max_num_pages, 'prev_text' => '←', 'next_text' => '→'])); ?></nav>
</main>
<?php get_footer(); ?>
