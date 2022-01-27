<?php
/**
 * Plugin Name: WP Engine Cache Plugin
 * Plugin URI:  https://www.wpengine.com
 * Description: WP Engine Cache Plugin
 * Version:     1.0.5
 * Author:      WP Engine
 *
 * @package wpengine\cache_plugin
 */

declare(strict_types=1);
namespace wpengine\cache_plugin;

require_once __DIR__ . '/wpe-cache-plugin/security/security-checks.php';
require_once __DIR__ . '/wpe-cache-plugin/cache-control.php';
require_once __DIR__ . '/wpe-cache-plugin/wpe-admin.php';

\wpengine\cache_plugin\check_security();

define( 'WPE_CACHE_PLUGIN_BASE', __FILE__ );
$plugin_version = get_file_data( WPE_CACHE_PLUGIN_BASE, array( 'Version' => 'Version' ) )['Version'];
define( 'WPE_CACHE_PLUGIN_VERSION', $plugin_version );

$cache_control = new CacheControl( CacheDbSettings::get_instance() );
add_action( 'wp', array( $cache_control, 'wpe_add_cache_header' ) );

add_filter(
	'rest_post_dispatch',
	function( $result, $server, $request ) use ( $cache_control ) {
		$route = $request->get_route();
		$cache_control->send_header_cache_control_api( $route );
		return $result;
	},
	10,
	3
);

if ( is_admin() ) {
	$admin = new WpeAdmin();
	$admin->initialize();
}
include_rest_registration();

function include_rest_registration() {
	include_once __DIR__ . '/wpe-cache-plugin/register-rest-endpoints.php';
}
