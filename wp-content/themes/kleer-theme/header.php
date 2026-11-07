<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?> <!-- Bắt buộc để WordPress nạp CSS/JS -->
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- NAVBAR DÙNG CHUNG -->
<header class="header">
    <div class="header__inner container">
        
        <a href="<?php echo esc_url(home_url('/')); ?>" class="header__brand">
            <span class="header__logo">KLEER</span>
            <svg class="header__leaf-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M20.5 3.5C20.5 3.5 13.5 3.5 8.5 8.5C3.5 13.5 2.5 21.5 2.5 21.5C2.5 21.5 10.5 20.5 15.5 15.5C20.5 10.5 20.5 3.5 20.5 3.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2.5 21.5L11.5 12.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>

        <div class="header__search">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="7" stroke="rgba(255,255,255,0.7)" stroke-width="2"/>
                <path d="M20 20L16 16" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input type="text" class="search-input" placeholder="Tìm kiếm sản phẩm...">
        </div>

        <div class="header__right">
            <nav class="header__nav">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-link active">Trang chủ</a>
                <a href="<?php echo esc_url(home_url('/shop')); ?>" class="nav-link">Sản phẩm</a>
                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="nav-link">Liên hệ</a>
                <a href="<?php echo esc_url(home_url('/about')); ?>" class="nav-link">Về chúng tôi</a>
            </nav>

            <div class="header__icons">
                <a href="<?php echo function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#'; ?>" class="icon-holder" title="Giỏ hàng">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icon-cart.svg" alt="Giỏ hàng" class="icon-img icon-cart">
                </a>
                <a href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>" class="icon-holder" title="Tài khoản">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icon-avatar.svg" alt="Tài khoản" class="icon-img icon-avatar">
                </a>
            </div>
        </div>

    </div>
</header>