<?php

namespace Roots\Sage\Customizer;

use Roots\Sage\Assets;

/**
 * Add postMessage support
 */
function customize_register($wp_customize) {
  $wp_customize->get_setting('blogname')->transport = 'postMessage';
}
add_action('customize_register', __NAMESPACE__ . '\\customize_register');

/**
 * Customizer JS
 */
function customize_preview_js() {
  wp_enqueue_script('sage/customizer', Assets\asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
}
add_action('customize_preview_init', __NAMESPACE__ . '\\customize_preview_js');

/*
* Customize Login Logo
*/
/* WordPress Overrides */
function enp_login_logo() { ?>
    <style type="text/css">
        .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/dist/images/cme-logo.png) !important;
            background-size: 315px !important;
            width: 315px!important;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\\enp_login_logo' );
function enp_login_logo_url() {
	return home_url();
}
add_filter('login_headerurl', __NAMESPACE__ . '\\enp_login_logo_url');