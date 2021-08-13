<?php
/**
 * Compatibility with WP Hide & Security Enhancer.
 *
 * @since 1.9.4
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPH
 */
class WPH {

	/**
	 * WP_Hummingbird_WPH_Integration constructor.
	 *
	 * @since 1.9.4
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'enable_integration' ) );
	}

	/**
	 * Enable integration.
	 *
	 * @since 1.9.4
	 */
	public function enable_integration() {
		// If WP Hide & Security Enhancer is not enabled - return.
		if ( ! defined( 'WPH_PATH' ) || ! defined( 'WPH_CORE_VERSION' ) ) {
			return;
		}

		// Page caching is not enabled.
		if ( ! apply_filters( 'wp_hummingbird_is_active_module_page_cache', false ) ) {
			return;
		}

		add_filter( 'wphb_cache_content', array( $this, 'replace_links' ) );
	}

	/**
	 * Replace links when URLs are replaced in WP Hide & Security Enhancer.
	 *
	 * @since 1.9.4
	 *
	 * @param string $content  Page buffer.
	 *
	 * @return string
	 */
	public function replace_links( $content ) {
		global $wph;

		$content = $wph->ob_start_callback( $content );

		return $content;
	}

}
