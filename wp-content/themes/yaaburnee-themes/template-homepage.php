<?php

/*

Template Name: Homepage

*/	

?>

<?php get_header(); ?>
<div class="innercontent">
	<div class="container">
            <div class="leftblock"><h2 class="maintitle">what's <span>happening</span></h2>
         <?php query_posts( array( 'post_type'=> 'post', 'showposts' => '4','cat' => '39') ); if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        	<div class="contentloop"><div class="thumbnail"><?php echo  get_the_post_thumbnail($page->ID, 'medium') ?></div>
            <div class="content">
        	 <h2><a href="<?php the_permalink() ?>" ><?php echo short_title('...', 30); ?></a></h2>
             	<span class="datestemp"><?php echo get_the_date(); ?></span>
             	<div class="textbox"><?php the_excerpt(); ?></div>
                <a href="<?php the_permalink() ?>" class="readmore">Read More</a>
             </div>
             </div>
        <?php endwhile; else: ?>
        <?php endif; ?>
        </div>
        <div class="rightblock"><h2 class="maintitle">Featured <span>Research</span></h2>
        <?php query_posts( array( 'post_type'=> 'page', 'post_parent' => '582') ); if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        	<div class="contentright <?php the_field('featured_research'); ?>"><div class="image"><?php echo  get_the_post_thumbnail($page->ID, 'medium') ?></div>
            <div class="content">
        	 <h2><?php the_title();?></h2>
             <span class="datestemp"><?php echo get_the_date(); ?></span>
             	<div class="textbox"><?php the_excerpt(); ?></div>
                <a href="<?php the_permalink() ?>" class="readmore">Read More</a>
             </div>
             </div>
        <?php endwhile; else: ?>
        <?php endif; ?>
        	<div class="twitterblock">
            
            <a class="twitter-timeline"  href="https://twitter.com/EngagingNews"  data-widget-id="466239013397856257">Tweets by @EngagingNews</a>
    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


            

</div>
        </div>
    </div>
</div>

<?php get_footer(); ?>