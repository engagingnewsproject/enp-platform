<?php
/**
 * Plugin Name: Defender
 * Plugin URI:  https://wpmudev.com/project/wp-defender/
 * Version:     2.4.10
 * Description: Get regular security scans, vulnerability reports, safety recommendations and customized hardening for your site in just a few clicks. Defender is the analyst and enforcer who never sleeps.
 * Author:      WPMU DEV
 * Author URI:  https://wpmudev.com/
 * License:     GNU General Public License (Version 2 - GPLv2)
 * Text Domain: wpdef
 * Network:     true
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! defined( 'DEFENDER_VERSION' ) ) {
	define( 'DEFENDER_VERSION', '2.4.10' );
}
if ( ! defined( 'DEFENDER_DB_VERSION' ) ) {
	define( 'DEFENDER_DB_VERSION', '2.4.10' );
}
if ( ! defined( 'DEFENDER_SUI' ) ) {
	define( 'DEFENDER_SUI', '2-9-6' );
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
require_once WP_DEFENDER_DIR . 'src/functions.php';
//create container
$builder = new \DI\ContainerBuilder();
global $wp_defender_di;
$wp_defender_di = $builder->build();
global $wp_defender_central;
$wp_defender_central = new \WP_Defender\Central();
do_action( 'wp_defender' );
//include routes
require_once WP_DEFENDER_DIR . 'src/bootstrap.php';
$bootstrap = new \WP_Defender\Bootstrap();
$bootstrap->check_if_table_exists();
//init
add_action( 'init', [ $bootstrap, 'init_modules' ], 8 );
//register routes
add_action( 'init', function () {
	require_once WP_DEFENDER_DIR . 'src/routes.php';
}, 9 );

if ( class_exists( 'WP_ClI' ) ) {
	$bootstrap->init_cli_command();
}
//include admin class
require_once WP_DEFENDER_DIR . 'src/class-admin.php';
add_action( 'admin_init', [ ( new \WP_Defender\Admin() ), 'init' ] );
add_action( 'init', [ ( new \WP_Defender\Upgrader() ), 'run' ] );
add_action( 'admin_enqueue_scripts', [ $bootstrap, 'register_assets' ] );
add_filter( 'admin_body_class', [ $bootstrap, 'add_sui_to_body' ], 99 );

register_deactivation_hook( __FILE__, [ $bootstrap, 'deactivation_hook' ] );
register_activation_hook( __FILE__, [ $bootstrap, 'activation_hook' ] );
