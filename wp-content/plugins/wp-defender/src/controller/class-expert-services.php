<?php
/**
 * Handles all service related actions.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Controller;
use WP_Defender\Behavior\WPMUDEV;

/**
 * Contains methods for handling scans.
 */
class Expert_Services extends Controller {

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-expert-services';

	/**
	 * Initializes the model and service, registers routes.
	 */
	public function __construct() {
		$wpmudev = new WPMUDEV();
		if ( $wpmudev->is_pro() && ! $wpmudev->is_whitelabel_enabled() ) {
			$this->register_page(
				esc_html__( 'Expert Services', 'wpdef' ),
				$this->slug,
				array( $this, 'main_view' ),
				$this->parent_slug
			);
			$this->register_routes();
			add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		}
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets(): void {
		if ( $this->is_page_active() ) {
			wp_enqueue_script( 'def-expert-services' );
			$this->enqueue_main_assets();
		}
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		return array();
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 *
	 * @return array
	 */
	public function to_array() {
		return array();
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data Data from other source.
	 *
	 * @return null|void
	 */
	public function import_data( array $data ) {
		// TODO: Implement import_data() method.
	}

	/**
	 * Remove all settings, configs generated in this container runtime.
	 *
	 * @return mixed
	 */
	public function remove_settings() {
		// TODO: Implement remove_settings() method.
	}

	/**
	 * Remove all data.
	 *
	 * @return mixed
	 */
	public function remove_data() {
		// TODO: Implement remove_data() method.
	}

	/**
	 * Export strings.
	 *
	 * @return array
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}
}