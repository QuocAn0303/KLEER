<?php
/**
 * Enqueue scripts and styles for Quiz SPA
 *
 * @package KLEER
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kleer_enqueue_quiz_scripts() {
    if ( is_page_template( 'page-templates/template-quiz.php' ) ) {
        wp_enqueue_style(
            'kleer-quiz',
            get_template_directory_uri() . '/assets/css/quiz.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'kleer-quiz',
            get_template_directory_uri() . '/assets/js/quiz.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'kleer-quiz',
            'kleerQuizConfig',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'restUrl'     => esc_url_raw( rest_url( 'kleer/v1/' ) ),
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'resultUrl'   => esc_url_raw( home_url( '/ket-qua-chan-doan/' ) ),
            ]
        );
    }
}
add_action( 'wp_enqueue_scripts', 'kleer_enqueue_quiz_scripts' );