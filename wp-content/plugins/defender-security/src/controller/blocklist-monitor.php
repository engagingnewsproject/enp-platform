<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Controller2;
use WP_Defender\Behavior\WPMUDEV;

/**
 * Class Blocklist_Monitor
 * @package WP_Defender\Controller
 */
class Blocklist_Monitor extends Controller2 {

	const CACHE_BLACKLIST_STATUS = 'wpdefender_blacklist_status', CACHE_TIME = 300;

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->register_routes();
	}

	/**
	 * @return mixed
	 */
	public function to_array() {
		return array();
	}

	/**
	 * Reset settings and clear transient.
	 */
	public function remove_settings() {
		$this->reset_blocklist_monitor();
		delete_site_transient( self::CACHE_BLACKLIST_STATUS );
	}

	public function remove_data() {}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		return $this->dump_routes_and_nonces();
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data
	 */
	public function import_data( $data ) {}

	/**
	 * @return mixed
	 */
	private function reset_blocklist_monitor() {

		return $this->make_wpmu_request( WPMUDEV::API_BLACKLIST, [], [
			'method' => 'DELETE'
		] );
	}

	/**
	 * Getting domain status.
	 *
	 * @return int
	 */
	protected function domain_status() {
		$response = $this->make_wpmu_request( WPMUDEV::API_BLACKLIST );

		if ( is_wp_error( $response ) ) {
			if ( 412 === $response->get_error_code() ) {
				return - 1;
			}

			return $response;
		}
		$status = 1;
		foreach ( $response['services'] as $service ) {
			if ( true === $service['blacklisted'] ) {
				$status = 0;
			}
		}

		return $status;
	}

	/**
	 * Endpoint for getting domain status.
	 * @defender_route
	 */
	public function blacklist_status() {
		$status = get_site_transient( self::CACHE_BLACKLIST_STATUS );
		if ( false === $status ) {
			$status = $this->domain_status();
			set_site_transient( self::CACHE_BLACKLIST_STATUS, $status, self::CACHE_TIME );
		}

		if ( is_wp_error( $status ) ) {
			return new Response( false, [
				'status_error' => $status->get_error_message()
			] );
		}

		return new Response( true, [
			'status' => $status
		] );
	}

	/**
	 * @param Request $request
	 *
	 * @return bool|Response
	 * @defender_route
	 */
	public function toggle_blacklist_status( Request $request ) {
		$data           = $request->get_data( [
			'status' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			]
		] );
		$current_status = isset( $data['status'] ) ? $data['status'] : null;
		if ( ! in_array( $current_status, [ 'good', 'new', 'blacklisted' ] ) ) {
			return false;
		}

		if ( ! $this->is_pro() ) {
			return new Response( false, [
				'message' => __( "A WPMU DEV subscription is required for blocklist monitoring.", 'wpdef' )
			] );
		}
		if ( 'new' === $current_status ) {
			$this->make_wpmu_request( WPMUDEV::API_BLACKLIST, [], [
				'method' => 'POST'
			] );
			$status = $this->domain_status();
		} else {
			$this->reset_blocklist_monitor();
			$status = - 1;
		}
		set_site_transient( self::CACHE_BLACKLIST_STATUS, $status, self::CACHE_TIME );

		return new Response( true, [
			'status' => $status
		] );
	}

	/**
	 * @param string $current_status
	 *
	 * @return mixed
	 */
	public function change_status( $current_status ) {
		$status = get_site_transient( self::CACHE_BLACKLIST_STATUS );
		if ( false === $status ) {
			$status = $this->domain_status();
		}
		// Without changes.
		if ( $status === $current_status ) {
			return;
		}
		if ( '1' === $current_status ) {
			$this->make_wpmu_request( WPMUDEV::API_BLACKLIST, [], [
				'method' => 'POST'
			] );
			$status = $this->domain_status();
		} else {
			$this->reset_blocklist_monitor();
			$status = - 1;
		}
		set_site_transient( self::CACHE_BLACKLIST_STATUS, $status, self::CACHE_TIME );
	}

	/**
	 * Get domain status. Variants:
	 * '1' -> 'good'
	 */
	public function get_status() {
		$status = get_site_transient( self::CACHE_BLACKLIST_STATUS );
		if ( false === $status ) {
			$status = $this->domain_status();
		}

		if ( is_wp_error( $status ) ) {
			return $status->get_error_message();
		}

		return $status;
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		if ( ! $this->is_pro() ) {
			return array(
				sprintf( __( 'Inactive %s', 'wpdef' ), '<span class="sui-tag sui-tag-pro">Pro</span>' ),
			);
		}

		if ( '1' === (string)$this->get_status() ) {
			$strings = array( __( 'Active', 'wpdef' ) );
		} else {
			$strings = array( __( 'Inactive', 'wpdef' ) );
		}

		return $strings;
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( $config, $is_pro ) {
		if ( $is_pro ) {
			$strings = $config['enabled'] ? array( __( 'Active', 'wpdef' ) ) : array( __( 'Inactive', 'wpdef' ) );
		} else {
			$strings = array(
				sprintf(
				/* translators: ... */
					__( 'Inactive %s', 'wpdef' ),
					'<span class="sui-tag sui-tag-pro">Pro</span>'
				)
			);
		}

		return $strings;
	}

	/**
	 * Define labels for settings key.
	 *
	 * @return array
	 */
	public function labels() {

		return array(
			'enabled' => __( 'Blocklist Monitor', 'wpdef' ),
			'status'  => __( 'Status', 'wpdef' ),
		);
	}
}
