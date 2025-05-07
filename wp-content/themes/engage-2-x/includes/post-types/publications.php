<?php
/**
 * Publications post type functionality
 * 
 * This file contains all hooks and functions related to the Publications post type,

**/

/**
 * Sort publications by publication date on taxonomy archive pages
 * 
 * This hook modifies the main query for publication category archive pages
 * to sort publications by their publication_date meta field in descending order.
 * This ensures that publications are displayed newest first on category pages.
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && 
        ($query->is_post_type_archive('publication') || $query->is_tax('publication-categories'))) {
        $query->set('meta_key', 'publication_date');
        $query->set('orderby', array(
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ));
    }
});
