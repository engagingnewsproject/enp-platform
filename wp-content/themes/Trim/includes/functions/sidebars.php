<?php
if ( function_exists('register_sidebar') ) {
	register_sidebar( array(
		'name' => 'Sidebar',
		'id' => 'sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div> <!-- end .widget -->',
		'before_title' => '<h4 class="widget_title">',
		'after_title' => '</h4>',
	) );

	register_sidebar( array(
		'name' => 'Footer Area #1',
		'id' => 'footer-area-1',
		'before_widget' => '<div id="%1$s" class="f_widget %2$s">',
		'after_widget' => '</div> <!-- end .f_widget -->',
		'before_title' => '<h4 class="widgettitle">',
		'after_title' => '</h4>',
	) );

	register_sidebar( array(
		'name' => 'Footer Area #2',
		'id' => 'footer-area-2',
		'before_widget' => '<div id="%1$s" class="f_widget %2$s">',
		'after_widget' => '</div> <!-- end .f_widget -->',
		'before_title' => '<h4 class="widgettitle">',
		'after_title' => '</h4>',
	) );

	register_sidebar( array(
		'name' => 'Footer Area #3',
		'id' => 'footer-area-3',
		'before_widget' => '<div id="%1$s" class="f_widget %2$s">',
		'after_widget' => '</div> <!-- end .f_widget -->',
		'before_title' => '<h4 class="widgettitle">',
		'after_title' => '</h4>',
	) );
}
?>