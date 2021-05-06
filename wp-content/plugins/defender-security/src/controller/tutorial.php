<?php

namespace WP_Defender\Controller;

use Calotes\Component\Response;
use Calotes\Helper\Route;
use WP_Defender\Controller2;
use WP_Defender\Behavior\WPMUDEV;

/**
 * Class Tutorial
 * @package WP_Defender\Controller
 */
class Tutorial extends Controller2 {
	public $slug = 'wdf-tutorial';

	/**
	 * Used to make sure that the Whitelabel section and the 'doc_links_enabled' setting are enabled.
	 *
	 * @var boolean
	 */
	private $show_doc_links;

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->show_doc_links = $this->is_show_doc_links();
		if ( ! $this->show_doc_links ) {
			$this->register_page(
				esc_html__( 'Tutorials', 'wpdef' ),
				$this->slug,
				array(
					&$this,
					'main_view',
				),
				$this->parent_slug
			);
		}
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->register_routes();
	}

	private function is_show_doc_links() {
		$settings = $this->get_whitelabel_data();

		return ! empty( $settings )
			&& $settings['enabled']
			&& $settings['doc_links_enabled'];
	}

	/**
	 * Enqueue assets & output data
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

	public function is_show() {
		return ! get_site_option( 'wp_defender_hide_tutorials' ) && ! $this->show_doc_links;
	}

	/**
	 * @return mixed
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
	 * Hide tutorials
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

	/**
	 * @return mixed
	 */
	function remove_settings() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	/**
	 * @return mixed
	 */
	function remove_data() {
		delete_site_option( 'wp_defender_hide_tutorials' );
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget
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
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset
	 *
	 * @param $data array
	 *
	 * @return boolean
	 */
	function import_data( $data ) {
		// TODO: Implement import_data() method.
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
