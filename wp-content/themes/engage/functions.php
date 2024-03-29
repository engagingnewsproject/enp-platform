<?php
// Load the Composer autoloader and initialize Timber. 
require_once __DIR__ . '/vendor/autoload.php';

$timber = new Timber\Timber();

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */

// Autoload Composer dependencies
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}
// Set Timber's directory
Timber::$dirname = ['templates'];

// Cache twig in staging and production.
if(strpos(get_home_url(), '.com') === false || !in_array(getenv('WP_APP_ENV'), ['production', 'staging'], true )) {
// on dev, don't cache it
    $engageEnv = 'DEV';
    $cacheTime = false;
} else {
    $engageEnv = 'PROD';
    $cacheTime = [
        MINUTE_IN_SECONDS * 5, // logged out, 5 min cache
        false // if logged in, no cache
    ];
}
define('ENGAGE_ENV', $engageEnv);
define('ENGAGE_PAGE_CACHE_TIME', $cacheTime);

// Initialize Timber
add_action('after_setup_theme', 'custom_setup_theme');

function custom_setup_theme() {
    add_theme_support('post-thumbnails');
    add_theme_support('align-wide');

    // Create an array of managers
    $managers = [
        new Engage\Managers\Globals(),
        new Engage\Managers\Login(),
        new Engage\Managers\Permalinks(),
        new Engage\Managers\Queries(),
        new Engage\Managers\Structures\PostTypes\PostTypes(['Research', 'Blogs', 'Announcement', 'Team', 'Funders', 'Board']),
        new Engage\Managers\Structures\Taxonomies\Taxonomies(['Verticals']),
        new Engage\Managers\TinyMCE()
    ];

    // Initialize Theme with managers
    new Engage\Managers\Theme($managers);
}

// is_plugin_active() is defined in /wp-admin/includes/plugin.php, 
// so this is only available from within the admin pages, 
// and any references to this function must be hooked to admin_init or a later action. 
// If you want to use this function from within a template, you will need to manually require plugin.php
include_once(ABSPATH .'wp-admin/includes/plugin.php');

if ( is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    // Add ACF options info site-wide
    add_filter('timber/context', 'engage_timber_context');
}

function engage_timber_context($context) 
{
    $context['options'] = get_fields('option');
    return $context;
}

if ( ! is_admin() && is_plugin_active('the-events-calendar/the-events-calendar.php')) { // Function will only be executed on the front end of the site and not in WordPress admin.
    add_filter('the_posts', 'tribe_past_reverse_chronological', 100);
}
// // When viewing previous events, they will be shown from most recent to oldest
function tribe_past_reverse_chronological ($post_object) {
    $past_ajax = (defined( 'DOING_AJAX' ) && DOING_AJAX && $_REQUEST['tribe_event_display'] === 'past') ? true : false;
   
    if (tribe_is_past() || $past_ajax) {
        $dates = get_dates_from_title('tribe_get_events_title');
        $current_date = date("m-d-Y");

        // If the user navigates from upcoming events to previous events then back to upcoming events,
        // the site will still regard this as a past events query. Thus we ensure the order of upcoming
        // events is not altered.
        if ($dates[1] < $current_date) {
            $post_object = array_reverse($post_object);
            add_filter( 'tribe_get_events_title', 'tribe_alter_event_archive_titles', 11, 2 );
        }
    }
   
    return $post_object;
  }

function tribe_alter_event_archive_titles( $original_recipe_title, $depth ) {
    // If we are displaying previous events, we still want the date range of events
    // to be from oldest to most recent despite the order of the posts being the opposite.
    // This is done by switching the order of the dates in the Events title string.
    $dates = get_dates_from_title($original_recipe_title);
    $title = sprintf( __( 'Events for %1$s - %2$s', 'the-events-calendar' ), $dates[1], $dates[0] );
    return $title;
}

function get_dates_from_title( $date_string ) {
// Helper function to extract the start and end date ranges of a subset of events
    // from the title shown
    $dates = explode(" - ", $date_string);
    $dates[0] = str_replace("Events for ", "", $dates[0]);
    return $dates;
}

// test windows git
// Some code for navbar?
// function register_my_menu() {
//   register_nav_menu('new-menu',__( 'Test Menu CHRIS' ));
// }
// add_action( 'init', 'register_my_menu' );


// prevent search results pages from being indexed by search engines
// https://wordpress.stackexchange.com/a/55645
add_action('wp_head', 'add_meta_tags');
function add_meta_tags()
{

    if (is_search()) {
        $search_keyword = get_search_query();
        echo '<meta name="robots" content="noindex" />';
    }
}

add_theme_support('align-wide');

add_post_type_support( 'page', 'excerpt' );

