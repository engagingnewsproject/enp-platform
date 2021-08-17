<?php
/**
 * Hummingbird Advanced Tools module
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Load necessary modules for caching.
 */

if ( ! class_exists( 'Hummingbird\\Core\\Modules\\Page_Cache' ) ) {
	if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
		return;
	}

	if ( is_dir( WP_CONTENT_DIR . '/plugins/wp-hummingbird/' ) ) {
		$plugin_path = WP_CONTENT_DIR . '/plugins/wp-hummingbird/';
	} elseif ( is_dir( WP_CONTENT_DIR . '/plugins/hummingbird-performance/' ) ) {
		$plugin_path = WP_CONTENT_DIR . '/plugins/hummingbird-performance/';
	} else {
		return;
	}

	if ( ! file_exists( $plugin_path . 'core/cache.php' ) ) {
		return;
	}

	define( 'WPHB_ADVANCED_CACHE', true );
	include_once $plugin_path . 'core/cache.php';
}
