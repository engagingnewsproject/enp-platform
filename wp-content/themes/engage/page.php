<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

// tribe events uses the page template, so let's redirect them to the right spot
if(is_post_type_archive('tribe_events')) {
	include 'archive.php';
} 
else if(is_singular('tribe_events')) {
	include 'single.php';
}
else {
	$context = Timber::get_context();

	if(is_front_page()) {
		$context['home'] = new Engage\Models\Homepage();
		Timber::render( [ 'homepage.twig' ], $context );
	} else {
		$post = new TimberPost();
		$context['post'] = $post;
		Timber::render( [ 'page-' . $post->post_name . '.twig', 'page.twig' ], $context );
	}
	
	
}



