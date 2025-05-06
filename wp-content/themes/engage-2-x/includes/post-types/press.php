<?php
/**
 * Press post type functionality
 * 
 * This file contains all hooks and functions related to the Press post type,

**/

/**
 * Sort press by publication date on taxonomy archive pages
 * 
 * This hook modifies the main query for press category archive pages
 * to sort press by their publication_date meta field in descending order.
 * This ensures that press are displayed newest first on category pages.
 */
add_action('pre_get_posts', function($query) {
    // Only modify the main query on the main press archive page
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('press')) {
        $query->set('meta_key', 'press_article_publication_date');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }
});