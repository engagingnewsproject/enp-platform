<?php
/**
 * Plugin Name:  Defender Pro
 * Plugin URI:   https://wpmudev.com/project/wp-defender/
 * Version:      5.2.0
 * Description:  Get regular security scans, vulnerability reports, safety recommendations and customized hardening for your site in just a few clicks. Defender is the analyst and enforcer who never sleeps.
 * Author:       WPMU DEV
 * Author URI:   https://wpmudev.com/
 * WDP ID:       1081723
 * License:      GNU General Public License (Version 2 - GPLv2)
 * Text Domain:  wpdef
 * Network:      true
 * Requires PHP: 7.4
 * Requires at least: 6.4
 *
 * @package WP_Defender
 */

/*
Copyright 2007-2025 Incsub (https://incsub.com)
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
	define( 'DEFENDER_VERSION', '5.2.0' );
}
if ( ! defined( 'DEFENDER_DB_VERSION' ) ) {
	define( 'DEFENDER_DB_VERSION', '5.2.0' );
}
if ( ! defined( 'DEFENDER_SUI' ) ) {
	define( 'DEFENDER_SUI', '2-12-24' );
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
if ( ! defined( 'WP_DEFENDER_BASE_URL' ) ) {
	define( 'WP_DEFENDER_BASE_URL', plugin_dir_url( WP_DEFENDER_FILE ) );
}
if ( ! defined( 'WP_DEFENDER_MIN_PHP_VERSION' ) ) {
	define( 'WP_DEFENDER_MIN_PHP_VERSION', '7.4' );
}
if ( ! defined( 'WP_DEFENDER_PRO_PATH' ) ) {
	define( 'WP_DEFENDER_PRO_PATH', 'wp-defender/wp-defender.php' );
}
if ( ! defined( 'WP_DEFENDER_PRO' ) ) {
	define( 'WP_DEFENDER_PRO', true );
}
if ( ! defined( 'WP_DEFENDER_SUPPORT_LINK' ) ) {
	define( 'WP_DEFENDER_SUPPORT_LINK', 'https://wpmudev.com/hub2/support/#get-support' );
}
if ( ! defined( 'WP_DEFENDER_POT_FILENAME' ) ) {
	define( 'WP_DEFENDER_POT_FILENAME', 'wpdef-default.pot' );
}
if ( ! defined( 'FS_METHOD' ) ) {
	define( 'FS_METHOD', 'direct' );
}
if ( ! defined( 'WP_DEFENDER_DOCS_LINK' ) ) {
	define( 'WP_DEFENDER_DOCS_LINK', 'https://wpmudev.com/docs/wpmu-dev-plugins/defender/' );
}
// If PHP version is downgraded while the plugin is running, deactivate the plugin.
if ( version_compare( PHP_VERSION, WP_DEFENDER_MIN_PHP_VERSION, '<' ) ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active( DEFENDER_PLUGIN_BASENAME ) ) {
		deactivate_plugins( DEFENDER_PLUGIN_BASENAME );
		return;
	}
}

/**
 * Run upgrade process.
 *
 * @since 2.4.6
 */
if ( DEFENDER_PLUGIN_BASENAME !== plugin_basename( __FILE__ ) ) {
	$pro_installed = file_exists( WP_PLUGIN_DIR . '/' . WP_DEFENDER_PRO_PATH );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( WP_DEFENDER_PRO_PATH ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		return;
	} elseif (
		$pro_installed &&
		! defined( 'WP_UNINSTALL_PLUGIN' ) &&
		is_plugin_active( DEFENDER_PLUGIN_BASENAME )
	) {
		deactivate_plugins( DEFENDER_PLUGIN_BASENAME );
		activate_plugin( plugin_basename( __FILE__ ) );
	}
}

require_once WP_DEFENDER_DIR . 'vendor/autoload.php';
// Load Action Scheduler package.
if ( file_exists( WP_DEFENDER_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php' ) ) {
	add_action(
		'plugins_loaded',
		function () {
			require_once WP_DEFENDER_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
		},
		-10 // Don't change the priority to positive number, because to load this before AS initialized.
	);
}

require_once WP_DEFENDER_DIR . 'src/functions.php';
// Create container.
$builder = new \WPMU_DEV\Defender\Vendor\DI\ContainerBuilder();
global $wp_defender_di;
$wp_defender_di = $builder->build();
global $wp_defender_central;
$wp_defender_central = new \WP_Defender\Central();
do_action( 'wp_defender' );
// Initialize bootstrap.
require_once WP_DEFENDER_DIR . 'src/class-bootstrap.php';
$bootstrap = new \WP_Defender\Bootstrap();
if ( method_exists( $bootstrap, 'includes' ) ) {
	$bootstrap->includes();
}

add_action( 'init', array( ( new \WP_Defender\Upgrader() ), 'run' ) );
add_action( 'admin_enqueue_scripts', array( $bootstrap, 'register_assets' ) );
add_filter( 'admin_body_class', array( $bootstrap, 'add_sui_to_body' ), 99 );

register_deactivation_hook( __FILE__, array( $bootstrap, 'deactivation_hook' ) );
register_activation_hook( __FILE__, array( $bootstrap, 'activation_hook' ) );

// Declare incompatibility with WooCommerce Checkout block.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WP_DEFENDER_FILE, false );
		}
	}
);