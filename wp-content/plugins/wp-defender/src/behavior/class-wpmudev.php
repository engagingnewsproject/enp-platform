<?php
/**
 * This file contains everything relate to WPMUDEV.
 *
 * @package WP_Defender\Behavior
 */

namespace WP_Defender\Behavior;

use WPMUDEV_Dashboard;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Formats;
use Calotes\Component\Behavior;
use WP_Defender\Traits\Defender_Hub_Client;
use WP_Defender\Traits\Defender_Dashboard_Client;
use WP_Defender\Component\Config\Config_Hub_Helper;

/**
 * This class contains everything relate to WPMUDEV.
 *
 * @since 2.2
 */
class WPMUDEV extends Behavior implements WPMUDEV_Const_Interface {

	use IO;
	use Formats;
	use Defender_Dashboard_Client;
	use Defender_Hub_Client;

	/**
	 * Get membership status.
	 *
	 * @return bool
	 */
	public function is_pro() {
		return $this->get_apikey() !== false;
	}

	/**
	 * Get WPMUDEV API KEY.
	 *
	 * @return bool|string
	 */
	public function get_apikey() {
		if ( ! class_exists( '\WPMUDEV_Dashboard' ) ) {
			return false;
		}

		WPMUDEV_Dashboard::instance();
		if (
			method_exists( WPMUDEV_Dashboard::$upgrader, 'user_can_install' )
			&& WPMUDEV_Dashboard::$upgrader->user_can_install(
				Config_Hub_Helper::WDP_ID,
				true
			)
		) {
			return WPMUDEV_Dashboard::$api->get_key();
		} else {
			return false;
		}
	}

	/**
	 * Check if whitelabel is enabled.
	 *
	 * @return bool Returns true if whitelabel is enabled, false otherwise.
	 * @since 2.5.5 Use Whitelabel filters instead of calling the whitelabel functions directly.
	 */
	public function is_whitelabel_enabled() {
		if ( $this->get_apikey() ) {
			// Use backward compatibility.
			if ( WPMUDEV_Dashboard::$version > '4.11.1' ) {
				$settings = apply_filters( 'wpmudev_branding', array() );

				return ! empty( $settings );
			} else {
				$site     = WPMUDEV_Dashboard::$site;
				$settings = $site->get_whitelabel_settings();

				return $settings['enabled'];
			}
		}

		return false;
	}

	/**
	 * Hide WPMU DEV urls for the current user if:
	 * 1) Whitelabel option is enabled,
	 * 2) the user is not listed in WPMU DEV > Settings > Permissions.
	 *
	 * @return bool
	 * @since 4.1.0
	 */
	public function hide_wpmu_dev_urls(): bool {
		return $this->is_whitelabel_enabled() && ! $this->is_wpmu_dev_admin();
	}

	/**
	 * Show support links if:
	 * plugin version isn't Free,
	 * Whitelabel is disabled.
	 *
	 * @return bool
	 * @since 2.5.5
	 */
	public function show_support_links() {
		if ( $this->get_apikey() ) {
			// Use backward compatibility.
			if ( WPMUDEV_Dashboard::$version > '4.11.1' ) {
				$settings = apply_filters( 'wpmudev_branding', array() );

				return empty( $settings );
			} else {
				$site     = WPMUDEV_Dashboard::$site;
				$settings = $site->get_whitelabel_settings();

				return ! $settings['enabled'];
			}
		}

		return false;
	}
}