<?php
/**
 * Represents a silent skin for WordPress automatic upgrade.
 *
 * @package WP_Defender\Behavior\Scan_Item
 */

namespace WP_Defender\Behavior\Scan_Item;

use Automatic_Upgrader_Skin;

/**
 * Include the WP_Upgrader class if it's not already loaded.
 */
if ( ! class_exists( \WP_Upgrader::class ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}
/**
 * Include the Theme_Upgrader class if it's not already loaded.
 */
if ( ! class_exists( \Theme_Upgrader::class ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';
}

/**
 * Represents a silent skin for WordPress automatic upgrade.
 */
class Silent_Skin extends Automatic_Upgrader_Skin {
	/**
	 * Outputs the footer HTML for the upgrade.
	 *
	 * @return void
	 */
	public function footer() {
	}

	/**
	 * Outputs the header HTML for the upgrade.
	 *
	 * @return void
	 */
	public function header() {
	}

	/**
	 * Handles feedback for the upgrade process.
	 *
	 * @param  mixed $data  The data received from the upgrade process.
	 * @param  mixed ...$args  Additional arguments passed to the method.
	 *
	 * @return string Empty string always returned.
	 */
	public function feedback( $data, ...$args ) {
		return '';
	}
}