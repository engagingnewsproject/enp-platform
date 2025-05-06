<?php
/**
 * Handles the functionality for detecting the IP header to be used on the site.
 *
 * @package    WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Controller\Firewall;
use WP_Defender\Model\Setting\Firewall as Model_Firewall;
use WP_Error;

/**
 * Handles the functionality for detecting the IP header to be used on the site.
 *
 * @since 4.9.0
 */
class Smart_Ip_Detection extends Component {
	/**
	 * The version of the API.
	 *
	 * @var string
	 */
	public const API_VERSION = '1';

	/**
	 * The namespace of the API.
	 *
	 * @var string
	 */
	public const API_NAMESPACE = 'wpdef';

	/**
	 * The route of the API.
	 *
	 * @var string
	 */
	public const API_ROUTE = 'detect-ip-header';

	/**
	 * The IP header detected by Smart IP Detection.
	 *
	 * @var string
	 */
	public const SMART_IP_DETECTION_HEADER = 'wd_smart_ip_detection_header';

	/**
	 * The list of IP services.
	 *
	 * @var array
	 */
	public const IP_SERVICES = array(
		'https://api.ipify.org',
		'https://checkip.amazonaws.com',
		'https://ipinfo.io/ip',
	);

	/**
	 * AJAX action for the ping.
	 *
	 * @var string
	 */
	public const ACTION_PING = 'defender_smart_ip_detection_ping';

	/**
	 * Get namespace with version.
	 *
	 * @return string
	 */
	public static function get_namespace(): string {
		return self::API_NAMESPACE . '/v' . self::API_VERSION;
	}

	/**
	 * Get the route of the API.
	 *
	 * @return string
	 */
	public static function get_route(): string {
		return '/' . self::API_ROUTE;
	}

	/**
	 * Check if the IP detection is set to automatic.
	 *
	 * @return bool True if the IP detection is set to automatic, false otherwise.
	 */
	public function is_smart_ip_detection_enabled(): bool {
		$model = wd_di()->get( Model_Firewall::class );
		return 'automatic' === $model->ip_detection_type;
	}

	/**
	 * Detect the IP header based on the known IP(s).
	 *
	 * @return array|WP_Error The detected IP & header, or an error message if the header is not found.
	 */
	public function smart_ip_detect_header() {
		$headers_to_check = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
			'HTTP_X_FORWARDED_FOR',
		);

		$known_ips = array(
			$this->get_server_public_ip(),
			'127.0.0.1',
		);

		$server_addr = defender_get_data_from_request( 'SERVER_ADDR', 's' );
		if ( $this->validate_ip( $server_addr ) ) {
			$known_ips[] = $server_addr;
		}
		$known_ips = array_unique( $known_ips );

		foreach ( $headers_to_check as $header ) {
			$ips = defender_get_data_from_request( $header, 's' );
			if ( ! empty( $ips ) ) {
				$ips = array_map( 'trim', preg_split( '/[\s,]+/', $ips ) );
				foreach ( $ips as $ip ) {
					if ( in_array( $ip, $known_ips, true ) ) {
						update_site_option( self::SMART_IP_DETECTION_HEADER, $header );

						$msg = sprintf(
						/* translators: 1. IP header. 2. IP. */
							esc_html__( 'IP header detected: %1$s with IP: %2$s.', 'wpdef' ),
							$header,
							$ip
						);
						$this->log( 'Smart IP DETECTION: ' . $msg, Firewall::FIREWALL_LOG );
						return array(
							'header'  => $header,
							'ip'      => $ip,
							'message' => $msg,
						);
					}
				}
			}
		}

