<?php

declare(strict_types=1);

namespace wpengine\cache_plugin;

\wpengine\cache_plugin\check_security();

require_once __DIR__ . '/plugin-rest-paths.php';
require_once __DIR__ . '/wpe-common-adapter.php';
require_once __DIR__ . '/clear-all-caches-controller.php';
require_once __DIR__ . '/date-time-helper.php';

use WP_REST_Request;

RegisterRestEndpoint::initialize();

/**
 * @codeCoverageIgnore
 */
class RegisterRestEndpoint {
	public static $controller;

	public static function initialize() {
		if ( ! WpeCommonAdapter::is_mu_common_plugin_present() ) {
			return;
		}

		self::$controller = new ClearAllCachesController( WpeCommonAdapter::get_instance(), CacheDbSettings::get_instance(), DateTimeHelper::get_instance() );

		add_action(
			'rest_api_init',
			function () {
				self::clear_all_caches_endpoint_setup();
				self::rate_limit_status_endpoint_setup();
			}
		);
	}

	private static function clear_all_caches_endpoint_setup() {
		register_rest_route(
			PluginRestPaths::BASE,
			PluginRestPaths::CLEAR_ALL_CACHES_PATH,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( self::$controller, 'clear_all_caches' ),
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);
	}

	private static function rate_limit_status_endpoint_setup() {
		register_rest_route(
			PluginRestPaths::BASE,
			PluginRestPaths::RATE_LIMIT_STATUS_PATH,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( self::$controller, 'rate_limit_status' ),
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);
	}

	public static function get_clear_all_caches_path() {
		return PluginRestPaths::BASE . PluginRestPaths::CLEAR_ALL_CACHES_PATH;
	}

	public static function get_rate_limit_status_path() {
		return PluginRestPaths::BASE . PluginRestPaths::RATE_LIMIT_STATUS_PATH;
	}
}
