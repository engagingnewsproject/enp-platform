<?php
/**
 * Manage Hummingbird REST API endpoints
 *
 * @package Hummingbird\Core\Api
 */

namespace Hummingbird\Core\Api;

use Hummingbird\Core\Configs;
use Hummingbird\Core\Utils;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST
 */
class Rest {

	/**
	 * REST API version.
	 *
	 * @var string
	 */
	public $version = '1';

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	public $namespace = 'hummingbird';

	/**
	 * REST constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Get namespace with version.
	 *
	 * @return string
	 */
	protected function get_namespace() {
		return $this->namespace . '/v' . $this->version;
	}

	/**
	 * Register the REST routes.
	 */
	public function register_routes() {
		// Route to return a modules status.
		register_rest_route(
			$this->get_namespace(),
			'/status/(?P<module>[\\w-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_module_status' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'module' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// Route to clear a modules cache.
		register_rest_route(
			$this->get_namespace(),
			'/clear_cache/(?P<module>[\\w-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'clear_module_cache' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'module'              => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			)
		);

		// Test route used to check if API is working.
		register_rest_route(
			$this->get_namespace(),
			'/test',
			array(
				'methods'             => 'POST,GET,PUT,PATCH,DELETE,COPY,HEAD',
				'callback'            => function() {
					return true;
				},
				'permission_callback' => '__return_true',
			)
		);

		// Configs routes.
		register_rest_route(
			$this->get_namespace(),
			'/preset_configs',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_configs' ),
					'permission_callback' => array( $this, 'check_configs_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_configs' ),
					'permission_callback' => array( $this, 'check_configs_permissions' ),
				),
			)
		);
	}

	/**
	 * Check if user has proper permissions (minimum manage_options capability) to use the endpoints.
	 *
	 * @since 3.0.1
	 *
	 * @return bool
	 */
	public function check_configs_permissions() {
		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		return current_user_can( $capability );
	}

	/**
	 * Check if user has proper permissions (minimum edit_posts capability) to use the endpoints.
	 *
	 * @since 2.7.3
	 *
	 * @return bool|WP_Error
	 */
	public function check_permissions() {
		if ( defined( 'WPHB_SKIP_REST_API_AUTH' ) && WPHB_SKIP_REST_API_AUTH ) {
			return true;
		}

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			esc_html__( 'Not enough permissions to access the endpoint.', 'wphb' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Returns the status of a module.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @return mixed
	 */
	public function get_module_status( $request ) {
		$module = $request->get_param( 'module' );

		$available_modules = array(
			'gzip',
			'caching',
		);
		if ( ! in_array( $module, $available_modules, true ) ) {
			return new WP_Error(
				'invalid_module',
				__( 'The requested module status was invalid.', 'wphb' ),
				array(
					'status' => 400,
				)
			);
		}

		$response = array(
			'module_active' => Utils::get_module( $module )->is_active(),
			'data'          => Utils::get_module( $module )->analyze_data(),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Clears the cache of a module.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @return mixed
	 */
	public function clear_module_cache( $request ) {
		$module            = $request->get_param( 'module' );
		$available_modules = array(
			'page_cache',
			'performance',
			'gravatar',
			'minify',
			'cloudflare',
		);

		// Make sure modules cache can be cleared.
		if ( ! in_array( $module, $available_modules, true ) ) {
			return new WP_Error(
				'invalid_module',
				__( 'The requested module was invalid.', 'wphb' ),
				array(
					'status' => 400,
				)
			);
		}

		// Make sure module is active.
		if ( ! Utils::get_module( $module )->is_active() ) {
			return new WP_Error(
				'inactive_module',
				__( 'The requested module is inactive.', 'wphb' ),
				array(
					'status' => 400,
				)
			);
		}

		// Clear the cache of module.
		switch ( $module ) {
			case 'minify':
				$response = array(
					'cache_cleared' => Utils::get_module( $module )->clear_cache( false ),
				);
				break;
			default:
				$response = array(
					'cache_cleared' => Utils::get_module( $module )->clear_cache(),
				);
				break;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Gets the local list of configs.
	 *
	 * @since 3.0.1
	 *
	 * @return array
	 */
	public function get_configs() {
		$stored_configs = get_site_option( 'wphb-preset_configs', false );

		if ( false === $stored_configs ) {
			$configs = new Configs();

			$stored_configs = array( $configs->get_basic_config() );

			update_site_option( 'wphb-preset_configs', $stored_configs );
		}

		return $stored_configs;
	}

	/**
	 * Updates the local list of configs.
	 *
	 * @since 3.0.1
	 *
	 * @param WP_REST_Request $request Class containing the request data.
	 *
	 * @return WP_Error
	 */
	public function set_configs( $request ) {
		$data = json_decode( $request->get_body(), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( '400', esc_html__( 'Missing configs data', 'wphb' ), array( 'status' => 400 ) );
		}

		// We might want to sanitize before this.
		update_site_option( 'wphb-preset_configs', $data );

		return $data;
	}

}
