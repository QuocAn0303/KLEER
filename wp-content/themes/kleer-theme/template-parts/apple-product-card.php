<?php
defined('ABSPATH') || exit;
$item = $args['item'];
$product = $args['product'];
?>
<article class="product-card" data-testid="product-card" style="--card-top-bg:<?php echo esc_attr($item['cardTopBg']); ?>;--card-bottom-bg:<?php echo esc_attr($item['cardBottomBg']); ?>;--card-accent:<?php echo esc_attr($item['cardAccent']); ?>">
    <div class="apple-card__info">
        <span class="apple-card__eyebrow"><?php echo esc_html($item['bestsellerTag']); ?></span>
        <h3 class="apple-card__title"><a href="<?php echo esc_url($item['permalink']); ?>"><?php echo esc_html($item['name']); ?></a></h3>
        <div class="apple-card__meta-row"><span class="apple-card__skin-chip" data-testid="taxonomy-badge"><?php echo esc_html($item['skinLabel']); ?></span><span><?php echo wp_kses_post($product->get_price_html()); ?></span></div>
        <p class="apple-card__sensory"><?php echo esc_html(wp_trim_words($item['sensory'], 22)); ?></p>
    </div>
    <div class="apple-card__stage">
        <a href="<?php echo esc_url($item['permalink']); ?>" aria-label="<?php echo esc_attr($item['name']); ?>"><img class="apple-card__img" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy"></a>
        <span class="apple-card__inci-pill"><?php echo esc_html($item['inciBadge']); ?></span>
        <?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()): ?>
            <button type="button" class="apple-card__circle-btn" data-add-cart="<?php echo esc_attr($item['id']); ?>" aria-label="<?php echo esc_attr('Thêm ' . $item['name'] . ' vào Routine'); ?>">+</button>
        <?php else: ?>
            <a class="apple-card__circle-btn" href="<?php echo esc_url($item['permalink']); ?>" aria-label="Xem sản phẩm">→</a>
        <?php endif; ?>
    </div>
</article>
