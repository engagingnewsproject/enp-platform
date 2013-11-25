<?php

include("self-service-poll/include/functions-poll.php");

/*this function controls the meta titles display*/
if ( ! function_exists( 'elegant_titles' ) ){
	function elegant_titles() {
		global $shortname;

		$sitename = get_bloginfo('name');
		$site_description = get_bloginfo('description');

		#if the title is being displayed on the homepage
		if (is_home() || is_front_page()) {
			if (et_get_option($shortname.'_seo_home_title') == 'on') echo et_get_option($shortname.'_seo_home_titletext');
			else {
				$seo_home_type = et_get_option( $shortname . '_seo_home_type' );
				$seo_home_separate = et_get_option($shortname.'_seo_home_separate');

				if ( $seo_home_type == 'BlogName | Blog description' ) echo $sitename . esc_html( $seo_home_separate ) . $site_description;
				if ( $seo_home_type == 'Blog description | BlogName') echo $site_description . esc_html( $seo_home_separate ) . $sitename;
				if ( $seo_home_type == 'BlogName only') echo $sitename;
			}
		}
		#if the title is being displayed on single posts/pages
		if ( ( is_single() || is_page() ) && ! is_front_page() ) {
			global $wp_query;
			$postid = $wp_query->post->ID;
			$key = et_get_option($shortname.'_seo_single_field_title');
			$exists3 = get_post_meta($postid, ''.$key.'', true);
					if (et_get_option($shortname.'_seo_single_title') == 'on' && $exists3 !== '' ) echo $exists3;
					else {
						$seo_single_type = et_get_option($shortname.'_seo_single_type');
						$seo_single_separate = et_get_option($shortname.'_seo_single_separate');
						if ( $seo_single_type == 'BlogName | Post title' ) echo $sitename . esc_html( $seo_single_separate );# . wp_title('',false,'');
						if ( $seo_single_type == 'Post title | BlogName' ) echo wp_title('',false,'');# . esc_html( $seo_single_separate ) . $sitename;
						if ( $seo_single_type == 'Post title only' ) echo wp_title('',false,'');
					}

		}
		#if the title is being displayed on index pages (categories/archives/search results)
		if (is_category() || is_archive() || is_search()) {
			$seo_index_type = et_get_option($shortname.'_seo_index_type');
			$seo_index_separate = et_get_option($shortname.'_seo_index_separate');
			if ( $seo_index_type == 'BlogName | Category name' ) echo $sitename . esc_html( $seo_index_separate ); #. wp_title('',false,'');
			if ( $seo_index_type == 'Category name | BlogName') echo wp_title('',false,'') . esc_html( $seo_index_separate );# . $sitename;
			if ( $seo_index_type == 'Category name only') echo wp_title('',false,'');
		}
	}
}

?>