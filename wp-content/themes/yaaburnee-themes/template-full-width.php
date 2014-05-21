<?php

/*

Template Name: Full Width Template

*/	

?>

<?php get_header(); ?>

<!-- Container -->
<div class="container">
	    <div id="primary-fullwith">
   
            <?php if (have_posts()) : ?>

    	<!-- Blank page container -->

        <article <?php post_class(); ?>>

        	<?php get_template_part(THEME_SINGLE."page-title"); ?> 

        	<?php get_template_part(THEME_SINGLE."image"); ?> 

        	<div class="post-content">

        		<?php the_content(); ?>

        	</div> 

			<?php 

				$args = array(

					'before'           => '<div class="post-pages"><p>' . __('Pages:',THEME_NAME),

					'after'            => '</p></div>',

					'link_before'      => '',

					'link_after'       => '',

					'next_or_number'   => 'number',

					'nextpagelink'     => __('Next page',THEME_NAME),

					'previouspagelink' => __('Previous page',THEME_NAME),

					'pagelink'         => '%',

					'echo'             => 1

				);



				wp_link_pages($args); 

			?>	

		</article>

		<?php //get_template_part(THEME_SINGLE."share"); ?>

		<?php wp_reset_query(); ?>

		

	<?php else: ?>

		<p><?php  _e('Sorry, no posts matched your criteria.' , THEME_NAME ); ?></p>

	<?php endif; ?>

      </div>
</div>

<?php get_footer(); ?>