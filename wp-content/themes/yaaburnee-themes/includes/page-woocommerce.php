<?php
	wp_reset_query();
?>
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
	<!-- Blank page container -->
    <article <?php post_class(); ?>>
	    <?php if (have_posts()) : ?>
	        <?php woocommerce_content(); ?>
		<?php else: ?>
			<p><?php  _e('Sorry, no posts matched your criteria.' , THEME_NAME ); ?></p>
		<?php endif; ?>
	</article>
<?php get_template_part(THEME_LOOP."loop","end"); ?> 
