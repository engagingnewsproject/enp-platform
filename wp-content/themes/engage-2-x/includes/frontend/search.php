<?php
/**
 * Search-related functionality
 * 
 * This file contains all hooks and functions related to search functionality,
 * including search results modifications and SEO settings.
 */

/**
 * Prevent search results pages from being indexed by search engines
 * https://wordpress.stackexchange.com/a/55645
 */
function add_meta_tags() {
    if (is_search()) {
        echo '<meta name="robots" content="noindex" />';
    }
}
add_action('wp_head', 'add_meta_tags'); 