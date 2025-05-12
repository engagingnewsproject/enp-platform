<?php
/**
 * Handle tutorials page.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Calotes\Helper\Route;
use WP_Defender\Controller;
use Calotes\Component\Response;

/**
 * Handles tutorials page.
 */
class Tutorial extends Controller {

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-tutorial';

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		// Check if tutorials should be hidden.
		$hide = apply_filters( 'wpmudev_branding_hide_doc_link', false );
		if ( ! $hide ) {
			$this->register_page(
				esc_html__( 'Tutorials', 'wpdef' ),
				$this->slug,
				array( &$this, 'main_view' ),
				$this->parent_slug
			);
			add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
			$this->register_routes();
		}
	}

	/**
	 * Enqueue assets & output data.
	 */
	public function enqueue_assets(): void {
		if ( $this->is_page_active() ) {
			wp_localize_script( 'def-tutorial', 'tutorial', $this->data_frontend() );
			wp_enqueue_script( 'def-tutorial' );
			$this->enqueue_main_assets();
		}
	}

	/**
	 * Renders the main view for this page.
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Checks if the tutorial page should be shown.
	 *
	 * @return bool True if the page should be shown, false otherwise.
	 */
	public function is_show(): bool {
		return ! get_site_option( 'wp_defender_hide_tutorials' ) && ! apply_filters(
			'wpmudev_branding_hide_doc_link',
			false
		);
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		[ $routes, $nonces ] = Route::export_routes( 'tutorial' );

		return array(
			'show'      => $this->is_show(),
			'endpoints' => $routes,
			'nonces'    => $nonces,
		);
	}

	/**
	 * Hide tutorials.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function hide(): Response {
		update_site_option( 'wp_defender_hide_tutorials', true );

		return new Response(
			true,
			array(
				'message' => sprintf(
				/* translators: %s: Tutorial link. */
					__(
						'The widget has been removed. You can check all defender tutorials at the <a href="%s">tutorials\' tab</a> at any time.',
						'wpdef'
					),
					network_admin_url( 'admin.php?page=wdf-tutorial' )
				),
			)
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array_merge(
			array( 'show' => $this->is_show() ),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}
}