<?php
/**
 * Uninstall file.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Logger;
use Hummingbird\Core\Settings;

// If uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( class_exists( 'Hummingbird\\WP_Hummingbird' ) ) {
	return;
}

if ( ! class_exists( 'Hummingbird\\Core\\Settings' ) ) {
	/* @noinspection PhpIncludeInspection */
	include_once plugin_dir_path( __FILE__ ) . '/core/class-settings.php';
}
$settings = Settings::get_settings( 'settings' );

if ( $settings['remove_settings'] ) {
	$options = array(
		'wphb-caching-api-checked',
		'wphb-cloudflare-dash-notice',
		'wphb-free-install-date',
		'wphb-gzip-api-checked',
		'wphb-hide-tutorials',
		'wphb-minification-files-scanned',
		'wphb-minification-show-advanced_modal',
		'wphb-minification-show-config_modal',
		'wphb-minify-server-errors',
		'wphb-notice-cache-cleaned-show',
		'wphb-notice-free-deactivated-dismissed',
		'wphb-notice-free-deactivated-show',
		'wphb-notice-free-rated-show',
		'wphb-notice-http2-info-show',
		'wphb-notice-minification-optimized-show',
		'wphb-notice-uptime-info-show',
		'wphb-preset_configs',
		'wphb_process_queue',
		'wphb-quick-setup',
		'wphb_run_onboarding',
		'wphb_scripts_collection',
		'wphb_settings',
		'wphb-stop-report',
		'wphb_styles_collection',
		'wphb_version',
	);

	// Clear cron at first.
	wp_clear_scheduled_hook( 'wphb_performance_report' );
	wp_clear_scheduled_hook( 'wphb_uptime_report' );
	wp_clear_scheduled_hook( 'wphb_database_report' );
	if ( wp_next_scheduled( 'wphb_minify_clear_files' ) ) {
		wp_clear_scheduled_hook( 'wphb_minify_clear_files' );
	}

	// Subsite wp_option.
	if ( is_multisite() && ! wp_is_large_network() ) {
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			foreach ( $options as $option ) {
				delete_option( $option );
			}

			if ( $settings['remove_data'] ) {
				delete_option( 'wphb-last-report' );
			}

			restore_current_blog();
		}
	}

	// Network wp_option, wp_sitemeta.
	foreach ( $options as $option ) {
		delete_option( $option );
		delete_site_option( $option );
	}
}


if ( $settings['remove_data'] ) {
	// Reports & data.
	delete_site_option( 'wphb-caching-data' );
	delete_site_option( 'wphb-gzip-data' );

	delete_option( 'wphb-last-report' );
	delete_site_option( 'wphb-last-report' );

	if ( ! class_exists( 'Hummingbird\\Core\\Filesystem' ) ) {
		/* @noinspection PhpIncludeInspection */
		include_once plugin_dir_path( __FILE__ ) . '/core/class-filesystem.php';
	}

	$fs = Filesystem::instance();
	if ( ! is_wp_error( $fs->status ) ) {
		$fs->clean_up();
	}

	if ( ! class_exists( 'Hummingbird\\Core\\Logger' ) ) {
		/* @noinspection PhpIncludeInspection */
		include_once plugin_dir_path( __FILE__ ) . '/core/class-logger.php';
	}
	Logger::cleanup();
}