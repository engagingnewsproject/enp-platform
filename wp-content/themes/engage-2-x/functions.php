<?php
// Load the Composer autoloader and initialize Timber
require_once __DIR__ . '/vendor/autoload.php';

Timber\Timber::init();

Timber::$dirname = ['templates'];

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
use Engage\Models\URLConstructor;
use Engage\Models\Publication;
use Engage\Models\PressPage;
/**
 * Term class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/term/classmap', function ($classmap) {
	$custom_classmap = [
		'research'	=> ResearchArticle::class,
		'team'		=> TeamArchive::class,
	];
	return array_merge($classmap, $custom_classmap);
});

/**
 * Post class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/post/classmap', function ($classmap) {
	$custom_classmap = [
		'research'		=> ResearchArticle::class,
		'blogs'			=> ResearchArticle::class,
		'team' 			=> Teammate::class,
		'board'			=> BoardMember::class,
		'event'			=> Event::class,
		'publication'	=> Publication::class,
		'press'			=> Press::class,
		'page' 			=> function (\WP_Post $post) {
			// Get the template file for the current post/page
			$template = get_page_template_slug($post->ID);
			if ($template === 'page-press.php') {
				return PressPage::class;
			} elseif ($template === 'page-landing.php') {
				return VerticalLanding::class;
			}
		},
	];
	return array_merge($classmap, $custom_classmap);
});

// Load core theme functionality
require_once __DIR__ . '/includes/core.php';

// Load hooks and filters
require_once __DIR__ . '/includes/hooks/acf.php';
require_once __DIR__ . '/includes/hooks/admin.php';
require_once __DIR__ . '/includes/hooks/assets.php';
require_once __DIR__ . '/includes/hooks/queries.php';
require_once __DIR__ . '/includes/hooks/editor.php';

// Load post types and taxonomies
require_once __DIR__ . '/includes/post-types/publications.php';
require_once __DIR__ . '/includes/post-types/research.php';

// Load helper functions
// require_once __DIR__ . '/includes/helpers/debug.php';

// Load frontend functionality
require_once __DIR__ . '/includes/frontend/login.php';
require_once __DIR__ . '/includes/frontend/search.php';
require_once __DIR__ . '/includes/frontend/events.php';

// Start the site
add_action('after_setup_theme', function () {
	$managers = [
		new Globals(),
		new Login(),
		new Permalinks(),
		new Queries(),
		new PostTypes(['Research', 'Blogs', 'Announcement', 'Team', 'Funders', 'Board', 'Publications', 'Press']),
		new TinyMCE()
	];

	add_theme_support('post-thumbnails');

	new Theme($managers);
});
