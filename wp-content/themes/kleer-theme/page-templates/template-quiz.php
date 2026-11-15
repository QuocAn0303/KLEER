<?php
/**
 * Template Name: Quiz Chẩn Đoán Da
 * Description: Trang Chẩn đoán da dùng chung khung Sanctuary 6 câu với trang chủ.
 * Project: KLEER - Unisex Skincare Store
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kleer-quiz-page">
    <?php get_template_part('template-parts/quiz-sanctuary'); ?>
</main>
<?php
get_footer();
