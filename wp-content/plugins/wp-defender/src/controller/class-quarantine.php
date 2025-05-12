<?php
/**
 * Handles quarantine related actions.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Controller;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Quarantine as Quarantine_Component;

/**
 * Handles quarantine related actions.
 */
class Quarantine extends Controller {

	/**
	 * Quarantine component.
	 *
	 * @var Quarantine_Component
	 */
	private $quarantine_component;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_routes();
		$this->quarantine_component = wd_di()->get( Quarantine_Component::class );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$quarantine_directory = array(
			'url'                                   => $this->quarantine_component->quarantine_directory_url(),
			'permission'                            => $this->quarantine_component::QUARANTINE_DIRECTORY_PERMISSION,
			'is_quarantine_directory_url_forbidden' => $this->quarantine_component->is_quarantine_directory_url_forbidden(),
		);

		return array_merge(
			array(
				'list'                 => $this->quarantine_component->quarantine_collection(),
				'cron_schedules'       => $this->quarantine_component->cron_schedules(),
				'quarantine_directory' => $quarantine_directory,
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
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
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
		$this->quarantine_component->on_uninstall();
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
	 * Restore the quarantined file.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function restore_file( Request $request ) {
		$data = $request->get_data(
			array(
				'id' => array(
					'type' => 'int',
				),
			)
		);

		$action = $this->quarantine_component->restore_file( $data['id'] );

		if ( isset( $action['success'] ) && true === $action['success'] ) {
			return new Response(
				true,
				array(
					'message'               => $action['message'],
					'file_id'               => $data['id'],
					'success'               => true,
					'quarantine_collection' => $this->quarantine_component->quarantine_collection(),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $action['message'],
				'file_id' => $data['id'],
				'success' => false,
			)
		);
	}

	/**
	 * Get quarantine collection.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function quarantine_collection( Request $request ) {
		$data = $this->quarantine_component->quarantine_collection();

		return new Response(
			true,
			array(
				'list' => $data,
			)
		);
	}

	/**
	 * Delete quarantined file.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function delete_file( Request $request ): Response {
		$data = $request->get_data(
			array(
				'id'        => array(
					'type' => 'int',
				),
				'file_name' => array(
					'type' => 'string',
				),
			)
		);

		$action = $this->quarantine_component->delete_quarantined_file( $data['id'] );

		if ( $action ) {
			return new Response(
				true,
				array(
					'message'               => sprintf(
						/* translators: 1: Filename with extension */
						esc_html__( 'Deleted %1$s permanently.', 'wpdef' ),
						'<strong>' . $data['file_name'] . '</strong>'
					),
					'file_id'               => $data['id'],
					'success'               => true,
					'quarantine_collection' => $this->quarantine_component->quarantine_collection(),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' =>
					sprintf(
					/* translators: %s: Filename with extension */
						esc_html__(
							'Deleting %s failed.',
							'wpdef'
						),
						'<strong>' . $data['file_name'] . '</strong>'
					),
				'file_id' => $data['id'],
				'success' => false,
			)
		);
	}
}