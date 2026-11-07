<?php
require_once '/var/www/html/wp-load.php';

// 1. Cap nhat permalink structure thanh /%postname%/
global $wp_rewrite;
update_option('permalink_structure', '/%postname%/');
$wp_rewrite->set_permalink_structure('/%postname%/');
$wp_rewrite->flush_rules(true);
echo "1. Permalink structure updated to: " . get_option('permalink_structure') . PHP_EOL;

// 2. Kiem tra va tao trang Quiz neu chua co
$quiz_page = get_page_by_path('quiz');
if (!$quiz_page) {
    $quiz_id = wp_insert_post([
        'post_title'     => 'Chẩn đoán da',
        'post_name'      => 'quiz',
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ]);
    if ($quiz_id && !is_wp_error($quiz_id)) {
        update_post_meta($quiz_id, '_wp_page_template', 'page-templates/template-quiz.php');
        echo "2. Created Quiz page (ID: {$quiz_id}) with template-quiz.php" . PHP_EOL;
    }
} else {
    update_post_meta($quiz_page->ID, '_wp_page_template', 'page-templates/template-quiz.php');
    echo "2. Quiz page exists (ID: {$quiz_page->ID})" . PHP_EOL;
}

// 3. Kiem tra va tao trang About neu chua co
$about_page = get_page_by_path('about');
if (!$about_page) {
    $about_id = wp_insert_post([
        'post_title'     => 'Về chúng tôi',
        'post_name'      => 'about',
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'post_content'   => '<h2>Triết lý KLEER</h2><p>KLEER là thương hiệu chăm sóc da unisex tối giản, kết hợp chiết xuất thực vật bản địa với khoa học lâm sàng tiên tiến.</p>',
        'comment_status' => 'closed',
    ]);
    echo "3. Created About page (ID: {$about_id})" . PHP_EOL;
} else {
    echo "3. About page exists (ID: {$about_page->ID})" . PHP_EOL;
}

// 4. Kiem tra va tao trang Contact neu chua co
$contact_page = get_page_by_path('contact');
if (!$contact_page) {
    $contact_id = wp_insert_post([
        'post_title'     => 'Liên hệ',
        'post_name'      => 'contact',
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'post_content'   => '<h2>Liên hệ hỗ trợ KLEER</h2><p>Email: support@kleer.vn<br>Hotline: 1900 xxxx</p>',
        'comment_status' => 'closed',
    ]);
    echo "4. Created Contact page (ID: {$contact_id})" . PHP_EOL;
} else {
    echo "4. Contact page exists (ID: {$contact_page->ID})" . PHP_EOL;
}

// Flush rewrite rules mot lan nua sau khi them pages
$wp_rewrite->flush_rules(true);

echo "--- VERIFICATION ---" . PHP_EOL;
echo "Shop URL: " . (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : get_permalink(get_page_by_path('shop'))) . PHP_EOL;
echo "Quiz URL: " . get_permalink(get_page_by_path('quiz')) . PHP_EOL;
echo "About URL: " . get_permalink(get_page_by_path('about')) . PHP_EOL;
echo "Contact URL: " . get_permalink(get_page_by_path('contact')) . PHP_EOL;
