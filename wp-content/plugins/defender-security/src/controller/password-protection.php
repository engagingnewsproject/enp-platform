<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Controller2;

/**
 * Class Password_Protection
 *
 * @package WP_Defender\Controller
 */
class Password_Protection extends Controller2 {
	/**
	 * Use for cache
	 *
	 * @var \WP_Defender\Model\Setting\Password_Protection
	 */
	public $model;

	/**
	 * @var \WP_Defender\Component\Password_Protection
	 */
	public $service;

	/**
	 * @var string
	 */
	public $default_msg;

	public function __construct() {
		add_action( 'wp_authenticate_user', array( $this, 'handle_login_password' ), 100, 2 );
		add_action( 'validate_password_reset', array( $this, 'handle_reset_check_password' ), 100, 2 );
		add_action( 'user_profile_update_errors', array( $this, 'handle_profile_update_password' ), 0, 3 );
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );
		$this->model       = wd_di()->get( \WP_Defender\Model\Setting\Password_Protection::class );
		$this->service     = wd_di()->get( \WP_Defender\Component\Password_Protection::class );
		$this->default_msg = __( 'You are required to change your password because the password you are using exists on database breach records.', 'wpdef' );
		$this->register_routes();
	}

	/**
	 * Handle user login password
	 * If pwned password found during login then redirect to reset password page to reset password
	 *
	 * @param WP_User $user
	 * @param string $password
	 *
	 * @return WP_User $user;
	 */
	public function handle_login_password( $user, $password ) {
		if ( is_wp_error( $user ) || ! $this->model->is_active() ) {
			return $user;
		}

		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return $user;
		}

		if ( ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return $user;
		}

		if ( $password ) {
			$is_pwned = $this->service->check_pwned_password( $password );
			if ( is_wp_error( $is_pwned ) ) {
				return $user;
			}
			if ( $is_pwned ) {
				// Set cookie to check and display the warning notice on reset password page
				$this->service->set_cookie_notice(
					'display_pwned_password_warning',
					true,
					time() + MINUTE_IN_SECONDS * 2
				);
				// Get the reset password URL
				$url = $this->service->get_reset_password_redirect_url( $user );
				// Redirect to the reset password page
				$this->service->reset_password_redirect( $url );
			}
		}

		return $user;
	}

	/**
	 * Handle password update on password reset
	 *
	 * @param \WP_Error $errors
	 * @return \WP_Error|\WP_User $user
	 *
	 * @return \WP_Error
	 */
	public function handle_reset_check_password( $errors, $user ) {
		if ( is_wp_error( $user ) || ! $this->model->is_active() ) {
			return;
		}

		if ( ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return;
		}

		// Check if display_pwned_password_warning cookie enabled then show warning message on reset password page
		if ( isset( $_COOKIE['display_pwned_password_warning'] ) ) {
			$message = empty( $this->model->pwned_actions['force_change_message'] )
				? $this->default_msg
				: $this->model->pwned_actions['force_change_message'];
			$errors->add( 'defender_password_protection', $message );
			// remove the one time cookie notice once it's displayed
			$this->service->remove_cookie_notice( 'display_pwned_password_warning', true, time() - MINUTE_IN_SECONDS );
		}

		$login_password = $this->service->get_submitted_password();
		if ( $login_password ) {
			$is_pwned = $this->service->check_pwned_password( $login_password );
			if ( is_wp_error( $is_pwned ) ) {
				return $errors;
			}

			if ( $is_pwned ) {
				$message = empty( $this->model->pwned_actions['force_change_message'] )
					? $this->default_msg
					: $this->model->pwned_actions['force_change_message'];
				$errors->add( 'defender_password_protection', $message );
			}
		}

		return $errors;
	}

	/**
	 * Handle password update on new user registration and user profile update
	 *
	 * @param \WP_Error $errors
	 * @param string $update
	 * @param \WP_User $user
	 *
	 * @return \WP_Error|void
	 */
	public function handle_profile_update_password( $errors, $update, $user ) {
		if ( $errors->get_error_message( 'pass' ) ||
			is_wp_error( $user ) ||
			! isset( $user->user_pass ) ||
			! $this->model->is_active()
		) {
			return;
		}

		// When updating the profile check if user's role preference is enabled
		if ( $update && ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return;
		}

		$login_password = $this->service->get_submitted_password();
		if ( $login_password ) {
			$is_pwned = $this->service->check_pwned_password( $login_password );
			if ( is_wp_error( $is_pwned ) ) {
				return $errors;
			}

			if ( $is_pwned ) {
				$message = empty( $this->model->pwned_actions['force_change_message'] )
					? $this->default_msg
					: $this->model->pwned_actions['force_change_message'];
				$errors->add( 'defender_password_protection', $message );
			}
		}

		return $errors;
	}

	/**
	 * @return \WP_Defender\Model\Setting\Password_Protection
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Password_Protection();
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function script_data( $data ) {
		$data['password_protection'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$model_data = $request->get_data_by_model( $this->model );
		$data       = $request->get_data(
			array(
				'intention' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$this->model->import( $model_data );
		if ( $this->model->validate() ) {
			$this->model->save();
			//Todo: clear active config
			$response = array(
				'message' => __( 'Your settings have been updated.', 'wpdef' ),
			);
			if ( $data && 'save_settings' === $data['intention'] && ! $this->model->is_active() ) {
				$response['type_notice'] = 'warning';
				$response['message']     = __( 'You need to check at least one of the <b>Pwned checks preferences below</b> and save your settings to enable Password Protection.', 'wpdef' );
			}

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

	public function remove_data() {}

	public function data_frontend() {
		$model = $this->get_model();

		return array_merge(
			array(
				'is_active'       => $model->is_active(),
				'model'           => $model->export(),
				'all_roles'       => wp_list_pluck( get_editable_roles(), 'name' ),
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
