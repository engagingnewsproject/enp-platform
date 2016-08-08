<?php
function custom_menu_classes_basic_classes($classes)
{
	$classes[] = array(
		'name' => __('No selection', 'custom-menu-class'),
		'class' => ''
	);
	
	$cmc_args = array(
		'post_type' => 'cmc_classes',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'ignore_sticky_posts'=> 1
	);

	$cmc_classes = get_posts($cmc_args);

	foreach ($cmc_classes as $cmc_class)
	{
		$cmc_class_item = $cmc_class -> post_title;
		
		$classes[] = array(
			'name' => __($cmc_class_item, 'custom-menu-class'),
			'class' => $cmc_class_item
		);
	}

	return $classes;
}

add_filter('custom_menu_css_classes', 'custom_menu_classes_basic_classes');