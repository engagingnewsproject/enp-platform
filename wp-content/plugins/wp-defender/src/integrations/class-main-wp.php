<?php
/**
 * Handles interaction with MainWP
 *
 * @package WP_Defender\Integrations
 * @since 5.0.2
 */

namespace WP_Defender\Integrations;

use WP_Defender\Traits\IP;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * MainWP plugin integration module.
 *
 * @since 5.0.2
 */
class Main_Wp {
	use IP;

	/**
	 * The option name for the whitelist MainWP dashboard public IP.
	 */
	public const WHITELIST_DASHBOARD_PUBLIC_IP_OPTION = 'wpdef_firewall_whitelist_mainwp_dashboard_public_ip';

	/**
	 * Constructor for the class.
	 * Registers the hook.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'mainwp_child_site_stats', array( $this, 'set_whitelist_dashboard_public_ip' ) );
	}

	/**
	 * Is whitelisting the MainWP Dashboard's public IP enabled.
	 *
	 * @return bool
	 */
	public function is_whitelist_dashboard_public_ip_enabled(): bool {
		/**
		 * Filter to enable/disable whitelisting the MainWP Dashboard site's public IP.
		 *
		 * @param bool $enable True to enable whitelisting, false otherwise.
		 *
		 * @since 5.0.2
		 */
		return (bool) apply_filters( 'wpdef_firewall_whitelist_mainwp_dashboard_public_ip_enabled', true );
	}

	/**
	 * Whitelist MainWP dashboard site's public IP.
	 *
	 * @return bool
	 */
	public function set_whitelist_dashboard_public_ip(): bool {
		if ( ! $this->is_whitelist_dashboard_public_ip_enabled() ) {
			return false;
		}

		$ips = $this->get_user_ip();

		if ( empty( $ips ) ) {
			return false;
		}

		$stored_ips = $this->get_whitelist_dashboard_public_ip();
		$stored_ips = array_unique( $stored_ips );
		$ips        = array_unique( $ips );

		sort( $stored_ips );
		sort( $ips );

		if ( $stored_ips !== $ips ) {
			update_site_option( self::WHITELIST_DASHBOARD_PUBLIC_IP_OPTION, $ips );
		}

		return true;
	}

	/**
	 * Get the whitelisted MainWP dashboard site's public IP.
	 *
	 * @return array
	 */
	public function get_whitelist_dashboard_public_ip(): array {
		return $this->is_whitelist_dashboard_public_ip_enabled() ?
			get_site_option( self::WHITELIST_DASHBOARD_PUBLIC_IP_OPTION, array() ) :
			array();
	}
}