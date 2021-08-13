<?php
/**
 * Minify request.
 *
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 *
 * @package Hummingbird\Core\Api\Request
 */

namespace Hummingbird\Core\Api\Request;

use WPMUDEV_Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minify
 */
class Minify extends Request {

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		global $wpmudev_un;

		if ( ! is_object( $wpmudev_un ) && class_exists( 'WPMUDEV_Dashboard' ) && method_exists( 'WPMUDEV_Dashboard', 'instance' ) ) {
			$wpmudev_un = WPMUDEV_Dashboard::instance();
		}

		$api_key = '';

		if ( defined( 'WPHB_API_KEY' ) ) {
			$api_key = WPHB_API_KEY;
		} elseif ( is_object( $wpmudev_un ) && method_exists( $wpmudev_un, 'get_apikey' ) ) {
			$api_key = $wpmudev_un->get_apikey();
		} elseif ( class_exists( 'WPMUDEV_Dashboard' ) && is_object( WPMUDEV_Dashboard::$api ) && method_exists( WPMUDEV_Dashboard::$api, 'get_key' ) ) {
			$api_key = WPMUDEV_Dashboard::$api->get_key();
		}

		return $api_key;
	}

	/**
	 * Get API URL.
	 *
	 * @param string $path  Endpoint path.
	 *
	 * @return string
	 */
	public function get_api_url( $path = '' ) {
		if ( defined( 'WPHB_USE_LOCAL_SITE' ) && WPHB_USE_LOCAL_SITE ) {
			return get_home_url();
		}

		$url = 'https://m9gnuc7j4d.execute-api.us-east-1.amazonaws.com/hummingbird/';
		return trailingslashit( $url . $path );
	}

	/**
	 * Sign request.
	 */
	protected function sign_request() {
		if ( $this->get_api_key() ) {
			$this->add_header_argument( 'Authorization', 'Basic ' . $this->get_api_key() );
		}
	}

	/**
	 * Get the current Site URL
	 *
	 * @return string
	 */
	public function get_this_site() {
		if ( defined( 'WPHB_API_DOMAIN' ) ) {
			$domain = WPHB_API_DOMAIN;
		} else {
			$domain = network_site_url();
		}

		return $domain;
	}

}
