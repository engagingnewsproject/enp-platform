<?php
/**
 * WP Engine Cache Adaptor
 *
 * @package wpengine/common-mu-plugin
 */

namespace wpe\plugin;

/**
 * A class for comm-ing with the wpe cache plugin
 */
class Wpe_Cache_Adaptor {
	/**
	 * The instance of this class
	 *
	 * @var Wpe_Cache_Adaptor
	 */
	private static $instance = null;

	/**
	 * Singleton Method
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wpe_Cache_Adaptor();
		}

		return self::$instance;
	}

	/**
	 * Method to determine if the cache plugin is present on this install
	 */
	public function is_cache_plugin_present() {
		return class_exists( '\wpengine\cache_plugin\WpeCachePage' );
	}

	/**
	 * Method to retrieve the path to the clear all caches endpoint
	 */
	public function get_clear_all_caches_path() {
		if ( ! $this->is_cache_plugin_present() ) {
			return '';
		}
		return \wpengine\cache_plugin\RegisterRestEndpoint::get_clear_all_caches_path();
	}

	/**
	 * Method to retrieve the path to the rate limit status endpoint
	 */
	public function get_rate_limit_status_path() {
		if ( ! $this->is_cache_plugin_present() ) {
			return '';
		}
		return \wpengine\cache_plugin\RegisterRestEndpoint::get_rate_limit_status_path();
	}
}
