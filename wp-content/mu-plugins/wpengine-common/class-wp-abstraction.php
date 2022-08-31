<?php
/**
 * Wp_Abstraction
 *
 * @package wpengine/common-mu-plugin
 */

namespace wpe\plugin;

/**
 * Class Wp_Abstraction
 */
class Wp_Abstraction {
	/**
	 * WordPress's get_site_url()
	 *
	 * Documentation: https://developer.wordpress.org/reference/functions/get_site_url/
	 *
	 * @return string Url of the site.
	 */
	public function get_site_url() {
		return \get_site_url();
	}

	/**
	 * WordPress's wp_remote_request()
	 *
	 * Documentation: https://developer.wordpress.org/reference/functions/wp_remote_request/
	 *
	 * @param string $url Some url.
	 * @return \Wp_Error|array Response as an array or Wp_Error if request failed.
	 */
	public function wp_remote_request( $url ) {
		return \wp_remote_request( $url );
	}

	/**
	 * WordPress's is_wp_error()
	 *
	 * Documentation: https://developer.wordpress.org/reference/functions/is_wp_error/
	 *
	 * @param mixed $object Some object to check.
	 * @return bool True if object is of instance type \Wp_Error.
	 */
	public function is_wp_error( $object ) {
		return \is_wp_error( $object );
	}

	/**
	 * WordPress's wp_cache_set()
	 *
	 * Documentation: https://developer.wordpress.org/reference/functions/wp_cache_set/
	 *
	 * @param int|string $key The cache key to use for retrieval later.
	 * @param mixed      $data The contents to store in the cache.
	 * @return bool True on success, false on failure.
	 */
	public function wp_cache_set( $key, $data ) {
		return \wp_cache_set( $key, $data );
	}

	/**
	 * WordPress's wp_using_ext_object_cache()
	 *
	 * Documentation: https://developer.wordpress.org/reference/functions/wp_using_ext_object_cache/
	 *
	 * @return bool True on if using object cache, else false.
	 */
	public function wp_using_ext_object_cache() {
		return \wp_using_ext_object_cache();
	}
}
