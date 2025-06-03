<?php
/**
 * Theme functions and definitions
 * 
 * This file serves as the main entry point for theme functionality.
 * It loads all required dependencies and initializes the theme.
 * 
 * @package Engage
 */

// Load the Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load core theme functionality
require_once __DIR__ . '/includes/core/timber.php';
require_once __DIR__ . '/includes/core/theme.php';
require_once __DIR__ . '/includes/core/classmaps.php';
require_once __DIR__ . '/includes/core/environment.php';

// Load hooks and filters
require_once __DIR__ . '/includes/hooks/acf.php';
require_once __DIR__ . '/includes/hooks/admin.php';
require_once __DIR__ . '/includes/hooks/assets.php';
require_once __DIR__ . '/includes/hooks/queries.php';
require_once __DIR__ . '/includes/hooks/editor.php';
// require_once __DIR__ . '/includes/hooks/import-export.php';

// Load admin functionality
require_once __DIR__ . '/includes/admin/manage-quizzes.php';

// Load post types and taxonomies
require_once __DIR__ . '/includes/post-types/publications.php';
require_once __DIR__ . '/includes/post-types/research.php';
require_once __DIR__ . '/includes/post-types/press.php';
// Load helper functions
require_once __DIR__ . '/includes/helpers/debug.php';
require_once __DIR__ . '/includes/helpers/mix.php';

// Load frontend functionality
require_once __DIR__ . '/includes/frontend/login.php';
require_once __DIR__ . '/includes/frontend/search.php';
require_once __DIR__ . '/includes/frontend/events.php';

/**
 * Filter the columns for the "Manage Quizzes" admin table.
 * 
 * This filter is required for the custom WP_List_Table implementation in
 * @manage-quizzes.php. It must be registered early (in functions.php)
 * so that WordPress can apply it when building the admin screen.
 * 
 * @see engage-2-x/includes/admin/manage-quizzes.php
 */
add_filter('manage_toplevel_page_manage_quizzes_columns', function($columns) {
    error_log('manage_toplevel_page_manage_quizzes_columns filter called');
    return [
        'cb'              => '',
        'quiz_id'         => 'ID',
        'quiz_title'      => 'Title',
        'quiz_status'     => 'Status',
        'quiz_owner'      => 'Owner',
        'quiz_created_at' => 'Created At',
        'quiz_views'      => 'Views',
		'embed_sites'     => 'Embed Sites',
    ];
});
