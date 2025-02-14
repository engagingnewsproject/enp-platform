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
use Engage\Models\LandingPage;

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
        'blogs'			=> ResearchArticle::class,
        'team' 			=> Teammate::class,
        'board'			=> BoardMember::class,
        'event'			=> Event::class,
        'page' 			=> function (\WP_Post $post) {
            // Get the template file for the current post/page
            $template = get_page_template_slug($post->ID);
            if ($template === 'page-press.php') {
                return Press::class;
            } elseif ($template === 'page-landing.php') {
                return LandingPage::class;
            }
        },
    ];
    return array_merge($classmap, $custom_classmap);
});

// Cache twig in staging and production.
if(strpos(get_home_url(), '.com') === false || !in_array(getenv('WP_APP_ENV'), ['production', 'staging'], true)) {
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
        new Taxonomies([]),
        new TinyMCE()
    ];
    
    add_theme_support('post-thumbnails');
    
    new Theme($managers);
});

// is_plugin_active() is defined in /wp-admin/includes/plugin.php,
// so this is only available from within the admin pages,
// and any references to this function must be hooked to admin_init or a later action.
// If you want to use this function from within a template, you will need to manually require plugin.php
include_once(ABSPATH .'wp-admin/includes/plugin.php');

// Include the menu export/import feature
require_once get_template_directory() . '/inc/menu-export-import.php';

// ACF settings
if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    // Set custom load and save paths for ACF JSON
    add_filter('acf/settings/save_json', function() {
        return get_stylesheet_directory() . '/acf-json';
    });
    
    add_filter('acf/settings/load_json', function($paths) {
        // Clear the default ACF JSON folder
        unset($paths[0]);
        // Add your custom path
        $paths[] = get_stylesheet_directory() . '/acf-json';
        return $paths;
    });
        
    add_filter('acf/settings/show_admin', '__return_true');
    add_filter('acf/settings/json', '__return_true');

}

if (! is_admin() && is_plugin_active('the-events-calendar/the-events-calendar.php')) { // Function will only be executed on the front end of the site and not in WordPress admin.
    add_filter('the_posts', 'tribe_past_reverse_chronological', 100);
    // // When viewing previous events, they will be shown from most recent to oldest
    function tribe_past_reverse_chronological($post_object)
    {
        $past_ajax = (defined('DOING_AJAX') && DOING_AJAX && $_REQUEST['tribe_event_display'] === 'past') ? true : false;
        
        if (tribe_is_past() || $past_ajax) {
            $event_title = tribe_get_events_title(); // This should retrieve the actual title
            $dates = get_dates_from_title($event_title); // Pass the correct title to the function
            $current_date = date('m-d-Y');
            
            // Skip processing if the title does not contain a date range
            if (count($dates) < 2) {
                error_log('Skipping processing as title does not contain a date range: ' . $event_title);
                return $post_object; // Exit early if the dates array is not as expected
            }

            if ($dates[1] < $current_date) {
                $post_object = array_reverse($post_object);
                add_filter('tribe_get_events_title', 'tribe_alter_event_archive_titles', 11, 2);

                // Debugging: Log the reversed posts object and dates
                error_log('Reversed post object: ' . print_r($post_object, true));
                error_log('Dates array: ' . print_r($dates, true));
                error_log('Current date: ' . $current_date);
            } else {
                error_log('Condition not met. Dates array: ' . print_r($dates, true) . ', Current date: ' . $current_date);
            }
        }
        
        return $post_object;
    }
    
    function tribe_alter_event_archive_titles($original_recipe_title, $depth)
    {
        // If we are displaying previous events, we still want the date range of events
        // to be from oldest to most recent despite the order of the posts being the opposite.
        // This is done by switching the order of the dates in the Events title string.
        $dates = get_dates_from_title($original_recipe_title);
        // Ensure that $dates has at least two elements before accessing them
        if (count($dates) < 2) {
            error_log('Unexpected dates array format in title alteration: ' . print_r($dates, true));
            return $original_recipe_title; // Return the original title if dates are not as expected
        }
        $title = sprintf(__('Events for %1$s - %2$s', 'the-events-calendar'), $dates[1], $dates[0]);
        return $title;
    }
    function get_dates_from_title($date_string)
    {
        // Helper function to extract the start and end date ranges of a subset of events
        // from the title shown
        $dates = explode(' - ', $date_string);
        $dates[0] = str_replace('Events for ', '', $dates[0]);

        // Check if the title doesn't contain a date range
        if (count($dates) < 2) {
            error_log('Processed title without date range: ' . $date_string);
            return $dates;
        }

        error_log('Processed dates from title: ' . print_r($dates, true));

        return $dates;
    }
}
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

add_post_type_support('page', 'excerpt');

// Dump twig Functions that Timber provides
/*
add_filter('timber/twig/functions', function ($functions) {
    var_dump($functions);

    return $functions;
});
*/

