<?php
/**
 * Setup wizard AJAX actions.
 *
 * @since 3.3.1
 * @package Hummingbird\Admin\Ajax
 */

namespace Hummingbird\Admin\Ajax;

use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use WPMUDEV_Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Setup.
 */
class Setup {

	/**
	 * Setup constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wphb_react_check_requirements', array( $this, 'check_requirements' ) );
		add_action( 'wp_ajax_wphb_react_remove_advanced_cache', array( $this, 'remove_advanced_cache' ) );
		add_action( 'wp_ajax_wphb_react_disable_fast_cgi', array( $this, 'disable_fast_cgi' ) );
		add_action( 'wp_ajax_wphb_react_cancel_wizard', array( $this, 'cancel' ) );
		add_action( 'wp_ajax_wphb_react_complete_wizard', array( $this, 'complete' ) );
		add_action( 'wp_ajax_wphb_react_settings', array( $this, 'update_settings' ) );
	}

	/**
	 * Get site ID from Dashboard plugin.
	 *
	 * @since 3.3.1
	 *
	 * @return false|int
	 */
	private function get_site_id() {
		// Only check on WPMU DEV hosting.
		if ( ! isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
			return false;
		}

		if ( ! class_exists( 'WPMUDEV_Dashboard' ) ) {
			return false;
		}

		if ( ! method_exists( 'WPMUDEV_Dashboard_Api', 'get_site_id' ) ) {
			return false;
		}

		return WPMUDEV_Dashboard::$api->get_site_id();
	}

	/**
	 * Check setup requirements.
	 *
	 * @since 3.3.1
	 */
	public function check_requirements() {
		check_ajax_referer( 'wphb-fetch' );

		$status = array(
			'advCacheFile' => false,
			'fastCGI'      => false,
		);

		// Check for advanced-cache.php conflicts.
		if ( file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
			$advanced_cache         = file_get_contents( WP_CONTENT_DIR . '/advanced-cache.php' );
			$status['advCacheFile'] = false === strpos( $advanced_cache, 'WPHB_ADVANCED_CACHE' );
		}

		// Check FastCGI cache.
		$site_id = $this->get_site_id();
		if ( $site_id ) {
			$hosting = Utils::get_api()->hosting->get_info( $site_id );
			if ( is_object( $hosting ) && property_exists( $hosting, 'static_cache' ) ) {
				$status['fastCGI'] = $hosting->static_cache->is_active;
			}
		}

		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * Remove the advanced-cache.php file.
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	public function remove_advanced_cache() {
		check_ajax_referer( 'wphb-fetch' );

		$adv_cache_file = dirname( get_theme_root() ) . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}

		$this->check_requirements();
	}

	/**
	 * Disable FastCGI cache on WPMU DEV hosting.
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	public function disable_fast_cgi() {
		check_ajax_referer( 'wphb-fetch' );

		$site_id = $this->get_site_id();
		if ( $site_id ) {
			Utils::get_api()->hosting->disable_fast_cgi( $site_id );
		}

		$this->check_requirements();
	}

	/**
	 * Cancel wizard.
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	public function cancel() {
		check_ajax_referer( 'wphb-fetch' );
		update_option( 'wphb_run_onboarding', null );
		wp_send_json_success();
	}

	/**
	 * Complete wizard.
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	public function complete() {
		check_ajax_referer( 'wphb-fetch' );
		update_option( 'wphb_run_onboarding', null );
		wp_send_json_success();
	}

	/**
	 * Update settings.
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	public function update_settings() {
		check_ajax_referer( 'wphb-fetch' );

		$settings = filter_input( INPUT_POST, 'data', FILTER_UNSAFE_RAW );
		$settings = json_decode( html_entity_decode( $settings ), true );

		// Tracking (make sure it's always updated).
		if ( is_admin() || ( is_multisite() && is_network_admin() ) ) {
			$tracking = isset( $settings['tracking'] ) && $settings['tracking'];
			Settings::update_setting( 'tracking', $tracking, 'settings' );
		}

		if ( 'ao' === $settings['module'] ) {
			if ( Utils::is_ajax_network_admin() ) {
				// On network admin we have a different set of options.
				$value = isset( $settings['enable'] ) && $settings['enable'];
				Utils::get_module( 'minify' )->toggle_service( $value, true );
			}

			if ( isset( $settings['enable'] ) && $settings['enable'] ) {
				$options = Settings::get_settings( 'minify' );

				$options['type']    = isset( $settings['aoSpeedy'] ) && $settings['aoSpeedy'] ? 'speedy' : 'basic';
				$options['use_cdn'] = isset( $settings['aoCdn'] ) && $settings['aoCdn'];

				Settings::update_settings( $options, 'minify' );
			} elseif ( ! Utils::is_ajax_network_admin() ) {
				Utils::get_module( 'minify' )->disable();
			}
		} elseif ( 'uptime' === $settings['module'] && Utils::get_module( 'uptime' )->has_access() ) {
			if ( isset( $settings['enable'] ) && $settings['enable'] ) {
				Utils::get_module( 'uptime' )->enable();
			} else {
				Utils::get_module( 'uptime' )->disable();
			}
		} elseif ( 'caching' === $settings['module'] ) {
			if ( Utils::is_ajax_network_admin() ) {
				define( 'WPHB_IS_NETWORK_ADMIN', true );
			}

			if ( isset( $settings['enable'] ) && $settings['enable'] ) {
				Utils::get_module( 'page_cache' )->enable();

				$caching_setting = Utils::get_module( 'page_cache' )->get_settings();

				$caching_setting['settings']['cache_headers'] = (int) ( isset( $settings['cacheHeader'] ) && $settings['cacheHeader'] );
				$caching_setting['settings']['mobile']        = (int) ( isset( $settings['cacheOnMobile'] ) && $settings['cacheOnMobile'] );
				$caching_setting['settings']['comment_clear'] = (int) ( isset( $settings['clearOnComment'] ) && $settings['clearOnComment'] );

				Utils::get_module( 'page_cache' )->save_settings( $caching_setting );

				$control = isset( $settings['clearCacheButton'] ) && $settings['clearCacheButton'];
				Settings::update_setting( 'control', $control, 'settings' );
			} else {
				Utils::get_module( 'page_cache' )->disable();
			}
		} else {
			$options = Settings::get_settings( 'advanced' );

			// Advanced tools options.
			$options['query_string']   = isset( $settings['queryStrings'] ) && $settings['queryStrings'];
			$options['cart_fragments'] = isset( $settings['cartFragments'] ) && $settings['cartFragments'];
			$options['emoji']          = isset( $settings['removeEmoji'] ) && $settings['removeEmoji'];

			Settings::update_settings( $options, 'advanced' );
		}

		wp_send_json_success();
	}

}