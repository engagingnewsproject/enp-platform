<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	global $wp_query;





	$sidebar = get_post_meta( df_page_id(), THEME_NAME.'_sidebar_select', true );

	



	if ( !isset($sidebar) || $sidebar=='' || is_search() || is_category() || is_tax()) {

		$sidebar='default';

	}



	if(is_category()) {

		$sidebar = df_get_option( get_cat_id( single_cat_title("",false) ), 'sidebar_select', false );

	}



	if (function_exists("is_bbpress") && is_bbpress()) {

		$sidebar='bbpress';

	}



?>


	  	<!-- Sidebar -->
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
        	<div class="twitterblock"><a class="twitter-timeline"  href="https://twitter.com/EngagingNews"  data-widget-id="466239013397856257">Tweets by @EngagingNews</a>
    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

</div>
        </div>