// Redirect users to a specific URL after login
function custom_login_redirect($redirect_to, $request, $user)
{
    // Check if the user is an administrator
    if (isset($user->roles) && is_array($user->roles) && in_array('administrator', $user->roles)) {
        // Redirect administrators to the dashboard
        return admin_url();
    } else {
        // Redirect other users to a different URL
        return home_url('/enp-quiz/dashboard/user/'); // Change 'your-custom-page' to your desired URL
    }
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// disables the ACF field group editing interface if the environment is set to production
add_filter('acf/settings/show_admin', function ($show_admin) {
    if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'production') {
        return false;
    }
    return $show_admin;
});

// Add a custom link next to "Terms and Conditions" on the registration page
function add_custom_link_to_registration_page() {
    // Check if we are on the registration page
    if (isset($_GET['action']) && $_GET['action'] === 'register') {
        echo '
        <div class="terms-page-link" style="text-align: center">
            <a href="/terms-and-conditions/" rel="custom-link">Terms and Conditions</a>
        </div>';
    }
}
add_action('login_footer', 'add_custom_link_to_registration_page');

// Add a custom link next to "Terms and Conditions" on the login page
function add_custom_link_to_login_page() {
    // Check if we are on the login page
    if (!isset($_GET['action']) || $_GET['action'] === 'login') {
        echo '
        <div class="terms-page-link" style="text-align: center">
            <a href="/terms-and-conditions/" rel="custom-link">Terms and Conditions</a>
        </div>';
    }
}
add_action('login_footer', 'add_custom_link_to_login_page');


// This filter allows you to customize the title displayed 
// for each layout in the Flexible Content field based on field values or any other logic
add_filter('acf/fields/flexible_content/layout_title', function ($title, $field, $layout, $i) {
    if ($layout['name'] === 'wysiwyg' || $layout['name'] === 'highlights' || $layout['name'] === 'research_initiatives' || $layout['name'] === 'parallax') { // Check if the layout is the "wysiwyg" layout
        // Access the "header" subfield inside the "header_group"
        $header_group = get_sub_field('header_group'); // Get the group field
        if ($header_group && isset($header_group['header']) && $header_group['header'] != '') { // Ensure the subfield exists
            $header_title = $header_group['header'];
            $title .= ' - ' . esc_html($header_title); // Append the header title
        }
    }
    return $title;
}, 10, 4);

// Add Custom Styles to the Classic Editor Dropdown
function custom_tinymce_style_formats($init_array) {
    $style_formats = [
        [
            'title' => 'Paragraph 1',
            'block' => 'p',
            'classes' => 'p-1',
            'wrapper' => false,
        ],
        [
            'title' => 'Paragraph 2',
            'block' => 'p',
            'classes' => 'p-2',
            'wrapper' => false,
        ],
        [
            'title' => 'Paragraph 3',
            'block' => 'p',
            'classes' => 'p-3',
            'wrapper' => false,
        ],
    ];

    $init_array['style_formats'] = json_encode($style_formats);
    return $init_array;
}
add_filter('tiny_mce_before_init', 'custom_tinymce_style_formats');

// Enable the Styles Dropdown in the Classic Editor
function add_custom_editor_buttons($buttons) {
    array_unshift($buttons, 'styleselect');
    return $buttons;
}
add_filter('mce_buttons', 'add_custom_editor_buttons');

// Defer or async scripts from plugins
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    // List of script handles to defer
    $defer_scripts = [
        'file_uploads_nfpluginsettings',
        'nf-front-end-deps',
        'nf-front-end',
    ];

    // List of script handles to async
    $async_scripts = [
        'plugin-script-handle-3',
    ];

    if (in_array($handle, $defer_scripts)) {
        return str_replace('<script ', '<script defer ', $tag);
    }

    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }

    return $tag;
}, 10, 3);

// Defer stylesheets from plugins
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    // List of stylesheet handles to defer
    $defer_styles = [
        'nf-display',
        'wp-block-library',
    ];

    if (in_array($handle, $defer_styles)) {
        return str_replace(
            '<link ',
            '<link media="print" onload="this.media=\'all\'" ',
            $tag
        );
    }
    return $tag;
}, 10, 3);


/* DEV SNIPPETS */

// List handles of scripts enqueued by plugins
/*
    add_action('wp_footer', function () {
        global $wp_scripts;
        foreach ($wp_scripts->queue as $handle) {
            echo '<p>Script handle: ' . $handle . '</p>';
        }
    });
*/

// List handles of stylesheets enqueued by plugins
/*
    add_action('wp_footer', function () {
        global $wp_styles;
        foreach ($wp_styles->queue as $handle) {
            echo '<p>Stylesheet handle: ' . $handle . '</p>';
        }
    });
*/

// Check timber version
/*
    if (version_compare(Timber::$version, '2.0.0', '>=')) {
        var_dump( 'Timber 2.x is installed.' );
    }
*/
