<?php
/**
 * Integration module for various page builders.
 *
 * @since 2.4.0
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

use Hummingbird\WP_Hummingbird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Builders
 */
class Builders {

	/**
	 * Builders constructor.
	 */
	public function __construct() {
		// Some page builders may fetch content with REST API requests.
		add_action( 'parse_request', array( $this, 'maybe_deactivate_minify_on_rest' ) );

		// Clear cache after layout save.
		add_action( 'fl_builder_after_save_layout', array( $this, 'beaver_builder_clear_cache' ) );

		/**
		 * Cornerstone actions that require to deactivate the minify module.
		 * Taken from Cornerstone_Integration_Caching -> __construct().
		 */
		add_action( 'cornerstone_load_builder', array( $this, 'deactivate_minify_module' ) );
		add_action( 'cornerstone_before_boot_app', array( $this, 'deactivate_minify_module' ) );
		add_action( 'cornerstone_before_custom_endpoint', array( $this, 'deactivate_minify_module' ) );
		add_action( 'cornerstone_before_load_preview', array( $this, 'deactivate_minify_module' ) );

		// Themeco Pro theme page builder.
		add_action( 'cornerstone_before_boot_app', array( $this, 'deactivate_minify_module' ) );

		// ACF options pages compatibility.
		add_action( 'acf/save_post', array( $this, 'purge_page_cache' ) );

		if ( $this->is_tag_div_editor() ) {
			$this->deactivate_minify_module();
		}
	}

	/**
	 * Maybe deactivate the minify module on REST API requests.
	 *
	 * @since 2.6.2
	 */
	public function maybe_deactivate_minify_on_rest() {
		$excluded_requests = array(
			'cs_preview_state', // Cornerstone builder request key.
			'elementor-preview', // Elementor's request key.
			'ct_builder', // Oxygen builder.
		);

		$exclude = ! empty( array_intersect( $excluded_requests, array_keys( $_REQUEST ) ) );

		if ( $exclude ) {
			$this->deactivate_minify_module();
		}
	}

	/**
	 * Deactivate the minify module.
	 *
	 * @since 2.6.2
	 *
	 * @uses add_filter() Calls wp_hummingbird_is_active_module_minify hook.
	 */
	public function deactivate_minify_module() {
		add_filter( 'wp_hummingbird_is_active_module_minify', '__return_false', 500 );
	}

	/**
	 * Clear cache on beaver builder save layout action.
	 *
	 * @since 2.4.0
	 */
	public function beaver_builder_clear_cache() {
		if ( ! isset( $_POST['fl_builder_data']['post_id'] ) ) {
			return;
		}

		WP_Hummingbird::flush_cache( false, false );
	}

	/**
	 * Check if tagDiv Composer is active on site.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	private function is_tag_div_editor() {
		if ( false !== strpos( $_SERVER['SCRIPT_FILENAME'], 'includes/wpeditor.php' ) && isset( $_GET['wp_path'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Purge page cache when ACF options are saved.
	 *
	 * @since 2.7.1
	 *
	 * @param int|string $post_id  The ID of the post being edited.
	 */
	public function purge_page_cache( $post_id ) {
		if ( defined( 'WPHB_PREVENT_CACHE_CLEAR_ACF' ) && WPHB_PREVENT_CACHE_CLEAR_ACF ) {
			return;
		}

		if ( 'options' !== $post_id ) {
			return;
		}

		do_action( 'wphb_clear_page_cache' );
	}

}
