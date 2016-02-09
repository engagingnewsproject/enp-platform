<?php

if( is_singular('research') || is_page_template('single-research.php') ){
	dynamic_sidebar( 'sidebar-research' );
} else if ( is_singular('page') ){
	dynamic_sidebar( 'sidebar-page' );
} else {
	dynamic_sidebar('sidebar-primary');
}
?>
