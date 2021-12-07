<?php
/**
 * Integration class for SiteGround hosting.
 *
 * @since 2.1.0
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

use Hummingbird\Admin\Notices;
use Hummingbird\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SiteGround
 */
class SiteGround {

	/**
	 * WP_Hummingbird_SiteGround_Integration constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! $this->sg_optimizer_active() ) {
			return;
		}

		// Page caching is enabled, we can integrate.
		if ( apply_filters( 'wp_hummingbird_is_active_module_page_cache', false ) ) {
			add_action( 'wphb_clear_cache_url', array( $this, 'purge_cache' ) );
		}

		add_filter( 'wphb_minification_disable_switchers', array( $this, 'filter_ao_switchers' ), 10, 3 );

		$this->advanced_tools_integration();
	}

	/**
	 * Checks if the SG optimization plugin is active on the site.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	private function sg_optimizer_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// We only continue if the SiteGround optimization plugin is active.
		return is_plugin_active( 'sg-cachepress/sg-cachepress.php' );
	}

	/**
	 * Clear cache for selected path.
	 *
	 * @since 2.1.0
	 *
	 * @param string $path  Directory path. Optional. If none specified, will clear all cache.
	 */
	public function purge_cache( $path = '' ) {
		$sg_caching = Settings::get( 'siteground_optimizer_enable_cache' );

		// Dynamic caching is disabled.
		if ( ! $sg_caching ) {
			return;
		}

		$url = isset( $path ) ? get_option( 'home' ) . $path : '';

		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache( $url );
		}

		if ( class_exists( '\\SiteGround_Optimizer\\Supercacher\\Supercacher' ) ) {
			\SiteGround_Optimizer\Supercacher\Supercacher::purge_cache_request( $url );
		}
	}

	/**
	 * Allows to enable/disable switchers in minification page.
	 *
	 * @since 2.1.0
	 *
	 * @param array  $disable_switchers  List of switchers disabled for an item (include, minify, combine).
	 * @param array  $item               Info about the current item.
	 * @param string $type               Type of the current item (scripts|styles).
	 *
	 * @return array
	 */
	public function filter_ao_switchers( $disable_switchers, $item, $type ) {
		$sg_optimize_css = Settings::get( 'siteground_optimizer_optimize_css' );
		$sg_optimize_js  = Settings::get( 'siteground_optimizer_optimize_javascript' );
		$sg_combine_css  = Settings::get( 'siteground_optimizer_combine_css' );
		$sg_async_js     = Settings::get( 'siteground_optimizer_optimize_javascript_async' );

		$notice = $sg_optimize_css || $sg_optimize_js || $sg_combine_css || $sg_async_js;

		if ( ( 'styles' === $type && $sg_optimize_css ) || ( 'scripts' === $type && $sg_optimize_js ) ) {
			$disable_switchers[] = 'minify';
		}

		if ( 'styles' === $type && $sg_combine_css ) {
			$disable_switchers[] = 'combine';
		}

		if ( 'scripts' === $type && $sg_async_js ) {
			$disable_switchers[] = 'position';
			$disable_switchers[] = 'defer';
		}

		add_action(
			'wphb_asset_optimization_notice',
			function() use ( $notice ) {
				if ( ! $notice ) {
					return;
				}

				$notice = __( 'Hummingbird detect that the SG Optimizer plugin with frontend optimization features is enabled. Some asset optimization features have been disabled for compatibility.', 'wphb' );
				Notices::get_instance()->show_inline( $notice, 'info' );
			}
		);

		return $disable_switchers;
	}

	/**
	 * Advanced tools integration.
	 *
	 * Disables URL Query Strings and Emojis options in Hummingbird if similar options are enabled in SG Optimizer.
	 *
	 * @since 2.1.0
	 */
	private function advanced_tools_integration() {
		$sg_query_strings  = Settings::get( 'siteground_optimizer_remove_query_strings' );
		$sg_disable_emojis = Settings::get( 'siteground_optimizer_disable_emojis' );

		if ( $sg_query_strings ) {
			add_filter( 'wphb_query_strings_disabled', '__return_true' );
		}

		if ( $sg_disable_emojis ) {
			add_filter( 'wphb_emojis_disabled', '__return_true' );
		}

		if ( $sg_query_strings || $sg_disable_emojis ) {
			add_action(
				'wphb_advanced_tools_notice',
				function() {
					$notice = __( 'Hummingbird detect that the SG Optimizer plugin with frontend optimization features is enabled. Some asset settings have been disabled for compatibility.', 'wphb' );
					Notices::get_instance()->show_inline( $notice, 'info' );
				}
			);
		}
	}

}
