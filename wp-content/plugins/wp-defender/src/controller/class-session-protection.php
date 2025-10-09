<?php
/**
 * Handle session protection module.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use WP_Defender\Traits\User;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Session_Protection as Service;
use WP_Defender\Model\Setting\Session_Protection as Settings;

/**
 * Handle session protection module.
 */
class Session_Protection extends Event {
	use User;

	/**
	 * The model for the session protection module.
	 *
	 * @var Settings|null
	 */
	protected ?Settings $model = null;

	/**
	 * The service for the session protection module.
	 *
	 * @var Service|null
	 */
	protected ?Service $service = null;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->model   = wd_di()->get( Settings::class );
		$this->service = wd_di()->get( Service::class );
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );
		$this->register_routes();
		if ( $this->model->enabled ) {
			add_action( 'init', array( $this->service, 'handle_session_timeout' ) );
			add_action( 'wp_enqueue_scripts', array( $this->service, 'enqueue_idle_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this->service, 'enqueue_idle_scripts' ) );
			add_action( 'wp_ajax_wpdef_logout', array( $this->service, 'logout' ) );
			add_action( 'wp_login', array( $this->service, 'update_last_activity' ) );

			// Show login modal with custom message.
			add_filter( 'wp_login_errors', array( $this->service, 'login_modal_message' ) );
			add_action( 'login_head', array( $this->service, 'login_modal_message_styles' ) );

			// Attach IPs to the current user session.
			if ( $this->model->has_properties() ) {
				add_filter( 'attach_session_information', array( $this->service, 'attach_session_information' ) );
			}
		}
	}

	/**
	 * Provide data to the frontend via localized script.
	 *
	 * @param array $data Data collection is ready to passed.
	 *
	 * @return array Modified data array with added this controller data.
	 */
	public function script_data( array $data ): array {
		$data['session_protection'] = $this->data_frontend();

		return $data;
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'model'      => $this->model->export(),
				'properties' => $this->service::session_lock_properties(),
				'roles'      => $this->get_all_editable_roles(),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Save settings.
	 *
	 * @param Request $request The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$model_data = $request->get_data_by_model( $this->model );
		$prev_data  = $this->model->get_old_settings();
		$this->model->import( $model_data );
		if ( $this->model->validate() ) {
			$this->model->save();
			// Changes for Hub.
			\WP_Defender\Component\Config\Config_Hub_Helper::set_clear_active_flag();

			// Maybe track if any settings have changed except user roles.
			if ( $this->maybe_track() && ! empty( $prev_data ) &&
				(
					( $this->model->enabled !== $prev_data['enabled'] )
					|| ! empty( array_diff( $this->model->lock_properties, $prev_data['lock_properties'] ) )
					|| $this->model->idle_timeout !== $prev_data['idle_timeout']
				)
			) {
				$data = array(
					'Idle Time'    => $this->model->idle_timeout,
					'Session Lock' => $this->service->get_session_lock_string(),
					'Action'       => $this->model->enabled ? 'Enable' : 'Disable',
				);
				$this->track_feature( 'def_session_protection', $data );
			}

			$message = esc_html__( 'Settings updated successfully!', 'wpdef' );
			if ( ( $prev_data['enabled'] ?? false ) !== $this->model->enabled && ! $this->model->enabled ) {
				/* translators: 1. tag open, 2. tag close */
				$message = sprintf( esc_html__( '%1$s Session Protection %2$s deactivated successfully!', 'wpdef' ), '<strong>', '</strong>' );
			} elseif ( ( $prev_data['enabled'] ?? false ) !== $this->model->enabled && $this->model->enabled ) {
				/* translators: 1. tag open, 2. tag close */
				$message = sprintf( esc_html__( '%1$s Session Protection %2$s activated successfully!', 'wpdef' ), '<strong>', '</strong>' );
				// Update last activity time to prevent instant logout.
				$this->service->update_last_activity();
			}

			return new Response(
				true,
				array_merge(
					array(
						'message'    => $message,
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response( false, array( 'message' => $this->model->get_formatted_errors() ) );
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 *
	 * @return array
	 */
	public function to_array() {
		return array();
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data Data from other source.
	 *
	 * @return null|void
	 */
	public function import_data( array $data ) {
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			$this->service->update_last_activity();
			return;
		}
	}

	/**
	 * Remove all settings, configs generated in this container runtime.
	 *
	 * @return mixed
	 */
	public function remove_settings() {
	}

	/**
	 * Remove all data.
	 */
	public function remove_data() {
		delete_site_transient( Service::LOGOUT_MSG_TRANSIENT_KEY );
	}

	/**
	 * Export strings.
	 *
	 * @return array
	 */
	public function export_strings() {
		return array(
			$this->model->is_active() ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Provides data for the dashboard widget.
	 *
	 * @return array An array of dashboard widget data.
	 */
	public function dashboard_widget(): array {
		return array( 'model' => $this->model->export() );
	}
}