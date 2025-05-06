<?php
/**
 * The onboard model class.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

/**
 * Class Onboard
 *
 * Provides methods to check if the site is newly created.
 */
class Onboard {
	/**
	 * Checks if the site is newly created.
	 *
	 * @return bool Returns true if the site is newly created, false otherwise.
	 */
	public static function maybe_show_onboarding(): bool {
		// First we need to check if the site is newly create.
		global $wpdb;
		if ( ! is_multisite() ) {
			$res = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'wp_defender_shown_activator'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$res = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = 'wp_defender_shown_activator' AND site_id = %d",
					get_current_network_id()
				)
			);
		}
		// Get '1' for direct SQL request if Onboarding was already.
		if ( empty( $res ) ) {
			return true;
		}

		return false;
	}
}