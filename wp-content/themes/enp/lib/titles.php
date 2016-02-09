<?php

namespace Roots\Sage\Titles;

/**
 * Page titles
 */
function title() {
  if (is_home()) {
    if (get_option('page_for_posts', true)) {
      return get_the_title(get_option('page_for_posts', true));
    } else {
      return __('Latest Posts', 'sage');
    }
  } elseif (is_archive()) {
    return get_the_archive_title();
  } elseif (is_search()) {
    return sprintf(__('Search Results for %s', 'sage'), get_search_query());
  } elseif (is_404()) {
    return __('Not Found', 'sage');
  } else {
    return get_the_title();
  }
}

add_filter( 'get_the_archive_title', __NAMESPACE__ . '\\remove_archive_title_label');
function remove_archive_title_label($title) {

	if ( is_category() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_archive() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() ) {
		$title = single_tag_title( '', false );
	} elseif ( is_year() ) {
		$title = get_the_date( '', false );
	} elseif ( is_month() ) {
		$title = get_the_date( '', false );
	} elseif ( is_day() ) {
		$title = get_the_date( '', false );
	} elseif ( is_post_type_archive() ) {
		$title = post_type_archive_title( '', false );
	} elseif ( is_author() ) {
		$title = '<span class="vcard">' . get_the_author() . '</span>' ;
	}
	return $title;
}
