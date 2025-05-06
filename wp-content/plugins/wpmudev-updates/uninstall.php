<?php
/**
 * Uninstall file.
 *
 * Cleanup plugin settings and transients if configured.
 *
 * @since   4.11.4
 * @package WPMUDEV
 */

// If uninstall not called from WordPress exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

// Get uninstall settings.
$keep_data     = get_site_option( 'wdp_un_uninstall_keep_data' );
$keep_settings = get_site_option( 'wdp_un_uninstall_preserve_settings' );

global $wpdb;

if ( ! $keep_data && ! $keep_settings ) {
	// Delete both settings and transients.
	if ( is_multisite() ) {
		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '%wdp_un_%'" ); // phpcs:ignore
	} else {
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wdp_un_%'" ); // phpcs:ignore
	}

	// Delete API key if everything needs to be cleaned.
	delete_site_option( 'wpmudev_apikey' );
} elseif ( ! $keep_data ) {
	// Delete transients.
	if ( is_multisite() ) {
		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '%_wdp_un_%'" ); // phpcs:ignore
	} else {
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_wdp_un_%'" ); // phpcs:ignore
	}

	// These are not settings, but data.
	delete_site_option( 'wdp_un_membership_data' );
	delete_site_option( 'wdp_un_remote_access' );
	delete_site_option( 'wdp_un_updates_data' );
	delete_site_option( 'wdp_un_translation_updates_available' );
	delete_site_option( 'wdp_un_profile_data' );
	delete_site_option( 'wdp_un_updates_available' );
	delete_site_option( 'wdp_un_notifications' );
} elseif ( ! $keep_settings ) {
	// Delete settings.
	if ( is_multisite() ) {
		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'wdp_un_%' AND meta_key NOT LIKE '%_wdp_un_%'" ); // phpcs:ignore
	} else {
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wdp_un_%' AND option_name NOT LIKE '%_wdp_un_%'" ); // phpcs:ignore
	}
}