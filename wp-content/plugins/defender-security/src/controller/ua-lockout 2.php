<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Traits\Setting;

/**
 * Class UA_Lockout.
 *
 * @package WP_Defender\Controller
 * @since 2.6.0
 */
class UA_Lockout extends \WP_Defender\Controller2 {
	use Setting;

	/**
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\User_Agent_Lockout
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $module_name;

	public function __construct() {
		$this->register_routes();
		$this->module_name = __( 'User Agent Banning', 'wpdef' );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model = $this->get_model();
	}

	/**
	 * @return \WP_Defender\Model\Setting\User_Agent_Lockout
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\User_Agent_Lockout();
	}


	/**
	 * Queue assets and require data.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'ua_lockout', $this->data_frontend() );
	}


	/**
	 * Save settings.
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data        = $request->get_data_by_model( $this->model );
		$old_enabled = (bool) $this->model->enabled;

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message' => $this->get_update_message( $data, $old_enabled, $this->module_name ),
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

	public function remove_settings() {}

	public function remove_data() {}

	public function to_array() {}

	/**
	 * @return array
	 */
	public function data_frontend() {
		$model = $this->get_model();

		return array_merge(
			array(
				'model'       => $model->export(),
				'module_name' => $this->module_name,
			),
			$this->dump_routes_and_nonces()
		);
	}

	public function import_data( $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
