<?php
/**
 * Handles all HTTP requests in this plugin.
 *
 * @package Calotes\Helper
 */

namespace Calotes\Helper;

/**
 * Validates and sanitizes HTTP request data.
 */
class HTTP {


	/**
	 * Strips the protocol from a URL.
	 *
	 * @param  string $url  The URL to strip the protocol from.
	 *
	 * @return string The URL without the protocol.
	 */
	public static function strips_protocol( $url ) {
		$parts = wp_parse_url( $url );

		$host = $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : null );

		return rtrim( $host, '/' );
	}

	/**
	 * Retrieves a value from the $_GET super global array based on the provided key.
	 *
	 * @param  mixed $key  The key to search for in the $_GET array.
	 * @param  mixed $default_name  The default value to return if key is not found.
	 * @param  bool  $strict  Flag to determine if strict comparison should be used.
	 *
	 * @return mixed The retrieved value after applying sanitization if needed.
	 */
	public static function get( $key, $default_name = null, bool $strict = false ) {
		$value = defender_get_data_from_request( $key, 'g' ) ?? $default_name;
		if ( true === $strict && empty( $value ) ) {
			$value = $default_name;
		}
		if ( is_array( $value ) ) {
			$value = defender_sanitize_data( $value );
		} elseif ( is_string( $value ) ) {
			$value = sanitize_textarea_field( $value );
		}

		return $value;
	}

	/**
	 * Retrieves a value from the $_POST super global array based on the provided key.
	 *
	 * @param  mixed $key  The key to search for in the $_POST array.
	 * @param  mixed $default_name  The default value to return if key is not found.
	 *
	 * @return mixed The retrieved value after applying sanitization if needed.
	 */
	public static function post( $key, $default_name = null ) {
		$value = defender_get_data_from_request( $key, 'p' ) ?? $default_name;
		if ( is_array( $value ) ) {
			$value = defender_sanitize_data( $value );
		} elseif ( is_string( $value ) ) {
			$value = sanitize_textarea_field( $value );
		}

		return $value;
	}
}