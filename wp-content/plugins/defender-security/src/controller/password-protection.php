<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;

/**
 * Class Password_Protection
 *
 * @package WP_Defender\Controller
 */
class Password_Protection extends Controller2 {
	/**
	 * Use for cache.
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
		$this->model       = wd_di()->get( \WP_Defender\Model\Setting\Password_Protection::class );
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
			add_action( 'wp_authenticate_user', array( $this, 'handle_login_password' ), 100, 2 );
			add_action( 'validate_password_reset', array( $this, 'handle_reset_check_password' ), 100, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'handle_profile_update_password' ), 1, 3 );
		}
	}

	/**
	 * Update 'network_site_url' if:
	 * not empty URL path,
	 * it's link to reset password.
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
	 * Handle user login password.
	 * If pwned password found during login then redirect to reset password page to reset password.
	 *
	 * @param \WP_User|\WP_Error $user     WP_User object or WP_Error.
	 * @param string             $password Password plain string.
	 *
	 * @return \WP_User|\WP_Error Return user object or error object.
	 */
	public function handle_login_password( $user, $password ) {
		$this->service->do_weak_reset( $user, $password );

		return $user;
	}

	/**
	 * Handle password update on password reset.
	 *
	 * @param \WP_Error $errors
	 *
	 * @return mixed
	 */
	public function handle_reset_check_password( $errors, $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}

		if ( ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return;
		}

		// Check if display_pwned_password_warning cookie enabled then show warning message on reset password page.
		if ( isset( $_COOKIE['display_pwned_password_warning'] ) ) {
			$message = empty( $this->model->pwned_actions['force_change_message'] )
				? $this->default_msg
				: $this->model->pwned_actions['force_change_message'];
			$errors->add( 'defender_password_protection', $message );
			// Remove the one time cookie notice once it's displayed.
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
	 * Handle password update on new user registration and user profile update.
	 *
	 * @param \WP_Error $errors
	 * @param string    $update
	 * @param \WP_User  $user
	 *
	 * @return \WP_Error|void
	 */
	public function handle_profile_update_password( $errors, $update, $user ) {
		if ( $errors->get_error_message( 'pass' ) ||
			is_wp_error( $user ) ||
			! isset( $user->user_pass )
		) {
			return;
		}

		// When updating the profile check if user's role preference is enabled.
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
	 * @param array $data
	 *
	 * @return array
	 */
	public function script_data( $data ) {
		$data['password_protection'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings.
	 * @param Request $request
	 *
	 * @return Response
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
			Config_Hub_Helper::set_clear_active_flag();

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

	public function export_strings() {

		return array(
			$this->model->is_active() ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( $config, $is_pro ) {
		if ( empty( $config['enabled'] ) || empty( $config['user_roles'] ) ) {
			return array( __( 'Inactive', 'wpdef' ) );
		}

		return array(
			$config['enabled'] && count( $config['user_roles'] ) > 0
				? __( 'Active', 'wpdef' )
				: __( 'Inactive', 'wpdef' )
		);
	}
}
