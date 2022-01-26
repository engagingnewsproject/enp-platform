<?php

namespace WP_Defender\Behavior;

use Calotes\Component\Behavior;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\Defender_Dashboard_Client;
use WP_Defender\Traits\Defender_Hub_Client;
use WP_Defender\Behavior\WPMUDEV_Const_Interface;

/**
 * This class contains everything relate to WPMUDEV.
 * Class WPMUDEV
 * @package WP_Defender\Behavior
 * @since 2.2
 */
class WPMUDEV extends Behavior implements WPMUDEV_Const_Interface {
	use IO, Formats, Defender_Dashboard_Client, Defender_Hub_Client;

	/**
	 * Get membership status.
	 *
	 * @return bool
	 */
	public function is_pro() {
		return false;
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

		\WPMUDEV_Dashboard::instance();

		$membership_status = \WPMUDEV_Dashboard::$api->get_membership_data();

		$key = \WPMUDEV_Dashboard::$api->get_key();

		if (
			! empty( $membership_status['hub_site_id'] )
			&& ! empty( $key )
		) {
			return $key;
		}

		return false;
	}

	/**
	 * Get the current membership status using Dash plugin.
	 *
	 * @return string
	 */
	public function membership_status() {
		return 'free';
	}

	/**
	 * @since 2.5.5 Use Whitelabel filters instead of calling the whitelabel functions directly.
	 * @return bool
	 */
	public function is_whitelabel_enabled() {
		return false;
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
		return false;
	}
}
