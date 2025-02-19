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
	* /mytheme/templates/page-mypage.twig
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
	use Engage\Models\Homepage;
	$context = Timber::context();
	if (is_front_page()) {
		try {
			$context['home'] = new Homepage();
			$templates = ['page/homepage.twig', 'page.twig'];
		} catch (\Exception $e) {
			$templates = ['page.twig'];
		}
	} else {
		$templates = ['page/' . $post->post_name . '.twig', 'page.twig'];
	}
	if(is_post_type_archive('tribe_events')) {
		include 'archive.php';
	}
	else if(is_singular('tribe_events')) {
		include 'single.php';
	} else {
		if(is_front_page()) {
			$context['home'] = new Homepage($wp_query);
		}
		$timber_post     = Timber::get_post();
		$context['post'] = $timber_post;
		Timber::render($templates, $context);
	}
	