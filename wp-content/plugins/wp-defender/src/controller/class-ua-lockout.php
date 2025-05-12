<?php
/**
 * Handles user agent lockout.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Exception;
use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Setting;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\User_Agent as User_Agent_Service;

/**
 * Handles user agent lockout.
 *
 * @since 2.6.0
 */
class UA_Lockout extends Event {

	use Setting;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-ip-lockout';

	/**
	 * The model for handling the data.
	 *
	 * @var User_Agent_Lockout
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var User_Agent_Service
	 */
	protected $service;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_routes();
		$this->model   = $this->get_model();
		$this->service = wd_di()->get( User_Agent_Service::class );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
	}

	/**
	 * Returns an instance of the User_Agent_Lockout model class.
	 *
	 * @return User_Agent_Lockout The User_Agent_Lockout model class.
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new User_Agent_Lockout();
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-iplockout', 'ua_lockout', $this->data_frontend() );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 * @throws Exception  If the table is not defined.
	 */
	public function save_settings( Request $request ) {
		$data        = $request->get_data_by_model( $this->model );
		$old_enabled = (bool) $this->model->enabled;
		$prev_data   = $this->model->export();

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();
			// Maybe track.
			if ( ! defender_is_wp_cli() && $this->is_feature_state_changed( $prev_data, $data ) ) {
				$track_data = array(
					'Action'                      => $data['enabled'] ? 'Enabled' : 'Disabled',
					'No of Bots in the Whitelist' => count( $this->model->get_lockout_list( 'allowlist', false ) ),
					'No of Bots in the Blocklist' => count( $this->model->get_lockout_list( 'blocklist', false ) ),
				);
				$this->track_feature( 'def_user_agent_banning', $track_data );
			}

			return new Response(
				true,
				array_merge(
					array(
						'message'    => $this->get_update_message(
							$data,
							$old_enabled,
							User_Agent_Lockout::get_module_name()
						),
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array( 'message' => $this->model->get_formatted_errors() )
		);
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
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$arr_model = $this->model->export();

		return array_merge(
			array(
				'model' => $arr_model,
				'misc'  => array(
					'no_ua'        => '' === $arr_model['blacklist'] && '' === $arr_model['whitelist'],
					'module_name'  => User_Agent_Lockout::get_module_name(),
					'spam_ua_list' => User_Agent_Service::get_spam_user_agent_list(),
				),
			),
			$this->dump_routes_and_nonces()
		);
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
		if ( isset( $data['ua_banning_enabled'] ) ) {
			$adapted_data['enabled'] = (bool) $data['ua_banning_enabled'];
		}
		if ( isset( $data['ua_banning_message'] ) ) {
			$adapted_data['message'] = $data['ua_banning_message'];
		}
		if ( isset( $data['ua_banning_blacklist'] ) ) {
			$adapted_data['blacklist'] = $data['ua_banning_blacklist'];
		}
		if ( isset( $data['ua_banning_whitelist'] ) ) {
			$adapted_data['whitelist'] = $data['ua_banning_whitelist'];
		}
		if ( isset( $data['ua_banning_empty_headers'] ) ) {
			$adapted_data['empty_headers'] = (bool) $data['ua_banning_empty_headers'];
		}

		return array_merge( $data, $adapted_data );
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 *
	 * @throws Exception If table is not defined.
	 */
	public function import_data( array $data ): void {
		$model = $this->get_model();
		if ( ! empty( $data ) ) {
			$data = $this->adapt_data( $data );
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
		} else {
			$default_ua_values    = $model->get_default_values();
			$model->enabled       = false;
			$model->message       = $default_ua_values['message'];
			$model->blacklist     = $default_ua_values['blacklist'];
			$model->whitelist     = $default_ua_values['whitelist'];
			$model->empty_headers = false;
			$model->save();
		}
	}

	/**
	 * Exports User Agents to a CSV file.
	 *
	 * @return void
	 * @defender_route
	 * @since 2.6.0
	 */
	public function export_ua(): void {
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
		// WP_Filesystem class doesn’t directly provide a function for opening a stream to php://memory with the 'w' mode.
		$fp = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		foreach ( $data as $fields ) {
			fputcsv( $fp, $fields, ',', '"', '\\' );
		}
		$filename = 'wdf-ua-export-' . wp_date( 'ymdHis' ) . '.csv';
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
	 * @param  Request $request  The request object.
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
				array( 'message' => esc_html__( 'Your file is invalid!', 'wpdef' ) )
			);
		}

		$file = get_attached_file( $attached_id );
		if ( ! is_file( $file ) ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Your file is invalid!', 'wpdef' ) )
			);
		}

		$data = $this->service->verify_import_file( $file );
		if ( ! $data ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Your file content is invalid! Please use a CSV file format and try again.', 'wpdef' ),
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
				'message'  => esc_html__( 'Your blocklist and allowlist have been successfully imported.', 'wpdef' ),
				'interval' => 1,
			)
		);
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