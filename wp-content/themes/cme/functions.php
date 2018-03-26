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
  'lib/cpt-funders.php',   // Team custom post type
  'lib/cpt-research.php', // Custom post type for research papers
  'lib/enp-shortcodes.php', // Custom shortcodes and widgets for ENP
  'lib/wp-bootstrap-navwalker/wp_bootstrap_navwalker.php',
  'widgets/widget-display-research-resources.php', // ENP Widget for displaying research resources
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);


// redirect to quiz creator dashboard on login
function redirect_to_quiz_dashboard($redirect_to) {

    if(ENP_QUIZ_DASHBOARD_URL) {
        $redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
    }
	return $redirect_to;

}
add_action('login_redirect', 'redirect_to_quiz_dashboard', 10, 1);
add_action('registration_redirect', 'redirect_to_quiz_dashboard', 10, 1);

// redirect to quiz dashboard if logged in and trying to get to the quiz creator
function redirect_to_quiz_dashboard_from_marketing() {
    if(is_user_logged_in() === true && is_page('quiz-creator') && ENP_QUIZ_DASHBOARD_URL) {
        $redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
        wp_redirect($redirect_to);
        exit;
    }
}
add_action('template_redirect', 'redirect_to_quiz_dashboard_from_marketing');


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
			case '#enpquizcreator#' : 	$item->url = is_user_logged_in() ? ENP_QUIZ_DASHBOARD_URL.'user' : site_url('quiz-creator');
                                        $item->title = 'Quiz Creator';

            break;

		}
		$item->url = esc_url( $item->url );
	}
	return $item;
}
