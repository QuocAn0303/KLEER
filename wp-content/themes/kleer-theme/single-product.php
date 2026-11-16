<?php
defined('ABSPATH') || exit;
get_header();
while (have_posts()): the_post();
$product = wc_get_product(get_the_ID());
if (!$product || !class_exists('\\Kleer\\Support\\ProductCardPresenter')) { continue; }
$item = \Kleer\Support\ProductCardPresenter::present($product);
?>
<main class="container kleer-subpage">
<nav class="breadcrumb" aria-label="Đường dẫn"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a> / <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Sản phẩm</a> / <?php echo esc_html($item['step']); ?> / <?php the_title(); ?></nav>
<div class="pdp-card" style="--card-top-bg:<?php echo esc_attr($item['cardTopBg']); ?>;--card-bottom-bg:<?php echo esc_attr($item['cardBottomBg']); ?>">
<section class="pdp-stage" data-testid="product-gallery">
<img id="mainProductImg" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name']); ?>">
<span class="apple-card__inci-pill"><?php echo esc_html($item['inciBadge']); ?></span>
<div class="pdp-thumbnails">
<?php foreach (array_unique(array_merge([$product->get_image_id()], $product->get_gallery_image_ids())) as $image_id): $url = wp_get_attachment_image_url($image_id, 'large'); if (!$url) { continue; } ?>
<button type="button" data-product-image="<?php echo esc_url($url); ?>" aria-label="Xem ảnh sản phẩm"><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($item['name']); ?>"></button>
<?php endforeach; ?></div>
</section>
<section class="pdp-info"><p><?php echo esc_html($item['step'] . ' · MÃ SKU: ' . $item['sku']); ?></p><h1><?php the_title(); ?></h1>
<div class="product-card__chips" data-testid="taxonomy-badge"><?php foreach (['skinLabel', 'concern', 'inciBadge'] as $key): ?><span><?php echo esc_html($item[$key]); ?></span><?php endforeach; ?></div>
<div class="pdp-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
<?php echo wp_kses_post(wpautop($product->get_short_description())); ?>
<?php if ($item['inciGroup'] !== 'neutral'): ?><aside class="pdp-warning">Lưu ý chống nắng khi sử dụng hoạt chất. Tránh dùng AHA/BHA cùng buổi với Retinoids; kiểm tra hướng dẫn sản phẩm trước khi kết hợp.</aside><?php endif; ?>
<?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()): ?>
<div class="pdp-actions"><label>Số lượng <input id="pdp-quantity" type="number" value="1" min="1" max="<?php echo esc_attr($product->get_max_purchase_quantity() > 0 ? $product->get_max_purchase_quantity() : 99); ?>" step="1" <?php disabled($product->is_sold_individually()); ?>></label>
<button type="button" class="btn" data-add-cart="<?php echo esc_attr($product->get_id()); ?>" data-quantity-input="pdp-quantity">Thêm vào giỏ hàng Routine</button></div>
<?php else: woocommerce_template_single_add_to_cart(); endif; ?>
<a class="btn" href="<?php echo esc_url(home_url('/quiz/')); ?>">Chẩn đoán độ hợp da</a>
</section></div>
<div class="pdp-details"><section class="subpage-panel"><h2>Thành phần & Công dụng chi tiết</h2><?php the_content(); ?></section>
<section class="subpage-panel"><h2>Vị trí trong Routine</h2><p><?php echo esc_html($item['step']); ?></p><ol><li>Làm sạch</li><li>Cân bằng</li><li>Đặc trị</li><li>Dưỡng ẩm · Chống nắng vào ban ngày</li></ol><p>Giỏ hàng tự động giảm 15% khi đủ từ 3 bước Routine khác nhau, kèm cảnh báo tương tác hoạt chất.</p></section></div>
<h2>Sản phẩm kết hợp cùng Routine</h2><div class="related-routine-grid">
<?php
$candidates = wc_get_products(['status' => 'publish', 'limit' => 40, 'exclude' => [$product->get_id()], 'orderby' => 'date', 'order' => 'DESC']);
$count = 0;
foreach ($candidates as $related) {
    if (!$related->is_visible() || !$related->is_in_stock()) { continue; }
    $conflicts = (new \Kleer\Services\CartComboService())->scanIngredientConflicts([['product_id' => $product->get_id()], ['product_id' => $related->get_id()]]);
    if ($conflicts['has_conflict']) { continue; }
    $other = \Kleer\Support\ProductCardPresenter::present($related);
    if (!$other['stepSlug'] || $other['stepSlug'] === $item['stepSlug']) { continue; }
    if (in_array($item['inciGroup'], ['aha_bha', 'retinol'], true) && in_array($other['inciGroup'], ['aha_bha', 'retinol'], true) && $item['inciGroup'] !== $other['inciGroup']) { continue; }
    \KleerTheme\product_card($related);
    if (++$count === 4) { break; }
}
if (!$count) { echo '<p>Chưa có sản phẩm kết hợp phù hợp.</p>'; }
?>
</div></main>
<?php endwhile; get_footer(); ?>
