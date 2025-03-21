<?php get_header(); ?>

<div class="container">
    <div class="page-content">
        <?php
        while (have_posts()) :
            the_post();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('页面:', 'imghosting'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            </article>
        <?php
        endwhile;
        ?>
    </div>
</div>

<?php get_footer(); ?>
