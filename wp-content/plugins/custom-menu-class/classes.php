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
	
	$cmc_classes = new WP_Query($cmc_args);
	
	if ($cmc_classes -> have_posts())
	{
		while ($cmc_classes -> have_posts())
		{
			$cmc_classes -> the_post();
			$cmc_class = get_the_title();
			
			$classes[] = array(
				'name' => __($cmc_class, 'custom-menu-class'),
				'class' => $cmc_class
			);
		}
	}

	wp_reset_query();

	return $classes;
}

add_filter('custom_menu_css_classes', 'custom_menu_classes_basic_classes');