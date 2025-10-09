<?php
/**
 * Handles password protection.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_User;
use WP_Error;
use stdClass;
use Countable;
use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Integrations\Woocommerce;
use WP_Defender\Component\Config\Config_Hub_Helper;

/**
 * Handles password protection.
 */
class Password_Protection extends Event {
	/**
	 * The model for handling the data.
	 *
	 * @var \WP_Defender\Model\Setting\Password_Protection
	 */
	public $model;

	/**
	 * Service for handling logic.
	 *
	 * @var \WP_Defender\Component\Password_Protection
	 */
	public $service;

	/**
	 *  Default message.
	 *
	 * @var string
	 */
	public $default_msg;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
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
				add_filter( 'network_site_url', array( $this, 'filter_site_url' ), 100, 2 );
			}
			add_action( 'wp_authenticate_user', array( $this, 'handle_login_password' ), 100, 2 );
			add_action( 'validate_password_reset', array( $this, 'handle_reset_check_password' ), 100, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'handle_profile_update_password' ), 1, 3 );
			if ( wd_di()->get( Woocommerce::class )->is_wc_login_context() ) {
				add_filter( 'woocommerce_reset_password_message', array( $this->service, 'add_woocommerce_error_message' ) );
			}
		}
	}

	/**
	 * Filters the site URL if the path is not empty and it is a link to reset password.
	 *
	 * @param  string $url  The original site URL.
	 * @param  string $path  The URL path.
	 *
	 * @return string The modified site URL if the conditions are met, otherwise the original URL.
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
	 * Handle user login password.
	 * If pwned password found during login then redirect to reset password page to reset password.
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
		$this->service->do_weak_reset( $user, $password );

		return $user;
	}

	/**
	 * Handle password update on password reset.
	 *
	 * @param  WP_Error         $errors  Error object.
	 * @param  WP_User|WP_Error $user  WP_User object or WP_Error.
	 *
	 * @return void|WP_Error
	 */
	public function handle_reset_check_password( WP_Error $errors, $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}

		if ( ! $this->service->is_enabled_by_user_role( $user, $this->model->user_roles ) ) {
			return;
		}

		// Check if display_pwned_password_warning cookie enabled then show warning message on reset password page.
		if ( ! defender_get_data_from_request( 'wc_reset_password', 'p' ) && isset( $_COOKIE['display_pwned_password_warning'] ) ) {
			$message = empty( $this->model->pwned_actions['force_change_message'] )
				? $this->default_msg
				: $this->model->pwned_actions['force_change_message'];
			$errors->add( 'defender_password_protection', $message );
			// Remove the one time cookie notice once it's displayed.
			$this->service->remove_cookie_notice( 'display_pwned_password_warning' );
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
	 * @param  WP_Error $errors  The errors object to add error messages to.
	 * @param  bool     $update  Whether the profile is being updated.
	 * @param  stdClass $user  The user object being updated.
	 *
	 * @return WP_Error The updated errors object.
	 */
	public function handle_profile_update_password( WP_Error $errors, bool $update, stdClass $user ) {
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
	 * Get model.
	 *
	 * @return \WP_Defender\Model\Setting\Password_Protection
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Password_Protection();
	}

	/**
	 * Provide data to the frontend via localized script.
	 *
	 * @param  array $data  Data collection is ready to passed.
	 *
	 * @return array Modified data array with added this controller data.
	 */
	public function script_data( array $data ): array {
		$data['password_protection'] = $this->data_frontend();

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
		$model_data = $request->get_data_by_model( $this->model );
		// Additional security layer because 'message' is nested model property of parent 'pwned_actions'.
		$model_data['pwned_actions']['force_change_message'] = sanitize_textarea_field(
			$model_data['pwned_actions']['force_change_message']
		);
		$this->model->import( $model_data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			$response = array(
				'auto_close' => true,
			);
			if ( $this->model->enabled && empty( $this->model->user_roles ) ) {
				// we need to control this message in front.
				$response['warning'] = sprintf(
					/* translators: 1. Open tag. 2. Close tag. */
					esc_html__( 'You need to check at least one of the %1$sPwned checks preferences below%2$s and save your settings to enable Password Protection.', 'wpdef' ),
					'<b>',
					'</b>'
				);

				return new Response( true, array_merge( $response, $this->data_frontend() ) );
			}

			if ( $this->maybe_track() ) {
				$prev_data = $this->get_model()->get_old_settings();

				if ( ! empty( $prev_data ) ) {
					if ( $this->model->enabled && ! $prev_data['enabled'] ) {
						$need_track = true;
						$event      = 'def_feature_activated';
					} elseif ( ! $this->model->enabled && $prev_data['enabled'] ) {
						$need_track = true;
						$event      = 'def_feature_deactivated';
					} else {
						$need_track = false;
					}

					if ( $need_track ) {
						$data = array(
							'Feature'        => 'Pwned Passwords',
							'Triggered From' => 'Feature page',
						);
						$this->track_feature( $event, $data );
					}
				}
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
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
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

	/**
	 * Provides data for the dashboard widget.
	 *
	 * @return array An array of dashboard widget data.
	 */
	public function dashboard_widget(): array {
		return array( 'model' => $this->get_model()->export() );
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
		if ( isset( $data['custom_message'] ) ) {
			$adapted_data['force_change_message'] = $data['custom_message'];
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
			// Upgrade for old versions.
			$data  = $this->adapt_data( $data );
			$model = $this->get_model();
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
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
	public function export_strings(): array {
		return array(
			$this->model->is_active() ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Generates configuration strings based on the provided configuration and whether the product is a pro version.
	 *
	 * @param  mixed $config  The configuration data.
	 * @param  bool  $is_pro  Indicates if the product is a pro version.
	 *
	 * @return array Returns an array of configuration strings.
	 */
	public function config_strings( $config, bool $is_pro ): array {
		if ( empty( $config['enabled'] ) || empty( $config['user_roles'] ) ) {
			return array( esc_html__( 'Inactive', 'wpdef' ) );
		}

		return array(
			$config['enabled'] && ( is_array( $config['user_roles'] ) || $config['user_roles'] instanceof Countable ? count( $config['user_roles'] ) : 0 ) > 0
				? esc_html__( 'Active', 'wpdef' )
				: esc_html__( 'Inactive', 'wpdef' ),
		);
	}
}