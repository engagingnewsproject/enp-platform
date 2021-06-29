<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Blacklist_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Traits\IP;

/**
 * Class Nf_Lockout
 * @package WP_Defender\Controller
 */
class Nf_Lockout extends Controller2 {
	use IP;

	/**
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * @var \WP_Defender\Component\Notfound_Lockout
	 */
	protected $service;

	/**
	 * @var Notfound_Lockout
	 */
	protected $model;

	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Notfound_Lockout::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Notfound_Lockout::class );
		$service       = wd_di()->get( Blacklist_Lockout::class );
		if ( $this->model->enabled && ! $service->is_ip_whitelisted( $this->get_user_ip() ) ) {
			$this->service->add_hooks();
		}
	}

	/**
	 * Queue assets and require data
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'nf_lockout', $this->data_frontend() );
	}

	/**
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data        = $request->get_data( $this->request_filter_rules() );
		$old_enabled = (bool) $this->model->enabled;

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response( true, array_merge( [
				'message' => $this->get_update_message( $data, $old_enabled ),
			], $this->data_frontend() ) );
		}

		return new Response( false, [
			'message' => $this->model->get_formatted_errors()
		] );
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget
	 *
	 * @return array
	 */
	public function data_frontend() {
		return array_merge( [
			'model' => $this->model->export(),
		], $this->dump_routes_and_nonces() );
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc
	 *
	 * @return array
	 */
	public function to_array() {
		// TODO: Implement to_array() method.
	}

	private function adapt_data( $data ) {
		$adapted_data = array();
		if ( isset( $data['detect_404'] ) ) {
			$adapted_data['enabled'] = $data['detect_404'];
		}
		if ( isset( $data['detect_404_threshold'] ) ) {
			$adapted_data['attempt'] = $data['detect_404_threshold'];
		}
		if ( isset( $data['detect_404_timeframe'] ) ) {
			$adapted_data['timeframe'] = $data['detect_404_timeframe'];
		}
		if ( isset( $data['detect_404_lockout_duration'] ) ) {
			$adapted_data['duration'] = $data['detect_404_lockout_duration'];
		}
		if ( isset( $data['detect_404_lockout_duration_unit'] ) ) {
			$adapted_data['duration_unit'] = $data['detect_404_lockout_duration_unit'];
		}
		if ( isset( $data['detect_404_lockout_ban'] ) ) {
			$adapted_data['lockout_type'] = 'permanent' === $data['detect_404_lockout_ban'] ? 'permanent' : 'timeframe';
		}
		if ( isset( $data['detect_404_blacklist'] ) ) {
			$adapted_data['blacklist'] = $data['detect_404_blacklist'];
		}
		if ( isset( $data['detect_404_whitelist'] ) ) {
			$adapted_data['whitelist'] = $data['detect_404_whitelist'];
		}
		if ( isset( $data['detect_404_lockout_message'] ) ) {
			$adapted_data['lockout_message'] = $data['detect_404_lockout_message'];
		}
		if ( isset( $data['detect_404_logged'] ) ) {
			$adapted_data['detect_logged'] = $data['detect_404_logged'];
		}

		return array_merge( $data, $adapted_data );
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset
	 *
	 * @param $data array
	 *
	 * @return boolean
	 */
	public function import_data( $data ) {
		if ( ! empty( $data ) ) {
			$data = $this->adapt_data( $data );
		} else {

			return;
		}

		$model = $this->model;
		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * Remove all settings, configs generated in this container runtime
	 *
	 * @return mixed
	 */
	public function remove_settings() {
		// TODO: Implement remove_settings() method.
	}

	/**
	 * Remove all data
	 *
	 * @return mixed
	 */
	public function remove_data() {
		// TODO: Implement remove_data() method.
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}

	/**
	 * Rules for request data
	 *
	 * @return array
	 */
	private function request_filter_rules() {
		return [
			'enabled'         => [
				'type' => 'boolean'
			],
			'attempt'         => [
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field'
			],
			'duration'        => [
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field'
			],
			'duration_unit'   => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
			'lockout_message' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
			'lockout_type'    => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			],
			'timeframe'       => [
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field'
			],
			'blacklist'       => [
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field'
			],
			'whitelist'       => [
				'type'     => 'string',
				'sanitize' => [
					'rawurldecode',
					'sanitize_textarea_field'
				]
			],
			'detect_logged'   => [
				'type' => 'boolean'
			]
		];
	}

	/**
	 * Prepare notice message
	 *
	 * @param array $data Form request.
	 * @param bool  $old_data Model activate value.
	 *
	 * @return string
	 */
	private function get_update_message( $data, $old_data ) {
		$new_data = (bool) $data['enabled'];

		// If old data and new data is matched, then it is not activated or deactivated.
		if ( $old_data === $new_data ) {
			return __( 'Your settings have been updated.', 'wpdef' );
		}

		if ( $new_data ) {
			return __( '404 Detection has been activated.', 'wpdef' );
		}

		return __( '404 Detection has been deactivated.', 'wpdef' );
	}

}
