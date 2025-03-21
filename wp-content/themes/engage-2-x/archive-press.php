<?php
/**
 * The template for displaying Press Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

namespace App;

use Timber\Timber;

$templates = [ 'templates/archive-press.twig', 'templates/index.twig' ];

$title = 'Press Archive';
if ( is_day() ) {
	$title = 'Press Archive: ' . get_the_date( 'D M Y' );
} elseif ( is_month() ) {
	$title = 'Press Archive: ' . get_the_date( 'M Y' );
} elseif ( is_year() ) {
	$title = 'Press Archive: ' . get_the_date( 'Y' );
} elseif ( is_tag() ) {
	$title = single_tag_title( '', false );
} elseif ( is_category() ) {
	$title = single_cat_title( '', false );
} elseif ( is_post_type_archive() ) {
	$title = post_type_archive_title( '', false );
	array_unshift( $templates, 'templates/archive-' . get_post_type() . '.twig' );
}

// Get posts
$posts = Timber::get_posts();

$context = Timber::context(
	[
		'title' => $title,
		'posts' => $posts,
		'debug' => [
			'post_type' => get_post_type(),
			'query_vars' => $wp_query->query_vars,
			'found_posts' => $wp_query->found_posts,
			'post_count' => $wp_query->post_count,
			'posts_count' => count($posts)
		]
	]
);

Timber::render( $templates, $context );