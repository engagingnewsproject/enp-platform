<?php
/**
 * Research post type functionality
 * 
 * This file contains all hooks and functions related to the Research post type,

**/

// Clear research filter menu cache
function clear_research_filter_menu_cache() {
    delete_transient('research-filter-menu');
}
add_action('save_post', 'clear_research_filter_menu_cache');
add_action('delete_post', 'clear_research_filter_menu_cache');
