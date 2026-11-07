<?php
<<<<<<< HEAD
get_header();
?>
<main class="container">
    <h1>KLEER Store</h1>
</main>
<?php
get_footer();
=======
/**
 * Main template file
 *
 * @package KLEER
 */

get_header();
?>
<main id="primary" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
    endif;
    ?>
</main>
<?php
get_footer();
>>>>>>> 0adee1e01b4ba3b6cb632d283ae2f35a94751075
