<?php

/*
Plugin Name: Ninja Forms - Addon Manager
Version: 3.0.13
Description: Install Ninja Forms add-ons with a single click.
Author: The WP Ninjas
Author URI: http://ninjaforms.com

Copyright 2018 Saturday Drive, Inc.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if( version_compare( PHP_VERSION, '5.3', '>=' ) ) {

    require_once( plugin_dir_path( __FILE__ ) . 'bootstrap.php' );

    \NinjaFormsAddonManager\Plugin::getInstance()->setup(  '3.0.13', __FILE__  );

} else {

  /**
 	 * Display an error notice if the PHP version is lower than 5.3.
 	 *
 	 * @return void
 	 */
	function ninja_forms_addon_manager_below_php_version_notice() {
 		if ( current_user_can( 'activate_plugins' ) ) {
 			echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by Ninja Forms Addon Manager. Please contact your host and request that your version be upgraded to 5.3 or later.', 'ninja-forms-addon-manager' ) . '</p></div>';
 		}
 	}
 	add_action( 'admin_notices', 'ninja_forms_addon_manager_below_php_version_notice' );

}

/**
 * Whitelist request to NinjaForms.com
 * @link https://core.trac.wordpress.org/ticket/24646
 */
