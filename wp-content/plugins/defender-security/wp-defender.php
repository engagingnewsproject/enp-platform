<?php
/**
 * Plugin Name: Defender
 * Plugin URI:  https://wpmudev.com/project/wp-defender/
 * Version:     2.6.5
 * Description: Get regular security scans, vulnerability reports, safety recommendations and customized hardening for your site in just a few clicks. Defender is the analyst and enforcer who never sleeps.
 * Author:      WPMU DEV
 * Author URI:  https://wpmudev.com/
 * License:     GNU General Public License (Version 2 - GPLv2)
 * Text Domain: wpdef
 * Network:     true
 */
/*
Copyright 2007-2021 Incsub (http://incsub.com)
Author - Hoang Ngo, Anton Shulga

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! defined( 'DEFENDER_VERSION' ) ) {
	define( 'DEFENDER_VERSION', '2.6.5' );
}
if ( ! defined( 'DEFENDER_DB_VERSION' ) ) {
	define( 'DEFENDER_DB_VERSION', '2.6.5' );
}
if ( ! defined( 'DEFENDER_SUI' ) ) {
	define( 'DEFENDER_SUI', '2-11-1' );
}
if ( ! defined( 'DEFENDER_PLUGIN_BASENAME' ) ) {
	define( 'DEFENDER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'WP_DEFENDER_DIR' ) ) {
	define( 'WP_DEFENDER_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WP_DEFENDER_FILE' ) ) {
	define( 'WP_DEFENDER_FILE', __FILE__ );
}
if ( ! defined( 'WP_DEFENDER_MIN_PHP_VERSION' ) ) {
	define( 'WP_DEFENDER_MIN_PHP_VERSION', '5.6.20' );
}

/**
 * Run upgrade process.
 *
 * @since 2.4.6
 */
if ( DEFENDER_PLUGIN_BASENAME !== plugin_basename( __FILE__ ) ) {
	$pro_installed = false;
	if ( file_exists( WP_PLUGIN_DIR . '/wp-defender/wp-defender.php' ) ) {
		$pro_installed = true;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( 'wp-defender/wp-defender.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		return;
	} elseif ( $pro_installed && is_plugin_active( DEFENDER_PLUGIN_BASENAME ) ) {
		deactivate_plugins( DEFENDER_PLUGIN_BASENAME );
		activate_plugin( plugin_basename( __FILE__ ) );
	}
}

require_once WP_DEFENDER_DIR . 'vendor/autoload.php';

add_action(
	'plugins_loaded',
	function() {
		require_once WP_DEFENDER_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
	},
	-10 // Don't change the priority to positive number, because to load this before AS initialized.
);

require_once WP_DEFENDER_DIR . 'src/functions.php';
// Create container.
$builder = new \DI\ContainerBuilder();
global $wp_defender_di;
$wp_defender_di = $builder->build();
global $wp_defender_central;
$wp_defender_central = new \WP_Defender\Central();
do_action( 'wp_defender' );
// Include routes.
require_once WP_DEFENDER_DIR . 'src/bootstrap.php';
$bootstrap = new \WP_Defender\Bootstrap();
$bootstrap->check_if_table_exists();
// Initialize modules.
add_action( 'init', [ $bootstrap, 'init_modules' ], 8 );
// Register routes.
add_action( 'init', function () {
	require_once WP_DEFENDER_DIR . 'src/routes.php';
}, 9 );

if ( class_exists( 'WP_ClI' ) ) {
	$bootstrap->init_cli_command();
}
// Include admin class.
require_once WP_DEFENDER_DIR . 'src/class-admin.php';
add_action( 'admin_init', [ ( new \WP_Defender\Admin() ), 'init' ] );
add_action( 'init', [ ( new \WP_Defender\Upgrader() ), 'run' ] );
add_action( 'admin_enqueue_scripts', [ $bootstrap, 'register_assets' ] );
add_filter( 'admin_body_class', [ $bootstrap, 'add_sui_to_body' ], 99 );

register_deactivation_hook( __FILE__, [ $bootstrap, 'deactivation_hook' ] );
register_activation_hook( __FILE__, [ $bootstrap, 'activation_hook' ] );
