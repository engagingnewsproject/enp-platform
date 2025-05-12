<?php
/**
 * Responsible for gathering analytics data for the firewall feature.
 *
 * @package WP_Defender\Helper\Analytics
 */

namespace WP_Defender\Helper\Analytics;

use WP_Defender\Event;

/**
 * Gather analytics data required for firewall feature.
 */
class Firewall extends Event {

	public const EVENT_IP_DETECTION = 'def_ip_detection';
	public const PROP_IP_DETECTION  = 'Detection Method';

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array();
	}

	/**
	 * Converts the current state of the object to an array.
	 *
	 * @return array Returns an associative array of object properties.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param array $data Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Exports strings.
	 */
	public function export_strings() {
	}
	// End.

	/**
	 * Get a label of the detection method.
	 *
	 * @param string $ip_detection_type IP detection type.
	 * @param string $http_ip_header    HTTP IP header.
	 *
	 * @return string
	 */
	public static function get_detection_method_label( string $ip_detection_type, string $http_ip_header ): string {
		if ( 'automatic' === $ip_detection_type ) {
			$detection_method = 'Automatic';
		} else {
			// Manual options.
			$detection_method = 'Manual - ';
			switch ( $http_ip_header ) {
				case 'HTTP_X_FORWARDED_FOR':
					$detection_method .= 'X-Forward-For';
					break;
				case 'HTTP_X_REAL_IP':
					$detection_method .= 'X-Real-IP';
					break;
				case 'HTTP_CF_CONNECTING_IP':
					$detection_method .= 'CF-Connecting-IP';
					break;
				case 'REMOTE_ADDR':
				default:
					$detection_method .= 'Remote-Addr';
					break;
			}
		}

		return $detection_method;
	}
}