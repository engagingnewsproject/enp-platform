<?php

namespace Calotes\Helper;

class HTTP {

	/**
	 * @param $url
	 *
	 * @return string
	 */
	public static function strips_protocol( $url ) {
		$parts = parse_url( $url );

		$host = $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : null );
		$host = rtrim( $host, '/' );

		return $host;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @param bool   $strict
	 *
	 * @return string
	 */
	public static function get( $key, $default = null, $strict = false ) {
		$value = $_GET[ $key ] ?? $default;
		if ( true === $strict && empty( $value ) ) {
			$value = $default;
		}
		if ( ! is_array( $value ) ) {
			$value = sanitize_textarea_field( $value );
		} else {
			$value = defender_sanitize_data( $value );
		}

		return $value;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return string
	 */
	public static function post( $key, $default = null ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : $default;
		if ( ! is_array( $value ) ) {
			$value = sanitize_textarea_field( $value );
		} else {
			$value = defender_sanitize_data( $value );
		}

		return $value;
	}

	/**
	 * @return array
	 */
	public static function gets() {
		return defender_sanitize_data( $_GET );
	}

	/**
	 * @return array
	 */
	public static function posts() {
		return defender_sanitize_data( $_POST );
	}
}