		$msg = sprintf(
			/* translators: 1. IP(s) */
			esc_html__( 'Not able to find any IP header via IP(s): %s.', 'wpdef' ),
			implode( ',', $known_ips )
		);
		$this->log( 'Smart IP DETECTION: ' . $msg, Firewall::FIREWALL_LOG );
		return new WP_Error(
			'defender_smart_ip_detection_no_header',
			$msg
		);
	}

	/**
	 * Send request to the API endpoint for IP detection.
	 *
	 * @param bool $is_skipped Whether to skip the check if Smart IP Detection is enabled.
	 *
	 * @return bool True if the request is sent successfully, false otherwise.
	 */
	public function smart_ip_detection_ping( bool $is_skipped = false ): bool {
		if ( ! $is_skipped && ! $this->is_smart_ip_detection_enabled() ) {
			$this->log( 'Smart IP DETECTION: IP Detection is not set to \'automatic\'', Firewall::FIREWALL_LOG );
			return false;
		}

		$nonce = bin2hex( Crypt::random_bytes( 32 ) );
		set_transient( self::get_nonce_context(), $nonce, MINUTE_IN_SECONDS );

		$url      = add_query_arg(
			array(
				'action' => self::ACTION_PING,
				'nonce'  => $nonce,
			),
			admin_url( 'admin-ajax.php' )
		);
		$args     = array(
			'sslverify' => false,  // Many hosts have no updated CA bundle.
		);
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log( 'Smart IP DETECTION: ' . $response->get_error_message(), Firewall::FIREWALL_LOG );
			return false;
		} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$this->log( 'Smart IP DETECTION: ' . wp_remote_retrieve_response_message( $response ), Firewall::FIREWALL_LOG );
			return false;
		} else {
			$this->log( 'Smart IP DETECTION: Pinged successfully.', Firewall::FIREWALL_LOG );
			return true;
		}
	}

	/**
	 * Get the details of the Smart IP Detection.
	 *
	 * @return array The details of the IP and header.
	 */
	public function get_smart_ip_detection_details(): array {
		$ip_details         = array();
		$recommended_header = get_site_option( self::SMART_IP_DETECTION_HEADER );

		$server = defender_get_data_from_request( null, 's' );
		if ( isset( $server[ $recommended_header ] ) ) {
			$ip_details[] = array( $server[ $recommended_header ], $recommended_header );
		}

		$ip_details[] = array_key_exists( 'REMOTE_ADDR', $server )
			? array( $server['REMOTE_ADDR'], 'REMOTE_ADDR' )
			: array( '127.0.0.1', 'REMOTE_ADDR' );

		if ( isset( $server['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_details[] = array( $server['HTTP_X_FORWARDED_FOR'], 'HTTP_X_FORWARDED_FOR' );
		}

		if ( isset( $server['HTTP_X_REAL_IP'] ) ) {
			$ip_details[] = array( $server['HTTP_X_REAL_IP'], 'HTTP_X_REAL_IP' );
		}

		$private_ip = array();
		foreach ( $ip_details as $ip_detail ) {
			$ip_array = array_reverse( array_map( 'trim', preg_split( '/[\s,]+/', $ip_detail[0] ) ) );

			foreach ( $ip_array as $ip ) {
				if ( ! $this->is_private_ip( $ip ) ) {
					// Immediately return the first public IP found.
					return array( $ip, $ip_detail[1] );
				} else {
					$private_ip[] = array( $ip, $ip_detail[1] );
				}
			}
		}

		// If public IP was not found then return the first private IP.
		return isset( $private_ip[0] ) ? $private_ip[0] : array();
	}

	/**
	 * Remove the IP header detected by Smart IP Detection.
	 */
	public static function remove_header(): void {
		delete_site_option( self::SMART_IP_DETECTION_HEADER );
	}

	/**
	 * Get server's public IP from various sources.
	 *
	 * @return string
	 */
	public function get_server_public_ip(): string {
		$ip = '';

		foreach ( self::IP_SERVICES as $service ) {
			$response = wp_remote_get( $service );
			if ( ! is_wp_error( $response ) ) {
				$ip = trim( wp_remote_retrieve_body( $response ) );
				if ( $this->validate_ip( $ip ) ) {
					return $ip;
				}
			}
		}

		return $ip;
	}

	/**
	 * Get the nonce context for the AJAX action.
	 */
	public static function get_nonce_context(): string {
		return self::ACTION_PING . '_ajax_nonce';
	}
}