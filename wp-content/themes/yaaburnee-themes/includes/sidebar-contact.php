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
<div class="rightblock"><h2 class="maintitle">Mailing  <span>Address</span></h2>
        <?php query_posts( array( 'post_type'=> 'post', 'showposts' => '1','cat' => '40') ); if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        	<div class="contentright"><div class="mapbox"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3445.172095513129!2d-97.740716!3d30.289161999999994!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8644b5829695d783%3A0xf546b0579c31882e!2s2504+Whitis+Ave!5e0!3m2!1sen!2sus!4v1398301620086" width="363" height="302" frameborder="0" style="border:0"></iframe></div>
            <div class="content mapbox">
        	<h2>Engaging News Project</h2>
Annette Strauss Institute for Civic Life<br />
2504 A Whitis Avenue (R2000)<br />
Austin, TX 78712-1538<br />

             </div>
             </div>
        <?php endwhile; else: ?>
        <?php endif; ?>
        	
        </div>

