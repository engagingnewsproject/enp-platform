<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Traits\Formats;

/**
 * Class Password_Reset
 *
 * @package WP_Defender\Controller
 */
class Password_Reset extends \WP_Defender\Controller2 {
	use Formats;

	/**
	 * Use for cache
	 *
	 * @var \WP_Defender\Model\Setting\Password_Reset
	 */
	protected $model;

	/**
	 * @var \WP_Defender\Component\Password_Protection
	 */
	protected $service;

	/**
	 * @var string
	 */
	public $default_msg;

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
				add_filter( 'network_site_url', array( &$this, 'filter_site_url' ), 100, 2 );
			}
			add_action( 'validate_password_reset', array( $this, 'handle_reset_check_password' ), 10, 2 );
			add_action( 'profile_update', array( $this, 'handle_update_user' ), 10, 2 );
			add_action( 'password_reset', array( $this, 'handle_password_reset' ), 10 );
			add_action( 'wp_authenticate_user', array( $this, 'handle_login_password' ), 999, 2 );
			// No use 'user_profile_update_errors' because there aren't checks for password resetting for logged user in.
		}
	}

	/**
	 * Update 'network_site_url' if:
	 * not empty URL path,
	 * it's link to reset password
	 * @param string $url
	 * @param string $path
	 *
	 * @return string
	*/
	public function filter_site_url( $url, $path ) {
		if ( $path && is_string( $path )
			&& ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'rp', 'resetpass' ), true )
			&& false !== stristr( $url, 'wp-login.php' )
		) {
			return get_option( 'siteurl' ) . '/' . ltrim( $path, '/' );
		}
		return $url;
	}

	/**
	 * @return \WP_Defender\Model\Setting\Password_Reset
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Password_Reset();
	}

	/**
	 * @param \WP_User|\WP_Error $user     WP_User object or WP_Error.
	 * @param string             $password Password plain string.
	 *
	 * @return \WP_User|\WP_Error Return user object or error object.
	 */
	public function handle_login_password( $user, $password ) {
		$this->service->do_force_reset( $user, $password );

		return $user;
	}

	/**
	 * Handle password update on password reset.
	 *
	 * @param \WP_Error          $errors
	 * @param \WP_Error|\WP_User $user
	 *
	 * @return void|\WP_Error
	 */
	public function handle_reset_check_password( $errors, $user ) {
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
			$this->service->remove_cookie_notice(
				'display_reset_password_warning',
				true,
				time() - MINUTE_IN_SECONDS
			);

			return $errors;
		}

		$login_password = $this->service->get_submitted_password();
		if (
			! empty( $user->ID )
			&& ! empty( $login_password )
			&& wp_check_password( $login_password, get_userdata( $user->ID )->user_pass, $user->ID )
		) {
			$message = wp_kses(
				__( 'This password has been used already. Please choose a different one.', 'wpdef' ),
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
	 * @param \WP_User $user
	 */
	public function handle_password_reset( $user ) {
		$this->service->handle_password_updated( $user );
	}

	/**
	 * Update password data when a user object is set or updated.
	 *
	 * @param int    $user_id
	 * @param object $old_user_data
	 */
	public function handle_update_user( $user_id, $old_user_data ) {

		$user = get_userdata( $user_id );

		if ( $user->user_pass === $old_user_data->user_pass ) {
			return;
		}

		$this->service->handle_password_updated( $user );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function script_data( $data ) {
		$data['password_reset'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings.
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			$response = array(
				'message' => __( 'Your settings have been updated.', 'wpdef' ),
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
	 * @defender_route
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function toggle_reset( Request $request ) {
		$data = $request->get_data_by_model( $this->model );
		if ( isset( $data['expire_force'] ) && true === $data['expire_force'] ) {
			$data['force_time'] = time();
			$response           = array(
				'message' => __( 'Selected user roles are required to reset their password upon next login.', 'wpdef' ),
			);
		} else {
			$response['message'] = __( 'Passwords reset is no longer required.', 'wpdef' );
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

	public function remove_settings() {}

	public function remove_data() {
		delete_metadata( 'user', null, 'wd_last_password_change', null, true );
	}

	public function data_frontend() {
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

	public function import_data( $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	public function to_array() {}

	public function export_strings() {}
}
