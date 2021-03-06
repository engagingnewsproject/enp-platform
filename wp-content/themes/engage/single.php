<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();
if(is_singular('research') || is_singular('blogs')) {
	$post = new Engage\Models\ResearchArticle();
}
elseif(is_singular('tribe_events')) {
	$post = new Engage\Models\Event();
}
else {
	$post = new Engage\Models\Article();
}

$context['post'] = $post;
$context['primary'] = Timber::get_widgets('primary');

if ( post_password_required( $post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
} else {
	# Set additional team members up, parse string into list of users
	$context['post']->additional_team_members_list = explode(', ', $post->additional_team_members);
	Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context, ENGAGE_PAGE_CACHE_TIME );
}
