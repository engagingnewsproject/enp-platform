<?php
/**
 * Handles retrieval of client IP addresses from HTTP headers.
 *
 * @package    WP_Defender\Component\Http\Remote_Address
 */

namespace WP_Defender\Component\Http\Remote_Address;

use WP_Defender\Traits\IP;

/**
 * Older way of getting the client/Remote IP address on HTTP request.
 */
class Classic_Remote_Address {

	use IP;

	/**
	 * List of HTTP headers that may contain the client IP address.
	 *
	 * @var array
	 */
	private $accepted_header = array(
		'HTTP_CLIENT_IP',
		'HTTP_X_REAL_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'HTTP_CF_CONNECTING_IP',
		'REMOTE_ADDR',
	);

	/**
	 * Returns client IP addresses.
	 *
	 * @return array IP addresses
	 */
	public function get_ip_address(): array {
		$ip_list = array();
		$server  = defender_get_data_from_request( null, 's' );
		foreach ( $this->accepted_header as $key ) {
			if ( array_key_exists( $key, $server ) && ! empty( $server[ $key ] ) ) {
				$ip_array = explode( ',', $server[ $key ] );
				foreach ( $ip_array as $ip ) {
					$ip = trim( $ip );
					if ( $this->check_validate_ip( $ip ) ) {
						$ip_list[] = $ip;
					}
				}
			}
		}

		return $ip_list;
	}

	/**
	 * Return all the headers found.
	 *
	 * @return array Header(s) key/name.
	 */
	public function get_ip_header(): array {
		$header_array = array();
		$server       = defender_get_data_from_request( null, 's' );
		foreach ( $this->accepted_header as $key ) {
			if ( array_key_exists( $key, $server ) && ! empty( $server[ $key ] ) ) {
				$ip_array = explode( ',', $server[ $key ] );
				foreach ( $ip_array as $ip ) {
					$ip = trim( $ip );
					if ( $this->check_validate_ip( $ip ) ) {
						$header_array[] = $key;
						break;
					}
				}
			}
		}

		return $header_array;
	}
}