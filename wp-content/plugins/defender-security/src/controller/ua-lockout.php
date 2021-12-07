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
	 * @var \WP_Defender\Component\User_Agent
	 */
	protected $service;

	/**
	 * @var string
	 */
	protected $module_name;

	public function __construct() {
		$this->register_routes();
		$this->module_name = __( 'User Agent Banning', 'wpdef' );
		$this->model       = $this->get_model();
		$this->service     = wd_di()->get( \WP_Defender\Component\User_Agent::class );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
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
		$arr_model = $this->model->export();

		return array_merge(
			array(
				'model'       => $arr_model,
				'module_name' => $this->module_name,
				'misc'  => array(
					'no_ua' => '' === $arr_model['blacklist'] && '' === $arr_model['whitelist'],
				),
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
	 * @defender_route
	 */
	public function export_ua() {
		$data = array();

		foreach ( $this->model->get_lockout_list( 'blocklist', false ) as $ua ) {
			$data[] = array(
				'ua'   => $ua,
				'type' => 'blocklist',
			);
		}
		foreach ( $this->model->get_lockout_list( 'allowlist', false ) as $ua ) {
			$data[] = array(
				'ua'   => $ua,
				'type' => 'allowlist',
			);
		}

		$fp = fopen( 'php://memory', 'w' );
		foreach ( $data as $fields ) {
			fputcsv( $fp, $fields );
		}
		$filename = 'wdf-ua-export-' . gmdate( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// Make php send the generated csv lines to the browser.
		fpassthru( $fp );
		exit();
	}

	/**
	 * Importing UAs from exporter.
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function import_ua( Request $request ) {
		$data = $request->get_data(
			array(
				'id' => array(
					'type' => 'int',
				),
			)
		);

		$attached_id = $data['id'];
		if ( ! is_object( get_post( $attached_id ) ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$file = get_attached_file( $attached_id );
		if ( ! is_file( $file ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Your file is invalid!', 'wpdef' ),
				)
			);
		}

		$data = $this->service->verify_import_file( $file );
		if ( ! $data ) {
			return new Response(
				false,
				array(
					'message' => __( 'Your file content is invalid! Please use a CSV file format and try again.', 'wpdef' ),
				)
			);
		}

		// All good, start to import.
		foreach ( $data as $line ) {
			$this->model->add_to_list( $line[0], $line[1] );
		}

		return new Response(
			true,
			array(
				'message' => __( 'Your blocklist and allowlist have been successfully imported.', 'wpdef' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
