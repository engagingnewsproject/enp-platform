<?php
/**
 * Class CTF_GDPR_Integrations
 *
 * Adds GDPR related workarounds for third-party plugins:
 * https://wordpress.org/plugins/cookie-law-info/
 *
 * @since 1.7/1.12
 */
namespace TwitterFeed;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class CTF_GDPR_Integrations {

	/**
	 * Undoing of Cookie Notice's Twitter Feed related code
	 * needs to be done late.
	 *
	 * @since 1.7/1.12
	 */
	public static function init() {
		add_filter( 'wt_cli_third_party_scripts', array( 'TwitterFeed\CTF_GDPR_Integrations', 'undo_script_blocking' ), 11 );
		add_filter( 'cmplz_known_script_tags', array( 'TwitterFeed\CTF_GDPR_Integrations', 'undo_script_blocking' ), 11 );
	}

	/**
	 * Prevents changes made to how JavaScript file is added to
	 * pages.
	 *
	 * @param array $return
	 *
	 * @return array
	 *
	 * @since 1.7/1.12
	 */
	public static function undo_script_blocking( $return ) {
		$settings = ctf_get_database_settings();
		if ( ! self::doing_gdpr( $settings ) ) {
			return $return;
		} unset( $return['twitter-feed'] );

		remove_filter( 'wt_cli_third_party_scripts', 'wt_cli_twitter_feed_script' );
		remove_filter( 'cmplz_known_script_tags', 'cmplz_twitter_feed_script' );

		return $return;
	}

	/**
	 * Whether or not consent plugins that Twitter Feed
	 * is compatible with are active.
	 *
	 * @return bool|string
	 *
	 * @since 1.7/1.12
	 */
	public static function gdpr_plugins_active() {
		if ( class_exists( 'Cookie_Notice' ) ) {
			return 'Cookie Notice by dFactory';
		}
		if ( function_exists( 'run_cookie_law_info' ) ) {
			return 'GDPR Cookie Consent by WebToffee';
		}
		if ( class_exists( 'Cookiebot_WP' ) ) {
			return 'Cookiebot by Cybot A/S';
		}
		if ( class_exists( 'COMPLIANZ' ) ) {
			return 'Complianz by Really Simple Plugins';
		}
		if ( function_exists( 'BorlabsCookieHelper' ) ) {
			return 'Borlabs Cookie by Borlabs';
		}

		return false;
	}

	/**
	 * GDPR features can be added automatically, forced enabled,
	 * or forced disabled.
	 *
	 * @param $settings
	 *
	 * @return bool
	 *
	 * @since 1.7/1.12
	 */
	public static function doing_gdpr( $settings ) {
		$gdpr = isset( $settings['gdpr'] ) ? $settings['gdpr'] : 'auto';
		if ( $gdpr === 'no' ) {
			return false;
		}
		if ( $gdpr === 'yes' ) {
			return true;
		}
		return ( self::gdpr_plugins_active() !== false );
	}

	/**
	 * No tests needed in free version
	 *
	 * @param bool $retest
	 *
	 * @return bool
	 *
	 * @since 1.7/1.12
	 */
	public static function gdpr_tests_successful( $retest = false ) {
		return true;
	}

	/**
	 * No tests needed in free version
	 *
	 * @return array
	 *
	 * @since 1.7/1.12
	 */
	public static function gdpr_tests_error_message() {
		return array();
	}

	public static function statuses() {
		$statuses_option = get_option( 'ctf_statuses', array() );

		$return = isset( $statuses_option['gdpr'] ) ? $statuses_option['gdpr'] : array();
		return $return;
	}

}
