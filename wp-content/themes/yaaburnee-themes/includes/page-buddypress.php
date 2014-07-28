<?php wp_reset_query(); ?>
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
	<!-- Blank page container -->

    <article <?php post_class(); ?>>
		<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();

					the_content();
				} // end while
			} // end if
		?>
    </article>
<?php get_template_part(THEME_LOOP."loop","end"); ?> 
