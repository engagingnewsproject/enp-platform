<?php
/**
 * Class for integration with the Gutenberg editor: WP_Hummingbird_Gutenberg_Integration class
 *
 * @since 1.9.4
 * @package Hummingbird\Core\Integration
 */

namespace Hummingbird\Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gutenberg
 */
class Gutenberg {

	/**
	 * Enabled status.
	 *
	 * @since 1.9.4
	 *
	 * @var bool $enabled
	 */
	private $enabled;

	/**
	 * WP_Hummingbird_Gutenberg_Integration constructor.
	 *
	 * @since 1.9.4
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Detect page caching and gutenberg, and initialize block editor components
	 *
	 * @since 2.5.0
	 */
	public function init() {
		// Page caching is not enabled.
		if ( ! apply_filters( 'wp_hummingbird_is_active_module_page_cache', false ) ) {
			return;
		}

		$this->check_for_gutenberg();

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_blocks' ) );
	}

	/**
	 * Make sure we only enqueue when Gutenberg is active.
	 *
	 * For WordPress pre 5.0 - only when Gutenberg plugin is installed.
	 * For WordPress 5.0+ - only when Classic Editor is NOT installed.
	 *
	 * @since 1.9.4
	 */
	private function check_for_gutenberg() {
		global $wp_version;

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if WordPress 5.0 or higher.
		$is_wp5point0 = version_compare( $wp_version, '4.9.9', '>' );

		if ( $is_wp5point0 ) {
			$this->enabled = ! is_plugin_active( 'classic-editor/classic-editor.php' );
		} else {
			$this->enabled = is_plugin_active( 'gutenberg/gutenberg.php' );
		}
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.9.4
	 */
	public function enqueue_gutenberg_blocks() {
		if ( ! $this->enabled ) {
			return;
		}

		// Gutenberg block scripts.
		wp_enqueue_script(
			'wphb-gutenberg',
			WPHB_DIR_URL . 'admin/assets/js/wphb-gb-block.min.js',
			array(),
			WPHB_VERSION,
			true
		);

		wp_localize_script(
			'wphb-gutenberg',
			'wphb',
			array(
				'strings' => array(
					'button' => esc_html__( 'Clear HB post cache', 'wphb' ),
					'notice' => esc_html__( 'Cache for post has been cleared.', 'wphb' ),
				),
				'nonces'  => array(
					'HBFetchNonce' => wp_create_nonce( 'wphb-fetch' ),
				),
			)
		);
	}

}
