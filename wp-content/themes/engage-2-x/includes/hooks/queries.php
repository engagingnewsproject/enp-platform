<?php
/**
 * Query-related hooks and filters
 * 
 * This file contains all hooks and filters related to WordPress queries,
 * including custom post type archives, taxonomies, and search functionality.
 */

/**
 * Sort publications by publication date on taxonomy archive pages
 * 
 * This hook modifies the main query for publication category archive pages
 * to sort publications by their publication_date meta field in descending order.
 * This ensures that publications are displayed newest first on category pages.
 */
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_tax('publication-categories')) {
        $query->set('meta_key', 'publication_date');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }
});

/**
 * Excludes specific categories from the research archive page.
 * - Excludes 'uncategorized' from both archive and category pages
 * - Excludes 'media-ethics' only from the main research archive page
 * 
 * @param WP_Query $query The WordPress query object
 */
function filter_research_archive_posts($query) {
    // Only modify the main query on research archive or category pages
    if (!is_admin() && $query->is_main_query() && 
        (is_post_type_archive('research') || is_tax('research-categories'))) {
        
        $tax_query = array();
        
        // Always exclude uncategorized
        $uncategorized_term = get_term_by('slug', 'uncategorized', 'research-categories');
        if ($uncategorized_term) {
            $tax_query[] = array(
                'taxonomy' => 'research-categories',
                'field' => 'term_id',
                'terms' => $uncategorized_term->term_id,
                'operator' => 'NOT IN'
            );
        }
        
        // Only exclude media-ethics on the main research archive page
        if (is_post_type_archive('research')) {
            $media_ethics_term = get_term_by('slug', 'media-ethics', 'research-categories');
            if ($media_ethics_term) {
                $tax_query[] = array(
                    'taxonomy' => 'research-categories',
                    'field' => 'term_id',
                    'terms' => $media_ethics_term->term_id,
                    'operator' => 'NOT IN'
                );
            }
        }
        
        // If we have tax queries, add them to the query
        if (!empty($tax_query)) {
            // If there's more than one condition, add the AND relation
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            
            // Get existing tax_query if it exists
            $existing_tax_query = $query->get('tax_query');
            if ($existing_tax_query) {
                $tax_query = array_merge($existing_tax_query, $tax_query);
            }
            
            $query->set('tax_query', $tax_query);
        }
    }
}
add_action('pre_get_posts', 'filter_research_archive_posts'); 