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
		new PostTypes(['Research', 'CaseStudy', 'Announcement', 'Team', 'Funders', 'Board']),
		new Taxonomies(['Verticals']),
		new TinyMCE()
	];
    add_theme_support('post-thumbnails');

    new Theme($managers);
});


if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

register_sidebar( array(
        'name'          => 'Newsletter',
        'id'            => 'newsletter',
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '<h4 class="widget__title">',
        'after_title'   => '</h4>',
    ) );

/*Some code for navbar?
function register_my_menu() {
  register_nav_menu('new-menu',__( 'Test Menu CHRIS' ));
}
add_action( 'init', 'register_my_menu' );
