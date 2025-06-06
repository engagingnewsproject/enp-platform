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
    if (!is_admin() && $query->is_main_query() && 
        ($query->is_post_type_archive('press') || $query->is_tax('press-categories'))) {
        
        $query->set('meta_key', 'press_article_publication_date');
        $query->set('orderby', array(
            'meta_value_num' => 'DESC',
            'date' => 'DESC'  // fallback to post date
        ));
    }
});