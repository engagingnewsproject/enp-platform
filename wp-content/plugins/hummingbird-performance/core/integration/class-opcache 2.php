<?php
/**
 * Opcache integration class.
 *
 * @since 2.1.0
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

use Exception;
use Hummingbird\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Opcache
 */
class Opcache {
	/**
	 * Module instance.
	 *
	 * @since 2.7.1
	 * @var Opcache|null
	 */
	private static $instance = null;

	/**
	 * Return module instance.
	 *
	 * @since 2.7.1
	 *
	 * @return Opcache|null
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Opcache constructor.
	 *
	 * @since 2.1.0
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Initialize module.
	 *
	 * We need to init the module via admin_init, because we need to make sure all Hummingbird modules have
	 * been properly loaded prior to checking the status.
	 *
	 * @since 2.6.0
	 */
	public function init() {
		// Page caching is not enabled.
		if ( ! apply_filters( 'wp_hummingbird_is_active_module_page_cache', false ) ) {
			return;
		}

		$integrations = Settings::get_setting( 'integrations', 'page_cache' );

		// Integration not enabled.
		if ( ! isset( $integrations['opcache'] ) || ! $integrations['opcache'] ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'wphb_clear_cache_url', array( $this, 'purge_cache' ) );
	}

	/**
	 * Check if opcache is enabled on the server.
	 *
	 * @since 2.1.0
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return false;
		}

		try {
			$opcache = opcache_get_status();
			if ( isset( $opcache['opcache_enabled'] ) ) {
				return $opcache['opcache_enabled'];
			}
		} catch ( Exception $e ) {
			return false;
		}

		return false;
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

		if ( ! function_exists( 'opcache_reset' ) ) {
			return;
		}

		opcache_reset();
	}

}
