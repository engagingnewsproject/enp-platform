<?php

namespace WP_Defender\Controller;

use Calotes\Component\Response;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Controller2;

class WAF extends Controller2 {
	public $slug = 'wdf-waf';

	private $wpmudev;

	public function __construct() {
		$this->wpmudev = wd_di()->get( \WP_Defender\Behavior\WPMUDEV::class );

		// Return the constructor and do not register WAF page if whitelable is enabled
		if ( $this->wpmudev->is_whitelabel_enabled() ) {
			return;
		}

		$this->register_page( esc_html__( 'WAF', 'wpdef' ), $this->slug, [
			&$this,
			'main_view'
		], $this->parent_slug );
		add_action( 'defender_enqueue_assets', [ &$this, 'enqueue_assets' ] );
		$this->register_routes();
	}

	/**
	 * Checks whether we're on WPMU DEV Hosting
	 *
	 * @return bool
	 */
	public function is_wpmu_hosting() {
		return isset( $_SERVER['WPMUDEV_HOSTED'] ) && ! empty( $_SERVER['WPMUDEV_HOSTED'] );
	}

	/**
	 * Enqueue assets & output data
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-waf', 'waf', $this->data_frontend() );
		wp_enqueue_script( 'def-waf' );
		$this->enqueue_main_assets();
	}

	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * @param $site_id
	 *
	 * @return bool|mixed
	 */
	public function get_waf_status( $site_id ) {
		if ( false === $site_id ) {
			return false;
		}

		$cached = get_site_transient( 'def_waf_status' );
		if ( in_array( $cached, [ 'enabled', 'disabled' ] ) ) {
			return $cached === 'enabled';
		}

		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$ret = $this->make_wpmu_request( WPMUDEV::API_WAF );
		if ( is_wp_error( $ret ) ) {
			return false;
		}
		$status = $ret['waf']['is_active'];
		set_site_transient( 'def_waf_status', $status === true ? 'enabled' : 'disabled', 300 );

		return $status;
	}

	/**
	 * And endpoint for removing the cache and return latest data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function recheck() {
		delete_site_transient( 'def_waf_status' );

		return new Response( true, [
			'waf' => $this->data_frontend()['waf']
		] );
	}

	/**
	 * @return bool
	 */
	public function maybe_show_dashboard_widget() {
		if (
			//not hosted on us
			! $this->is_wpmu_hosting()
			//is pro
			&& $this->is_pro() === true
			//and enable whitelabel
			&& $this->is_whitelabel_enabled()
		) {
			//hide it
			return false;
		}

		return true;
	}

	/**
	 * This is for dashboard widget.
	 *
	 * @return array
	 */
	public function to_array() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$site_id = $this->get_site_id();

		return [
			'waf' => [
				'hosted'     => $this->is_wpmu_hosting(),
				'status'     => $this->get_waf_status( $site_id ),
				'maybe_show' => $this->maybe_show_dashboard_widget(),
			],
		];
	}

	public function remove_settings() {}

	public function remove_data() {}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$site_id = $this->get_site_id();

		return array_merge( [
			'site_id' => $site_id,
			'waf'     => [
				'hosted' => $this->is_wpmu_hosting(),
				'status' => $this->get_waf_status( $site_id ),
			],
		], $this->dump_routes_and_nonces() );
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data
	 */
	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}
}