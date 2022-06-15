<?php
/**
 * Class that handles compatibility functionality.
 *
 * @link    https://wpmudev.com
 * @since   4.11.9
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Compatibility
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Compatibility
 */
class WPMUDEV_Dashboard_Compatibility {

	/**
	 * WPMUDEV_Dashboard_Compatibility constructor.
	 *
	 * @since 4.11.9
	 */
	public function __construct() {
		// Plugin basename.
		$basename = WPMUDEV_Dashboard::$basename;
		// Register with WP Consent API - https://github.com/rlankhorst/wp-consent-level-api.
		add_filter( "wp_consent_api_registered_$basename", '__return_true' );
		add_action( 'plugins_loaded', array( $this, 'register_cookies' ) );
		add_filter( 'wpmudev_dashboard_analytics_tracking', array( $this, 'check_analytics_cookie_consent' ) );
	}

	/**
	 * Disable analytics tracking if cookie consent is not given.
	 *
	 * @param bool $enable Is enabled.
	 *
	 * @since 4.11.9
	 *
	 * @return bool
	 */
	public function check_analytics_cookie_consent( $enable ) {
		// Check for statistics cookie consent.
		if ( function_exists( 'wp_has_consent' ) && ! wp_has_consent( 'statistics' ) ) {
			$enable = false;
		}

		return $enable;
	}

	/**
	 * Register our details with consent API.
	 *
	 * @since 4.11.9
	 *
	 * @return void
	 */
	public function register_cookies() {
		// Only if required function exists.
		if ( function_exists( 'wp_add_cookie_info' ) ) {
			wp_add_cookie_info(
				'WPMUDEV Analytics',
				'WPMUDEV Dashboard',
				'statistics',
				'',
				__( 'Tracking visitors details.', 'wpmudev' ),
				false,
				false,
				false
			);
		}
	}
}