<?php
/**
 * Handles the Firewall settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;
use WP_Defender\Traits\IP;

/**
 * Model for Firewall settings.
 */
class Firewall extends Setting {

	use IP;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_lockdown_settings';

	/**
	 * IP Detection Type.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip_detection_type = 'automatic';

	/**
	 * IP Blocklist Cleanup interval.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip_blocklist_cleanup_interval = 'never';

	/**
	 * Storage days.
	 *
	 * @var int
	 * @defender_property
	 */
	public $storage_days = 30;

	/**
	 * HTTP IP header.
	 *
	 * @var string
	 * @defender_property
	 */
	public $http_ip_header = 'REMOTE_ADDR';

	/**
	 * Trusted proxies IP.
	 *
	 * @var string
	 * @defender_property
	 */
	public $trusted_proxies_ip = '';

	/**
	 * Trusted proxy preset.
	 *
	 * @var string
	 * @defender_property
	 */
	public $trusted_proxy_preset = '';

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'storage_days'                  => esc_html__( 'Days to keep logs', 'wpdef' ),
			'ip_blocklist_cleanup_interval' => esc_html__( 'Clear Temporary IP Block List', 'wpdef' ),
			'http_ip_header'                => esc_html__( 'Detect IP Addresses', 'wpdef' ),
			'trusted_proxies_ip'            => esc_html__( 'Edit Trusted Proxies', 'wpdef' ),
		);
	}

	/**
	 * Get the trusted proxies as an array of IPs.
	 *
	 * @return array Array of IPs.
	 */
	public function get_trusted_proxies_ip(): array {
		$ip = $this->trusted_proxies_ip;

		$ip_array = array();

		if ( is_string( $ip ) ) {
			$ip_array = preg_split( '#\r\n|[\r\n]#', $ip );

			if ( is_array( $ip_array ) ) {
				$ip_array = array_filter( $ip_array );
				$ip_array = array_map( 'trim', $ip_array );
				$ip_array = array_map( 'strtolower', $ip_array );
			}
		}

		return (array) $ip_array;
	}

	/**
	 * Get the trusted proxy preset.
	 *
	 * @return string
	 */
	public function get_trusted_proxy_preset(): string {
		return $this->trusted_proxy_preset;
	}

	/**
	 * Validation method.
	 *
	 * @return void
	 */
	protected function after_validate(): void {
		$validation_object = $this->validate_trusted_proxies();

		if (
			isset( $validation_object['error'] )
			&& true === $validation_object['error']
			&& ! empty( $validation_object['message'] )
		) {
			$this->errors[] = $validation_object['message'];
		}

		if ( 'manual' === $this->ip_detection_type && '' === $this->http_ip_header ) {
			$this->errors[] = esc_html__( 'IP Detection option should not be empty.', 'wpdef' );
		}
	}

	/**
	 * Validation method for trusted proxies.
	 *
	 * @return array Return an array with mandatory boolean index error and optional index message which describes the
	 *     error.
	 */
	private function validate_trusted_proxies(): array {
		if (
			in_array(
				$this->http_ip_header,
				\WP_Defender\Component\Firewall::custom_http_headers(),
				true
			)
		) {
			$trusted_proxies_ip = $this->get_trusted_proxies_ip();

			if ( empty( $trusted_proxies_ip ) ) {
				// Nothing to check.
				return array( 'error' => false );
			}

			foreach ( $trusted_proxies_ip as $ip ) {
				if ( ! $this->validate_ip( $ip ) ) {
					return array(
						'error'   => true,
						'message' => sprintf(
						/* translators: %s: IP value. */
							esc_html__( '%s is not a valid IP address', 'wpdef' ),
							$ip
						),
					);
				}
			}
		}

		return array( 'error' => false );
	}
}