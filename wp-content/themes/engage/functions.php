<?php

include 'vendor/autoload.php';

Timber::$dirname = array('templates');

use Engage\Managers\Globals;
use Engage\Managers\Login;
use Engage\Managers\Permalinks;
use Engage\Managers\Queries;
use Engage\Managers\Structures\PostTypes\PostTypes;
use Engage\Managers\Structures\Taxonomies\Taxonomies;
use Engage\Managers\Theme;
use Engage\Managers\TinyMCE;

// Cache twig in staging and production.
if(strpos(get_home_url(), '.com') === false || !in_array(getenv('WP_APP_ENV'), ['production', 'staging'], true )) {
    // on dev, don't cache it
    $engageEnv = 'DEV';
    $cacheTime = false;
} else {
	$engageEnv = 'PROD';
    // we're on a live site since it ends in `.com`
    // we can set this as an array and have the cache be different for logged in vs logged out
    $cacheTime = [
        MINUTE_IN_SECONDS * 5, // logged out, 5 min cache
        false // if logged in, no cache
    ];
}
define('ENGAGE_ENV', $engageEnv);
define('ENGAGE_PAGE_CACHE_TIME', $cacheTime);

// Start the site
add_action('after_setup_theme', function () {
	$managers = [
		new Globals(),
		new Login(),
		new Permalinks(),
		new Queries(),
		new PostTypes(['Research', 'Blogs', 'Announcement', 'Team', 'Funders', 'Board']),
		new Taxonomies(['Verticals']),
		new TinyMCE()
	];
    add_theme_support('post-thumbnails');

    new Theme($managers);
});


if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

add_filter('pre_get_posts', 'tribe_change_event_order', 99);

// When all previous events are viewable in one page, the events will
// be sorted from most recent to oldest
function tribe_change_event_order($query)
{
    $past_ajax = (defined('DOING_AJAX') && DOING_AJAX && $_REQUEST['tribe_event_display'] === 'past') ? true : false;

    if ($query->get('posts_per_page') == -1 && (tribe_is_past() || $past_ajax)) {
        $query->set('orderby', 'date');
        $query->set('order', 'ASC');
        add_filter('tribe_get_events_title', 'tribe_alter_event_archive_titles', 11, 2);
    }

    return $query;
}


function tribe_alter_event_archive_titles( $original_recipe_title, $depth ) {
    // If we are displaying all previous events, we still want the date range of events
    // to be from oldest to most recent despite the order of the posts being the opposite.
    // This is done by switching the order of the dates in the Events title string.
    $dates = explode(" - ", $original_recipe_title);
    $dates[0] = str_replace("Events for ", "", $dates[0]);

    $title = sprintf( __( 'Events for %1$s - %2$s', 'the-events-calendar' ), $dates[1], $dates[0] );
    return $title;
}

// Some code for navbar?
// function register_my_menu() {
//   register_nav_menu('new-menu',__( 'Test Menu CHRIS' ));
// }
// add_action( 'init', 'register_my_menu' );
