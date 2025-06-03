<?php
/**
 * Theme initialization and setup
 */

use Engage\Managers\Globals;
use Engage\Managers\Login;
use Engage\Managers\Permalinks;
use Engage\Managers\Queries;
use Engage\Managers\Structures\PostTypes\PostTypes;
use Engage\Managers\Structures\PostTypes\Quiz;
use Engage\Managers\Structures\Taxonomies\Taxonomies;
use Engage\Managers\Theme;
use Engage\Managers\TinyMCE;

// Initialize theme
add_action('after_setup_theme', function () {
    $managers = [
        new Globals(),
        new Login(),
        new Permalinks(),
        new Queries(),
        new PostTypes([
            'Research', 
            'Blogs', 
            'Announcement', 
            'Team', 
            'Funders', 
            'Board', 
            'Publications', 
            'Press',
            'Quiz'
        ]),
		new Taxonomies(['Verticals']),
        new TinyMCE()
    ];

    add_theme_support('post-thumbnails');
    add_theme_support('align-wide');
    add_post_type_support('page', 'excerpt');

    new Theme($managers);
}); 