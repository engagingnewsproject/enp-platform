<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/assets.php',    // Scripts and stylesheets
  'lib/extras.php',    // Custom functions
  'lib/setup.php',     // Theme setup
  'lib/titles.php',    // Page titles
  'lib/wrapper.php',   // Theme wrapper class
  'lib/customizer.php', // Theme customizer
  'lib/cpt-team.php',   // Team custom post type
  'lib/cpt-research.php', // Custom post type for research papers
  'lib/wp-bootstrap-navwalker/wp_bootstrap_navwalker.php',
  'widgets/widget-display-research-resources.php', // ENP Widget for displaying research resources
  'self-service-quiz/include/functions-quiz.php'  // ENP Create a Quiz app
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);


// redirect to quiz creator dashboard on login
function redirect_to_quiz_dashboard($redirect_to, $request, $user ) {

    if(ENP_QUIZ_DASHBOARD_URL) {
        $redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
    }
	return $redirect_to;

}
add_action('login_redirect', 'redirect_to_quiz_dashboard', 10, 3);

// link to Create a Quiz Landing Page when logged out, Direct to Dashboard when logged in
/*add_filter( 'wp_nav_menu_items', 'add_quiz_creator_link', 10, 2 );
function add_quiz_creator_link( $items, $args ) {


    // we want it second to last
    if (is_user_logged_in() && $args->theme_location == 'secondary_navigation') {
        $items .= '<li><a href="'. wp_logout_url() .'">Log Out</a></li>
                   <li><a href="'. wp_logout_url() .'">Log Out</a></li>';
    }
    elseif (!is_user_logged_in() && $args->theme_location == 'secondary_navigation') {
        $items .= '<li><a href="'. site_url('wp-login.php') .'">Log In</a></li>';
    }
    return $items;
}

add_filter('wp_nav_menu_objects', 'enp_add_menu_items', 10, 3);

function enp_add_menu_items($items, $menu, $args) {
    if($menu->theme_location === 'secondary_navigation') {
        $login_out = new stdClass;
        $login_out->ID = 'log-in-out';
        $login_out->menu_item_parent = 0;
        $login_out->menu_order = 100;
        $login_out->object_id = 'log-in-out';
        $login_out->post_parent = 0;
        if(is_user_logged_in()) {
            $login_out->url = wp_logout_url();
            $login_out->title = 'Log out';
        } else {
            $login_out->url = site_url('wp-login.php');
            $login_out->title = 'Login';
        }


        $items[] = (object) $new_item;
    }
    return $items;
}*/


/* The main code, this replace the #keyword# by the correct links with nonce ect */
add_filter( 'wp_setup_nav_menu_item', 'enp_setup_nav_menu_item' );
function enp_setup_nav_menu_item( $item ) {
	global $pagenow;
	if ( $pagenow != 'nav-menus.php' && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#enp' ) != '' ) {
		$item_url = substr( $item->url, 0, strpos( $item->url, '#', 1 ) ) . '#';

		switch ( $item_url ) {

			case '#enplogin#' : 	$item->url = is_user_logged_in() ? wp_logout_url() : wp_login_url();
                                    $item->title = is_user_logged_in() ? 'Log out' : 'Login';
            break;
			case '#enpquizcreator#' : 	$item->url = is_user_logged_in() ? ENP_QUIZ_DASHBOARD_URL.'user' : site_url('create-a-quiz');
                                        $item->title = 'Quiz Creator';
            break;

		}
		$item->url = esc_url( $item->url );
	}
	return $item;
}
