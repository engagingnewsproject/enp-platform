<?php
/**
 * Handles password reset operations.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_User;
use WP_Error;
use Exception;
use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Formats;
use WP_Defender\Component\Config\Config_Hub_Helper;

/**
 * Handles password reset operations.
 */
class Password_Reset extends Event {

	use Formats;

	/**
	 * The model for handling the data.
	 *
	 * @var \WP_Defender\Model\Setting\Password_Reset
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var \WP_Defender\Component\Password_Protection
	 */
	protected $service;

	/**
	 * Default message.
	 *
	 * @var string
	 */
	public $default_msg;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->model       = $this->get_model();
		$this->service     = wd_di()->get( \WP_Defender\Component\Password_Protection::class );
		$default_values    = $this->model->get_default_values();
		$this->default_msg = $default_values['message'];
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );
		$this->register_routes();
		if ( $this->model->is_active() ) {
			// Update site url on sub-site when MaskLogin is disabled.
			if (
				is_multisite() && ! is_main_site()
				&& ! wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->is_active()
			) {
				add_filter( 'network_site_url', array( $this, 'filter_site_url' ), 100, 2 );
			}
			add_action( 'validate_password_reset', array( $this, 'handle_reset_check_password' ), 10, 2 );
			add_action( 'profile_update', array( $this, 'handle_update_user' ), 10, 2 );
			add_action( 'password_reset', array( $this, 'handle_password_reset' ), 10 );
			add_action( 'wp_authenticate_user', array( $this, 'handle_login_password' ), 999, 2 );
			// No use 'user_profile_update_errors' because there aren't checks for password resetting for logged user in.
		}
	}

	/**
	 * Filters the site URL for password resetting on sub-sites.
	 *
	 * @param  string $url  The original site URL.
	 * @param  string $path  The path to append to the site URL.
	 *
	 * @return string The modified site URL.
	 */
	public function filter_site_url( string $url, string $path ) {
		$action = defender_get_data_from_request( 'action', 'g' );
		if ( $path && is_string( $path )
			&& in_array( $action, array( 'rp', 'resetpass' ), true )
			&& false !== stristr( $url, 'wp-login.php' )
		) {
			return get_option( 'siteurl' ) . '/' . ltrim( $path, '/' );
		}

		return $url;
	}

	/**
	 * Get model.
	 *
	 * @return \WP_Defender\Model\Setting\Password_Reset
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Password_Reset();
	}

	/**
	 *  Handle password update on login.
	 *
	 * @param  WP_User|WP_Error $user  WP_User object or WP_Error.
	 * @param  string           $password  Password plain string.
	 *
	 * @return WP_User|WP_Error Return user object or error object.
	 */
	public function handle_login_password( $user, $password ) {
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}
		if ( empty( $password ) ) {
			return new WP_Error(
				'defender_invalid_password',
				esc_html__( 'Invalid user data.', 'wpdef' )
			);
		}
		$this->service->do_force_reset( $user, $password );

		return $user;
	}

	/**
	 * Handle password update on password reset.
	 *
	 * @param  WP_Error         $errors  Error object.
	 * @param  WP_Error|WP_User $user  WP_User object or WP_Error.
	 *
	 * @return null|WP_Error
	 */
	public function handle_reset_check_password( WP_Error $errors, $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}

		if ( ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return;
		}

		// Check if display_reset_password_warning cookie enabled then show warning message on reset password page.
		if ( isset( $_COOKIE['display_reset_password_warning'] ) ) {
			$message = empty( $this->model->message ) ? $this->default_msg : $this->model->message;
			$errors->add( 'defender_password_reset', $message );
			// Remove the one time cookie notice once it's displayed.
			$this->service->remove_cookie_notice( 'display_reset_password_warning' );

			return $errors;
		}

		$login_password = $this->service->get_submitted_password();
		if (
			! empty( $user->ID )
			&& ! empty( $login_password )
			&& wp_check_password( $login_password, get_userdata( $user->ID )->user_pass, $user->ID )
		) {
			$message = wp_kses(
				esc_html__( 'This password has been used already. Please choose a different one.', 'wpdef' ),
				array( 'strong' => array() )
			);
			$errors->add( 'defender_password_reset', $message );

			return $errors;
		}

		return $errors;
	}

	/**
	 * Update the time when a user resets their password.
	 *
	 * @param  WP_User $user  User object.
	 *
	 * @return void
	 */
	public function handle_password_reset( WP_User $user ): void {
		$this->service->handle_password_updated( $user );
	}

	/**
	 * Update password data when a user object is set or updated.
	 *
	 * @param  int     $user_id  User ID.
	 * @param  WP_User $old_user_data  Old user data.
	 *
	 * @return void
	 */
	public function handle_update_user( int $user_id, WP_User $old_user_data ) {
		$user = get_userdata( $user_id );

		if ( $user->user_pass === $old_user_data->user_pass ) {
			return;
		}

		$this->service->handle_password_updated( $user );
	}

	/**
	 * Provide data to the frontend via localized script.
	 *
	 * @param  array $data  Data collection is ready to passed.
	 *
	 * @return array Modified data array with added this controller data.
	 */
	public function script_data( array $data ): array {
		$data['password_reset'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			$response = array(
				'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
				'auto_close' => true,
			);

			return new Response( true, array_merge( $response, $this->data_frontend() ) );
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
		);
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function toggle_reset( Request $request ) {
		$response = array();
		$data     = $request->get_data_by_model( $this->model );
		if ( isset( $data['expire_force'] ) && true === $data['expire_force'] ) {
			$data['force_time'] = time();
			$response           = array(
				'message' => esc_html__(
					'Selected user roles are required to reset their password upon next login.',
					'wpdef'
				),
			);
		} else {
			$response['message'] = esc_html__( 'Force Reset Password has been disabled.', 'wpdef' );
		}
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response( true, array_merge( $response, $this->data_frontend() ) );
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
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
	public function remove_data(): void {
		delete_metadata( 'user', null, 'wd_last_password_change', null, true );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$model = $this->get_model();

		return array_merge(
			array(
				'model'           => $model->export(),
				'all_roles'       => wp_list_pluck( get_editable_roles(), 'name' ),
				'reset_last'      => empty( $model->force_time ) ? '' : $this->format_date_time( $model->force_time ),
				'default_message' => $this->default_msg,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 *
	 * @throws Exception If table is not defined.
	 */
	public function import_data( array $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
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
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings() {
		return array();
	}
}