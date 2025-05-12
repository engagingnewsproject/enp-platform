<?php
/**
 * Handles the determination of the remote IP address based on server settings and configurations.
 *
 * @package    WP_Defender\Component\Http
 */

namespace WP_Defender\Component\Http;

use WP_Defender\Model\Setting\Firewall;
use WP_Defender\Component\Http\Remote_Address\Classic_Remote_Address;
use WP_Defender\Component\Http\Remote_Address\Remote_Address as Modern_Remote_Address;

/**
 * Service layer decides/strategist which instance of remote address class to use.
 */
class Remote_Address {

	/**
	 * Firewall settings instance.
	 *
	 * @var Firewall
	 */
	private $firewall;

	/**
	 * HTTP IP header name.
	 *
	 * @var string
	 */
	private $http_ip_header = '';

	/**
	 *  Initializes the Remote_Address service with firewall settings.
	 */
	public function __construct() {
		$this->firewall       = wd_di()->get( Firewall::class );
		$this->http_ip_header = esc_html( $this->firewall->http_ip_header );
	}

	/**
	 * Sets the HTTP IP header key.
	 *
	 * @param  string $http_header  HTTP header key/name.
	 */
	public function set_http_ip_header( $http_header ): void {
		$this->http_ip_header = $http_header;
	}

	/**
	 * Determines the appropriate Remote_Address instance based on the configured HTTP IP header.
	 *
	 * @return Remote_Address The appropriate Remote_Address instance.
	 */
	private function instance() {
		/**
		 * Filter the HTTP IP header.
		 *
		 * @param  string  $http_ip_header  HTTP header for identifying client's IP.
		 *
		 * @since 4.5.1
		 */
		$http_ip_header = (string) apply_filters( 'wpdef_firewall_ip_detection', $this->http_ip_header );
		switch ( $http_ip_header ) {
			case 'HTTP_X_FORWARDED_FOR':
			case 'HTTP_X_REAL_IP':
			case 'HTTP_CF_CONNECTING_IP':
				$remote_address = wd_di()->get( Modern_Remote_Address::class );
				$remote_address
					->set_use_proxy()
					->set_proxy_header( $http_ip_header )
					->set_trusted_proxies( $this->firewall->get_trusted_proxies_ip() )
					->set_trusted_proxy_preset( $this->firewall->get_trusted_proxy_preset() );

				return $remote_address;

			case 'REMOTE_ADDR':
				$remote_address = wd_di()->get( Modern_Remote_Address::class );
				$remote_address->set_use_proxy( false );

				return $remote_address;

			default:
				return wd_di()->get( Classic_Remote_Address::class );
		}
	}

	/**
	 * Retrieves the client IP address using the appropriate Remote_Address instance.
	 *
	 * @return string The client IP address.
	 */
	public function get_ip_address() {
		return $this->instance()->get_ip_address();
	}

	/**
	 * Return HTTP IP header(s) value if it presents else failed message.
	 *
	 * @param  string $http_ip_header_key  HTTP header key/name.
	 *
	 * @return string Header(s) value or failure message.
	 */
	public function get_http_ip_header_value( string $http_ip_header_key ): string {
		$ip_array = array();
		$server   = defender_get_data_from_request( null, 's' );
		if ( empty( $http_ip_header_key ) ) {
			$ip_array = wd_di()->get( Classic_Remote_Address::class )->get_ip_header();
		} elseif ( isset( $server[ $http_ip_header_key ] ) ) {
			$ip_array[] = $server[ $http_ip_header_key ];
		}

		return ! empty( $ip_array ) ?
			implode( ', ', $ip_array ) :
			sprintf( /* translators: %s - HTTP IP header */
				esc_html__( '%s header missing in $_SERVER global variable.', 'wpdef' ),
				$http_ip_header_key
			);
	}
}