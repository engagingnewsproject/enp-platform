<?php

	wp_reset_query();



	$showTitle = get_post_meta ( $post->ID, THEME_NAME."_show_title", true ); 

    //post date, author

    $metaShow = get_post_meta ( $post->ID, THEME_NAME."_show_meta", true );

?>

<?php get_template_part(THEME_LOOP."loop","start"); ?> 

 	<?php if (have_posts()) : ?>

        <!-- Article post -->

        <article <?php post_class(); ?>>

        	<?php if($showTitle!="hide") { ?>

	            <!-- Title -->

	            <div class="post-title">

	                <h1 class="entry-title"><?php the_title(); ?></h1>

	            </div>

	        <?php } ?>

	        


            <!-- Post content -->

            <div class="post-content">

				<?php the_content(); ?>

            </div> 

        </article>


		<?php wp_reset_query(); ?>


	<?php else: ?>

		<p><?php  _e('Sorry, no posts matched your criteria.' , THEME_NAME ); ?></p>

	<?php endif; ?>

<?php get_template_part(THEME_LOOP."loop","end"); ?> 	

