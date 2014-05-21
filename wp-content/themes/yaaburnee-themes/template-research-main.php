<?php

/*

Template Name: Research Main Cat Template

*/	

?>
<?php get_header(); ?>


<!-- Container -->
<div class="container">
  <div id="primary-fullwith" class="researchpagemain">
  <div class="post-title">
		<h1 class="entry-title"><?php the_title();?></h1>
	</div>
    
    	<?php  if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
       <?php the_content();?>
        <?php endwhile; else: ?>
        <?php endif; ?>
        <br />
   <?php query_posts( array( 'showposts' => '20', 'post_parent' => '582', 'post_type' => 'page' ) ); if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        	<div class="contentloop"><div class="thumbnail"><?php echo  get_the_post_thumbnail($page->ID, 'medium') ?></div>
            <div class="content">
        	 <h2><a href="<?php the_permalink() ?>" ><?php echo short_title('...', 30); ?></a></h2>
             	<span class="datestemp"><?php echo get_the_date(); ?></span>
             	<div class="textbox"><?php the_field('short_content'); ?></div>
                <a href="<?php the_permalink() ?>" class="readmore">Read More</a>
             </div>
             </div>
        <?php endwhile; else: ?>
        <?php endif; ?>
  </div>
</div>
<?php get_footer(); ?>
