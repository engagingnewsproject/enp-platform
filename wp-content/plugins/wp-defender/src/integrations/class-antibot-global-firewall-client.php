<?php
/**
 * Handles interactions with AntiBot Global Firewall API.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use WP_Error;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Traits\Defender_Dashboard_Client;

/**
 * AntiBot Global Firewall API client.
 *
 * @since 4.7.1
 */
class Antibot_Global_Firewall_Client {
	use Defender_Dashboard_Client;

	/**
	 * The base URL of the AntiBot Global Firewall API service.
	 *
	 * @var string
	 */
	private $base_url = 'https://api.blocklist-service.com';

	/**
	 * The WPMUDEV instance.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * Constructor for the Antibot_Global_Firewall_Client class.
	 *
	 * @param  WPMUDEV $wpmudev  The WPMUDEV object.
	 */
	public function __construct( WPMUDEV $wpmudev ) {
		$this->wpmudev = $wpmudev;
	}

	/**
	 * Get the base URL of the AntiBot Global Firewall API service.
	 *
	 * @return string
	 */
	private function get_base_url(): string {
		$base_url = defined( 'ANTIBOT_GLOBAL_FIREWALL_CUSTOM_API_SERVER' ) && ANTIBOT_GLOBAL_FIREWALL_CUSTOM_API_SERVER
			? ANTIBOT_GLOBAL_FIREWALL_CUSTOM_API_SERVER
			: $this->base_url;

		return $base_url . '/api';
	}

	/**
	 * Send firewall logs to AntiBot Global Firewall API.
	 *
	 * @param  array $data  The firewall logs.
	 *
	 * @return array|WP_Error
	 */
	public function send_reports( $data ) {
		return $this->make_request( 'POST', '/report', $data );
	}

	/**
	 * Get the blocklist download URL and hashes.
	 *
	 * @since 4.8.0
	 * @return array|WP_Error
	 */
	public function get_blocklist_download() {
		return $this->make_request( 'GET', '/download' );
	}

	/**
	 * Get Blocklist Statistics.
	 *
	 * @return int|\WP_Error
	 */
	public function get_blocklist_stats() {
		$response = $this->make_request( 'GET', '/stats' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) || ! isset( $response['data']['blocked_ips'] ) ) {
			return 0;
		}

		return (int) $response['data']['blocked_ips'];
	}

	/**
	 * Make a request to the AntiBot Global Firewall API service.
	 *
	 * @param  string $method  The HTTP method to use.
	 * @param  string $endpoint  The API endpoint to request.
	 * @param  array  $data  The data to send with the request or query variables.
	 *
	 * @return array|WP_Error
	 */
	private function make_request( $method, $endpoint, $data = array() ) {
		$apikey = $this->get_api_key();

		if ( ! $apikey ) {
			return new WP_Error( 'no_api_key', 'No API key provided' );
		}

		$base_url = $this->get_base_url();
		// Combine Url.
		$url  = $base_url . $endpoint;
		$args = array(
			'method'  => $method,
			'headers' => array(
				'x-blocklist-auth' => $apikey,
			),
		);

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $data );
		} elseif ( 'GET' === $method ) {
			$url = add_query_arg( $data, $url );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}