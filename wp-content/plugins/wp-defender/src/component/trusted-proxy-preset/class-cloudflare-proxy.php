<?php
/**
 * Handles Cloudflare CIDR IP ranges.
 *
 * @package WP_Defender\Component\Trusted_Proxy_Preset
 */

namespace WP_Defender\Component\Trusted_Proxy_Preset;

use Calotes\Base\Component;
use WP_Defender\Controller\Firewall;

/**
 * Provides methods for managing Cloudflare CIDR IP ranges.
 */
class Cloudflare_Proxy extends Component implements Trusted_Proxy_Preset_Strategy_Interface {

	public const PROXY_SLUG  = 'cloudflare';
	public const OPTION_NAME = 'wpdef_cloudflare_ips';
	public const PROXY_API   = 'https://api.cloudflare.com/client/v4/ips';

	/**
	 * Get Cloudflare CIDR IP ranges.
	 *
	 * @return array
	 */
	public function get_ips(): array {
		$ip_ranges = get_site_option( self::OPTION_NAME, false );

		if ( false === $ip_ranges ) {
			$ip_ranges = $this->update_ips();
		}

		return is_array( $ip_ranges ) ? $ip_ranges : array();
	}

	/**
	 * Update Cloudflare CIDR IP ranges from API.
	 *
	 * @return bool|array
	 */
	public function update_ips() {
		$response = wp_remote_get( self::PROXY_API );

		if ( is_wp_error( $response ) ) {
			$this->log( 'Error fetching Cloudflare IPs: ' . $response->get_error_message(), Firewall::FIREWALL_LOG );

			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! ( isset( $data['result']['ipv4_cidrs'] ) || isset( $data['result']['ipv6_cidrs'] ) ) ) {
			$this->log( 'Invalid response from Cloudflare API', Firewall::FIREWALL_LOG );

			return false;
		}

		$ip_ranges = array_merge(
			$data['result']['ipv4_cidrs'] ?? array(),
			$data['result']['ipv6_cidrs'] ?? array()
		);

		// Store IP ranges in the database.
		update_site_option( self::OPTION_NAME, $ip_ranges );

		return $ip_ranges;
	}

	/**
	 * Delete Cloudflare CIDR IP ranges.
	 *
	 * @return bool
	 */
	public function delete_ips(): bool {
		return delete_site_option( self::OPTION_NAME );
	}
}