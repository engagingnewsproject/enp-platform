<?php
/**
 * Handles not found lockout.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use WP_Defender\Traits\IP;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Setting;
use WP_Defender\Component\Blacklist_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;

/**
 * Handles not found lockout.
 */
class Nf_Lockout extends Event {

	use IP;
	use Setting;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-ip-lockout';

	/**
	 * Service for handling logic.
	 *
	 * @var \WP_Defender\Component\Notfound_Lockout
	 */
	protected $service;

	/**
	 * The model for handling the data.
	 *
	 * @var Notfound_Lockout
	 */
	protected $model;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Notfound_Lockout::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Notfound_Lockout::class );
		$service       = wd_di()->get( Blacklist_Lockout::class );
		$ip            = $this->get_user_ip();
		if ( $this->model->enabled && ! $service->are_ips_whitelisted( $ip ) ) {
			$this->service->add_hooks();
		}
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'nf_lockout', $this->data_frontend() );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data        = $request->get_data( $this->request_filter_rules() );
		$old_enabled = (bool) $this->model->enabled;
		$prev_data   = $this->model->export();

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();
			// Maybe track.
			if ( ! defender_is_wp_cli() && $this->is_feature_state_changed( $prev_data, $data ) ) {
				$track_data = array(
					'Action'   => $data['enabled'] ? 'Enabled' : 'Disabled',
					'Duration' => 'timeframe' === $data['lockout_type'] ? 'Temporary' : 'Permanent',
				);
				$this->track_feature( 'def_404_detection', $track_data );
			}

			return new Response(
				true,
				array_merge(
					array(
						'message'    => $this->get_update_message(
							$data,
							$old_enabled,
							Notfound_Lockout::get_module_name()
						),
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
		);
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'model' => $this->model->export(),
				'misc'  => array( 'module_name' => Notfound_Lockout::get_module_name() ),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Adapt the given data array by adding additional fields if necessary.
	 *
	 * @param  array $data  The data array to adapt.
	 *
	 * @return array The adapted data array.
	 */
	private function adapt_data( array $data ): array {
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
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		if ( ! empty( $data ) ) {
			$data  = $this->adapt_data( $data );
			$model = $this->model;
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
		}
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Rules for request data.
	 *
	 * @return array
	 */
	private function request_filter_rules(): array {
		return array(
			'enabled'         => array(
				'type' => 'boolean',
			),
			'attempt'         => array(
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field',
			),
			'duration'        => array(
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field',
			),
			'duration_unit'   => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			'lockout_message' => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			'lockout_type'    => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			'timeframe'       => array(
				'type'     => 'int',
				'sanitize' => 'sanitize_text_field',
			),
			'blacklist'       => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field',
			),
			'whitelist'       => array(
				'type'     => 'string',
				'sanitize' => array(
					'rawurldecode',
					'sanitize_textarea_field',
				),
			),
			'detect_logged'   => array(
				'type' => 'boolean',
			),
		);
	}
}