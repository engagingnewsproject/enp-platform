<?php
/**
 * Admin UI - Logic for actions which take place on the Information Tab.
 *
 * @package wpengine/common-mu-plugin
 */

declare(strict_types=1);

namespace wpengine\admin_options;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Get the blog posts from WP Engine to show in the WP admin dashboard.
 *
 * @return array
 */
function get_blog_posts() {

	// First check if there's a cached result we can use.
	$latest_posts_transient = get_transient( 'wpecommon_latest_posts' );

	// If there is a cached result we can use, use it.
	if ( ! empty( $latest_posts_transient ) ) {
		return $latest_posts_transient;
	}

	// Otherwise, fetch the REST api for the site where the posts come from.
	$api_response = wp_remote_retrieve_body( wp_remote_get( 'https://wpengine.com/wp-json/wp/v2/posts/?_embed&per_page=1' ) );

	if ( ! is_wp_error( $api_response ) ) {
		$latest_posts = json_decode( $api_response, true );
	} else {
		// Return an empty array if the API response failed. This is not mission critical if it fails, so we fail silently here for now.
		return array();
	}

	// Set the API result in a transient for 24 hours.
	set_transient( 'wpecommon_latest_posts', $latest_posts, DAY_IN_SECONDS );

	return $latest_posts;
}
