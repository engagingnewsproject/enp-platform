<?php

/*

Template Name: Contact Page

*/	

?>

<?php get_header(); ?>

<?php 



	wp_reset_query();

	global $post;

	$mail_to = get_post_meta ($post->ID, THEME_NAME."_contact_mail", true );



	$showTitle = get_post_meta ( $post->ID, THEME_NAME."_show_title", true ); 



	//google map

	$map = get_post_meta ($post->ID, THEME_NAME."_map", true );







?>


<!-- Container -->
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
          <div class="post-title">
		<h1 class="entry-title"><?php the_title();?></h1>
	</div>
    	<div class="workshop">
         <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        
         <?php the_content();?>
        <?php endwhile; else: ?>
        <?php endif; ?>
         </div>
      </div>
      <?php get_template_part(THEME_INCLUDES."sidebar-contact"); ?> 
</div>
<?php get_footer(); ?>

