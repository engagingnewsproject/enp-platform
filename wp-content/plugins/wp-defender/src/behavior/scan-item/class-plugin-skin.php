<?php
/**
 * Represents a skin for WordPress plugin upgrade.
 *
 * @package WP_Defender\Behavior\Scan_Item
 */

namespace WP_Defender\Behavior\Scan_Item;

use WP_Ajax_Upgrader_Skin;

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
 * Represents a skin for WordPress plugin upgrade.
 */
class Plugin_Skin extends WP_Ajax_Upgrader_Skin {
	/**
	 * Handles feedback for the plugin upgrade process.
	 *
	 * @param  mixed $data  The data received from the plugin upgrade process.
	 * @param  mixed ...$args  Additional arguments passed to the method.
	 *
	 * @return string Empty string always returned.
	 */
	public function feedback( $data, ...$args ) {
		return '';
	}
}