<?php

namespace WP_Defender\Controller;

use Calotes\Component\Response;
use Calotes\Helper\Route;
use WP_Defender\Controller2;

/**
 * Class Tutorial
 * @package WP_Defender\Controller
 */
class Tutorial extends Controller2 {
	public $slug = 'wdf-tutorial';

	public function __construct() {
		// Check if tutorials should be hidden.
		$hide = apply_filters( 'wpmudev_branding_hide_doc_link', false );
		if ( ! $hide ) {
			$this->register_page(
				esc_html__( 'Tutorials', 'wpdef' ),
				$this->slug,
				array(
					&$this,
					'main_view',
				),
				$this->parent_slug
			);
			add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
			$this->register_routes();
		}
	}

	/**
	 * Enqueue assets & output data.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-tutorial', 'tutorial', $this->data_frontend() );
		wp_enqueue_script( 'def-tutorial' );
		$this->enqueue_main_assets();
	}

	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * @return bool
	 */
	public function is_show() {
		return ! get_site_option( 'wp_defender_hide_tutorials' ) && ! apply_filters( 'wpmudev_branding_hide_doc_link', false );
	}

	/**
	 * @return array
	 */
	public function to_array() {
		list( $routes, $nonces ) = Route::export_routes( 'tutorial' );

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
	public function hide() {
		update_site_option( 'wp_defender_hide_tutorials', true );

		return new Response(
			true,
			array(
				'message' => sprintf(
				/* translators: %s - tutorial link */
					__(
						"The widget has been removed. You can check all defender tutorials at the <a href=\"%s\">tutorials' tab</a> at any time.",
						'wpdef'
					),
					network_admin_url( 'admin.php?page=wdf-tutorial' )
				),
			)
		);
	}

	public function remove_settings() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	public function remove_data() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		return array_merge(
			array(
				'time_read'       => __( 'min read', 'wpdef' ),
				'title_read_link' => __( 'Read article', 'wpdef' ),
				'show'            => $this->is_show(),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param $data array
	 */
	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
