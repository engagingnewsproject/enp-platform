<?php

if( is_singular('research') || is_page_template('single-research.php') ){
	dynamic_sidebar( 'sidebar-research' );?>
	<section class="widget widget--enp-list-posts enp-widget-list">
		<?php enp_list_related_research($post->ID, 5);?>
	</section>
	<?php
} else if ( is_singular('page') ){
	dynamic_sidebar( 'sidebar-page' );
} else {
	dynamic_sidebar('sidebar-primary');
}
?>
