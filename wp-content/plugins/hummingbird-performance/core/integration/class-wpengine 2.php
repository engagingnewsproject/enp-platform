<?php
/**
 * WP Engine integration.
 *
 * @since 2.1.0
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpengine
 */
class Wpengine {

	/**
	 * Wpengine constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		if ( ! $this->should_run() ) {
			return;
		}

		// Do not cache pages for compatibility reasons.
		add_filter( 'wphb_should_cache_request_pre', '__return_false' );

		// Purge WP Engine cache.
		add_action( 'wphb_clear_cache_url', array( $this, 'purge_cache' ) );
	}

	/**
	 * Check if the integration module should be enabled.
	 *
	 * @since 2.1.0
	 * @return bool
	 */
	private function should_run() {
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! class_exists( '\WpeCommon' ) || ! function_exists( 'wpe_param' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Purge cache.
	 *
	 * @since 2.1.0
	 *
	 * @param string $path  Path to purge for.
	 */
	public function purge_cache( $path = '' ) {
		// Only purge when full cache is cleared.
		if ( ! empty( $path ) ) {
			return;
		}

		if ( method_exists( '\WpeCommon', 'purge_memcached' ) ) {
			\WpeCommon::purge_memcached();
		}

		if ( method_exists( '\WpeCommon', 'purge_varnish_cache' ) ) {
			\WpeCommon::purge_varnish_cache();
		}
	}

}
