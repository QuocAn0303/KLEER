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
                <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-link <?php echo is_front_page() ? 'active' : ''; ?>">Trang chủ</a>
                <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="nav-link">Sản phẩm</a>
                <a href="<?php echo esc_url(home_url('/quiz/')); ?>" class="nav-link">Chẩn đoán da</a>
                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="nav-link">Về chúng tôi</a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="nav-link">Liên hệ</a>
            </nav>

            <div class="header__icons">
                <a href="<?php echo function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'); ?>" class="icon-holder" title="Giỏ hàng">
                    <svg class="icon-img icon-cart" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </a>
                <a href="<?php echo function_exists('wc_get_page_id') && wc_get_page_id('myaccount') > 0 ? get_permalink(wc_get_page_id('myaccount')) : home_url('/my-account/'); ?>" class="icon-holder" title="Tài khoản">
                    <svg class="icon-img icon-avatar" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </div>

    </div>
</header>
