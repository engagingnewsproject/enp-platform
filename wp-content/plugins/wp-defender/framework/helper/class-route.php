<?php
/**
 * Helper to enqueue ajax route, also generate the nonce's.
 *
 * @package Calotes\Helper
 */

namespace Calotes\Helper;

/**
 * Helper to enqueue ajax route, also generate the nonce's.
 */
class Route {

	/**
	 * Array to store registered routes.
	 *
	 * @var array
	 */
	protected static $routes = array();

	/**
	 * Array to store nonce's for registered routes.
	 *
	 * @var array
	 */
	protected static $nonces = array();

	/**
	 * Registers a new route for handling AJAX requests.
	 *
	 * @param  mixed $name  The name of the route.
	 * @param  mixed $category  The category of the route.
	 * @param  mixed $route  The route to be registered.
	 * @param  mixed $callback  The callback function to handle the request.
	 * @param  bool  $nopriv  Whether the route should be accessible without authentication.
	 */
	public static function register_route( $name, $category, $route, $callback, $nopriv = false ) {
		$namespace = self::get_namespace( $category );
		$route     = $namespace . '/' . $route;
		add_action( 'wp_ajax_' . $route, $callback );
		if ( true === $nopriv ) {
			add_action( 'wp_ajax_nopriv_' . $route, $callback );
		}
		if ( ! isset( self::$routes[ $category ] ) ) {
			self::$routes[ $category ] = array();
			self::$nonces[ $category ] = array();
		}
		self::$routes[ $category ][ $name ] = $route;
		self::$nonces[ $category ][ $name ] = wp_create_nonce( $name . $category );
	}

	/**
	 * Retrieves the exported routes and nonce's for the specified categories.
	 *
	 * @param  mixed $categories  The categories for which to export routes and nonce's.
	 *
	 * @return array The exported routes and nonce's.
	 */
	public static function export_routes( $categories ) {
		$routes = self::$routes[ $categories ] ?? array();
		$nonces = self::$nonces[ $categories ] ?? array();

		return array( $routes, $nonces );
	}

	/**
	 * Retrieves the namespace for a given module.
	 *
	 * @param  mixed $module  The module for which to retrieve the namespace.
	 *
	 * @return string The namespace for the specified module.
	 */
	public static function get_namespace( $module ) {
		return 'wp-defender/v1/' . $module;
	}
}