<?php
/**
 * Debugging and logging functions
 * 
 * This file contains various debugging and logging functions

**/

/* DEBUG TOOLS */
// Set this to true to enable permalink debugging
define('DEBUG_PERMALINKS', false);

if (defined('DEBUG_PERMALINKS') && DEBUG_PERMALINKS === true) {
	// Debug rewrite rules
	add_filter('rewrite_rules_array', function ($rules) {
		error_log('=== START REWRITE RULES ===');
		error_log(print_r($rules, true));
		error_log('=== END REWRITE RULES ===');
		return $rules;
	});

	// Debug query variables
	add_action('parse_request', function ($wp) {
		error_log('=== START QUERY VARS ===');
		error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
		error_log('Query Variables: ' . print_r($wp->query_vars, true));
		error_log('=== END QUERY VARS ===');
	});
}

// Clear research filter menu transient cache
// function clear_research_filter_menu_cache() {
//     delete_transient('research-filter-menu');
// }
// add_action('init', 'clear_research_filter_menu_cache');