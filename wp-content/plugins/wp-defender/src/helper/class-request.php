<?php
/**
 * Request Helper.
 *
 * This helper class provides utility methods for handling HTTP requests.
 *
 * @package WP_Defender\Helper
 */

namespace WP_Defender\Helper;

/**
 * Request Helper Class.
 */
class Request {
	/**
	 * Get the current request URI.
	 *
	 * @return string
	 */
	public static function get_request_uri(): string {
		// Don't use defender_get_data_from_request() for fetching REQUEST_URI.
		return isset( $_SERVER['REQUEST_URI'] )
			? urldecode( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: '';
	}
}