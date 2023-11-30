<?php
// Load the Composer autoloader and initialize Timber. 
require_once __DIR__ . '/vendor/autoload.php';

Timber\Timber::init();

Timber::$dirname = [ 'templates' ];

/**
* By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
* No prob! Just set this value to true
*/
Timber::$autoescape = false;

use Engage\Managers\Globals;
use Engage\Managers\Login;
use Engage\Managers\Permalinks;
use Engage\Managers\Queries;
use Engage\Managers\Structures\PostTypes\PostTypes;
use Engage\Managers\Structures\Taxonomies\Taxonomies;
use Engage\Managers\Theme;
use Engage\Managers\TinyMCE;
// Models
use Engage\Models\BoardMember;
use Engage\Models\Event;
use Engage\Models\ResearchArticle;
use Engage\Models\TeamArchive;
use Engage\Models\Teammate;
use Engage\Models\Press;
use Engage\Models\VerticalLanding;

/**
 * Term class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/term/classmap', function ($classmap) {
	$custom_classmap = [
		// 'verticals' 	=> TileArchive::class,
		'research'	=> ResearchArticle::class,
		'team'			=> TeamArchive::class,
	];
	return array_merge($classmap, $custom_classmap);
});

/**
 * Post class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/post/classmap', function ($classmap) {
	$custom_classmap = [
		'research'	=> ResearchArticle::class,
		'team' 			=> Teammate::class,
		'board'			=> BoardMember::class,
		'event'			=> Event::class,
		'page' 			=> function (\WP_Post $post) {
			// Get the template file for the current post/page
			$template = get_page_template_slug($post->ID);
			if ($template === 'page-press.php') {
				return Press::class;
			} elseif ($template === 'page-landing.php') {
				return VerticalLanding::class;
			}
		},
	];
	return array_merge($classmap, $custom_classmap);
});

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

// use ACF options info site wide, https://timber.github.io/docs/guides/acf-cookbook/#use-options-info-site-wide
add_filter('timber/context', 'engage_timber_context');
function engage_timber_context($context) {
	$context['options'] = get_fields('option');
	return $context;
}

if ( ! is_admin() ) { // Function will only be executed on the front end of the site and not in WordPress admin.
	add_filter('the_posts', 'tribe_past_reverse_chronological', 100);
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
}
// prevent search results pages from being indexed by search engines
// https://wordpress.stackexchange.com/a/55645
add_action('wp_head', 'add_meta_tags');
function add_meta_tags() {
	if (is_search()) {
		$search_keyword = get_search_query();
		echo '<meta name="robots" content="noindex" />';
	}
}

add_theme_support('align-wide');

add_post_type_support( 'page', 'excerpt' );

// Dump twig Functions that Timber provides
/*
add_filter('timber/twig/functions', function ($functions) {
	var_dump($functions);
	
	return $functions;
});
*/

// check timber version
// if (version_compare(Timber::$version, '2.0.0', '>=')) {
	//     var_dump( 'Timber 2.x is installed.' );
	// }