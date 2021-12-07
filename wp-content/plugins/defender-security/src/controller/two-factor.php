<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Helper\Array_Cache;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Setting\Two_Fa;
use Calotes\Component\Response;

class Two_Factor extends Controller2 {
	public $slug = 'wdf-2fa';

	/**
	 * @var Two_Fa
	 */
	protected $model;

	/**
	 * @var mixed|\WP_Defender\Component\Two_Fa
	 */
	protected $service;

	/**
	 * @var array
	 */
	protected $compatibility_notices = array();

	/**
	 * @var \WP_Defender\Component\Password_Protection
	 */
	protected $password_protection_service;

	public function __construct() {
		$this->register_page(
			esc_html__( '2FA', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->register_routes();
		$this->service = wd_di()->get( \WP_Defender\Component\Two_Fa::class );
		$this->model   = wd_di()->get( Two_Fa::class );

		$this->password_protection_service = wd_di()->get( \WP_Defender\Component\Password_Protection::class );

		add_action( 'update_option_jetpack_active_modules', array( &$this, 'listen_for_jetpack_option', 10, 3 ) );

		if ( $this->model->enabled ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$is_jetpack_sso = $this->service->is_jetpack_sso();
			$is_tml         = $this->service->is_tml();
			add_action( 'pre_get_users', array( &$this, 'filter_users_by_2fa' ) );
			add_action( 'show_user_profile', array( &$this, 'show_user_profile' ) );
			add_action( 'profile_update', array( &$this, 'profile_update' ) );

			if ( ! defined( 'DOING_AJAX' ) && ! $is_jetpack_sso && ! $is_tml ) {
				add_filter( 'wp_authenticate_user', array( &$this, 'maybe_show_otp_form' ), 9, 2 );
				add_action( 'set_logged_in_cookie', array( &$this, 'store_session_key' ) );
				add_action( 'login_form_defender-verify-otp', array( &$this, 'verify_otp_login_time' ) );
			} else {
				if ( $is_jetpack_sso ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Jetpack's Wordpress.com Log In feature. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
				if ( $is_tml ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Theme my login. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
			}
			// Force auth redirect.
			add_action( 'current_screen', array( &$this, 'maybe_redirect_to_show_2fa_enabler' ), 1 );
			if ( is_multisite() ) {
				add_filter( 'wpmu_users_columns', array( &$this, 'alter_users_table' ) );
			} else {
				add_filter( 'manage_users_columns', array( &$this, 'alter_users_table' ) );
			}
			add_filter( 'manage_users_custom_column', array( &$this, 'alter_user_table_row' ), 10, 3 );
			add_filter( 'ms_shortcode_ajax_login', array( &$this, 'm2_no_ajax' ) );
			//Todo: add the verify for filter 'login_redirect'.

			$this->woocommerce_hooks();
		}
	}

	/**
	 * We have some feature conflict with jetpack, so listen to know when Defender can on.
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option
	 */
	public function listen_for_jetpack_option( $old_value, $value, $option ) {
		if ( false !== array_search( 'sso', $value ) ) {
			$this->model->mark_as_conflict( 'jetpack/jetpack.php' );
		} else {
			$this->model->mark_as_un_conflict( 'jetpack/jetpack.php' );
		}
	}

	/**
	 * If force redirect enabled, then we should check and redirect to profile page until the 2FA enabled.
	 */
	public function maybe_redirect_to_show_2fa_enabler() {
		$user = wp_get_current_user();
		if ( ! is_object( $user ) ) {
			return;
		}

		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return;
		}

		if ( false === $this->model->force_auth ) {
			return;
		}

		if ( $this->service->is_user_enabled_otp( $user->ID ) ) {
			return;
		}

		if ( ! $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( 'profile' !== $screen->id ) {
			wp_safe_redirect( admin_url( 'profile.php' ) . '#defender-security' );
			exit;
		}
	}

	/**
	 * Add a column in the users table, column will be last.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function alter_users_table( $columns ) {
		$columns = array_slice( $columns, 0, count( $columns ) - 1 ) + array(
			'defender-two-fa' => __( 'Two Factor', 'wpdef' ),
		) + array_slice( $columns, count( $columns ) - 1 );

		return $columns;
	}

	/**
	 * @param $val
	 * @param $column_name
	 * @param $user_id
	 */
	public function alter_user_table_row( $val, $column_name, $user_id ) {
		if ( 'defender-two-fa' !== $column_name ) {
			return $val;
		}
		$is_on = get_user_meta( $user_id, 'defenderAuthOn', true );
		if ( $is_on ) {
			return '<span class="def-oval oval-green"></span>';
		}

		return '<span class="def-oval"></span>';
	}

	/**
	 * Retrieve the backup code if lost phone.
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function send_backup_code( Request $request ) {
		$data  = $request->get_data();
		$token = $data['token'];
		$ret   = $this->service->send_otp_to_email( $token );
		if ( false === $ret ) {
			return new Response(
				false,
				array(
					'message' => __( 'Please try again', 'wpdef' ),
				)
			);
		}

		if ( is_wp_error( $ret ) ) {
			return new Response(
				false,
				array(
					'message' => $ret->get_error_message(),
				)
			);
		}

		return new Response(
			true,
			array(
				'message' => __( 'Your code has been sent to your email.', 'wpdef' ),
			)
		);
	}

	/**
	 * Verify the OTP after user login successful.
	 *
	 * @return void
	 */
	public function verify_otp_login_time() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'verify_otp' ) ) {
			return;
		}

		$params = array();
		// Find the user first.
		$token = HTTP::post( 'login_token' );
		$query = new \WP_User_Query(
			array(
				'blog_id'    => 0,
				'meta_key'   => 'defender_two_fa_token',
				'meta_value' => $token,
			)
		);

		if ( $query->get_total() === 0 ) {
			$params['error'] = new \WP_Error( 'opt_fail', __( 'Invalid request', 'wpdef' ) );
			$this->render_otp_screen( $params );
		}

		$user            = $query->get_results()[0];
		$params['token'] = uniqid( 'two_fa' );
		update_user_meta( $user->ID, 'defender_two_fa_token', $params['token'] );
		$otp      = HTTP::post( 'otp' );
		$redirect = HTTP::post( 'redirect_to', admin_url() );
		if ( empty( $otp ) ) {
			$params['error'] = new \WP_Error(
				'opt_fail',
				__( 'Whoops, the passcode you entered was incorrect or expired.', 'wpdef' )
			);
			$this->render_otp_screen( $params );
		}
		$handler = new \WP_Defender\Component\Two_Fa();
		$ret     = $handler->verify_otp( $otp, $user );
		if ( ! $ret ) {
			// Perhaps backup email?
			$backup_code = get_user_meta( $user->ID, 'defenderBackupCode', true );
			if ( $backup_code && $backup_code['code'] === $otp && strtotime(
				'+3 minutes',
				$backup_code['time']
			) > time() ) {
				$ret = true;
				delete_user_meta( $user->ID, 'defenderBackupCode' );
			}
		}

		if ( $ret ) {
			// Clean token.
			delete_user_meta( $user->ID, 'defender_two_fa_token' );

			$password = HTTP::post( 'password' );

			$is_weak_password = $this->password_protection_service->is_weak_password( $user, $password );
			if ( true === $is_weak_password ) {
				$this->password_protection_service->do_weak_reset( $user, $password );
			} elseif ( $this->password_protection_service->is_force_reset( $user ) ) {
				$this->password_protection_service->do_force_reset( $user, $password );
			} else {
				$user_id = $user->ID;

				// Set active user.
				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id, true );

				/**
				 * Fires after successful login via 2fa.
				 *
				 * @since 2.6.1
				 *
				 * @param int $user_id
				 */
				do_action( 'wpmu_2fa_login', $user_id );

				if ( isset( $_REQUEST['interim-login'] ) ) {
					$params['interim_login'] = 'success';
					$params['message']       = '<p class="message">' . __( 'You have logged in successfully.', 'wpdef' ) . '</p>';
					$this->render_otp_screen( $params );
					exit;
				} else {
					$redirect = apply_filters(
						'login_redirect',
						$redirect,
						$this->redirect_url(),
						$user
					);
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}

		$params['error'] = new \WP_Error(
			'opt_fail',
			__( 'Whoops, the passcode you entered was incorrect or expired.', 'wpdef' )
		);
		$this->render_otp_screen( $params );
	}

	/**
	 * Render otp form.
	 *
	 * @param WP_User $user     WP_User object of the logged-in user.
	 * @param string  $password Plain password string.
	 */
	public function maybe_show_otp_form( $user, $password ) {
		if (
			! empty( $user ) &&
			! empty( $password ) &&
			$user instanceof \WP_User &&
			wp_check_password( $password, $user->data->user_pass, $user->ID ) &&
			$this->service->is_user_enable_otp( $user )
		) {
			$cookie = Array_Cache::get( 'auth_cookie', '2fa' );
			if ( null !== $cookie ) {
				// Clear all session data if any.
				$session = \WP_Session_Tokens::get_instance( $user->ID );
				$session->destroy( $cookie['token'] );
			}
			// Prevent user to login, and show otp screen.
			wp_clear_auth_cookie();
			// All goods, we'll need to create a unique token to mark this user.
			$params['token'] = uniqid( 'two_fa' );
			update_user_meta( $user->ID, 'defender_two_fa_token', $params['token'] );
			$params['password'] = $password;
			$this->render_otp_screen( $params );
		}

		return $user;
	}

	/**
	 * Render the OTP screen after login successful.
	 *
	 * @param $user
	 * @param array $params
	 */
	private function render_otp_screen( $params = array() ) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'defender-otp-screen', defender_asset_url( '/assets/css/otp.css' ) );
		$params['redirect_to'] = $this->redirect_url();
		if ( ! isset( $params['error'] ) ) {
			$params['error'] = null;
		}
		// If this goes here then the current user is ok, need to show the 2 auth.
		$this->attach_behavior( 'wpmudev', WPMUDEV::class );
		$custom_graphic = '';
		$settings       = new Two_Fa();
		if ( $this->is_pro() && $settings->custom_graphic && '' !== $settings->custom_graphic_url ) {
				$custom_graphic = $settings->custom_graphic_url;
		}
		$this->detach_behavior( 'wpmudev' );
		$params['custom_graphic'] = $custom_graphic;
		$params['lost_phone']     = $settings->lost_phone;

		$routes = $this->dump_routes_and_nonces();

		$params['lost_phone_url'] = admin_url( 'admin-ajax.php' ) . sprintf(
			'?action=wp_defender/v1/hub/&route=%s&_def_nonce=%s',
			$routes['routes']['send_backup_code'],
			$routes['nonces']['send_backup_code']
		);
		$params['otp_text']       = $settings->app_text;
		$this->render_partial( 'two-fa/otp', $params );
		exit;
	}

	/**
	 * Cache the auth cookie.
	 *
	 * @param $cookie
	 */
	public function store_session_key( $cookie ) {
		// Clear login cookie to ensure nonce consistency.
		if ( ! is_user_logged_in() && isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		}

		$cookie = wp_parse_auth_cookie( $cookie, 'logged_in' );
		Array_Cache::set( 'auth_cookie', $cookie, '2fa' );
	}


	/**
	 * Disable a user via 2fa.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function disable_2fa() {
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'defenderAuthOn', 0 );
		delete_user_meta( $user_id, 'defenderAuthSecret' );

		return new Response( true, array() );
	}

	/**
	 * Verify the OTP and enable 2fa, use in profile.php.
	 * @defender_route
	 * @is_public
	 */
	public function verify_otp_for_enabling( Request $request ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$data = $request->get_data();
		$otp  = isset( $data['otp'] ) ? $data['otp'] : false;
		if ( false === $otp || strlen( $otp ) < 6 ) {
			return new Response(
				false,
				array(
					'message' => __( 'Please input a valid OTP code', 'wpdef' ),
				)
			);
		}
		if ( $this->service->verify_otp( $otp ) ) {
			$user_id = get_current_user_id();
			$this->service->enable_otp( $user_id );

			return new Response( true, array() );
		} else {
			return new Response(
				false,
				array(
					'message' => __( 'Your OTP code is incorrect. Please try again.', 'wpdef' ),
				)
			);
		}
	}

	/**
	 * @param $user_id
	 */
	public function profile_update( $user_id ) {
		$email = HTTP::post( 'def_2fa_backup_email' );
		if ( $email && get_current_user_id() === $user_id ) {
			update_user_meta( $user_id, 'defenderAuthEmail', $email );
		}
	}

	/**
	 * Check if DEFENDER_DEBUG is enabled for the route.
	 * @param string $route
	 *
	 * @return string
	 */
	private function check_route( $route ) {

		return defined( 'DEFENDER_DEBUG' ) && DEFENDER_DEBUG ? wp_slash( $route ) : $route;
	}

	/**
	 * A simple filter to show activate 2fa screen on profile page.
	 *
	 * @return void
	 */
	public function show_user_profile() {
		$user = wp_get_current_user();
		if ( empty( array_intersect( $user->roles, $this->model->user_roles ) ) ) {
			return;
		}
		$forced_auth = ! empty( array_intersect( $user->roles, $this->model->force_auth_roles ) );
		$is_on       = get_user_meta( $user->ID, 'defenderAuthOn', true );
		$routes      = $this->dump_routes_and_nonces();
		if ( $is_on ) {
			$url          = admin_url( 'admin-ajax.php' ) . sprintf(
				'?action=wp_defender/v1/hub/&route=%s&_def_nonce=%s',
				$this->check_route( $routes['routes']['disable_2fa'] ),
				$routes['nonces']['disable_2fa']
			);
			$backup_email = $this->service->get_backup_email();
			$this->render_partial(
				'two-fa/enabled',
				array(
					'backup_email' => $backup_email,
					'url'          => $url,
				)
			);
		} else {
			$url            = admin_url( 'admin-ajax.php' ) . sprintf(
				'?action=wp_defender/v1/hub/&route=%s&_def_nonce=%s',
				$this->check_route( $routes['routes']['verify_otp_for_enabling'] ),
				$routes['nonces']['verify_otp_for_enabling']
			);
			$default_values = $this->model->get_default_values();
			$this->render_partial(
				'two-fa/disabled',
				array(
					'is_force_auth'      => $forced_auth && $this->model->force_auth,
					'force_auth_message' => $this->model->force_auth_mess,
					'url'                => $url,
					'default_message'    => $default_values['message'],
				)
			);
		}
	}

	/**
	 * Save settings.
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$model = $this->model;
		$data  = $request->get_data();
		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message' => __( 'Your settings have been updated.', 'wpdef' ),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $model->get_formatted_errors(),
			)
		);
	}

	/**
	 * @throws \ReflectionException
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_enqueue_script( 'clipboard' );
		wp_enqueue_media();
		wp_localize_script( 'def-2fa', 'two_fa', $this->data_frontend() );
		wp_enqueue_script( 'def-2fa' );
		$this->enqueue_main_assets();
	}

	/**
	 * Send test email, use in settings screen.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function send_test_email( Request $request ) {
		$data = $request->get_data(
			array(
				'email_subject' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'email_sender'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'email_body'    => array(
					'type'     => 'string',
					'sanitize' => 'wp_kses_post',
				),
			)
		);

		$subject = $data['email_subject'];
		$sender  = $data['email_sender'];
		$body    = $data['email_body'];

		$params = array(
			'passcode'     => '[a-sample-passcode]',
			'display_name' => $this->get_user_display(),
		);
		foreach ( $params as $key => $param ) {
			$body = str_replace( "{{{$key}}}", $param, $body );
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( $sender ) {
			$from_email = get_bloginfo( 'admin_email' );
			$headers[]  = sprintf( 'From: %s <%s>', $sender, $from_email );
		}

		$send_mail = wp_mail( $this->service->get_backup_email(), $subject, nl2br( $body ), $headers );
		if ( $send_mail ) {
			return new Response(
				true,
				array(
					'message' => __( 'Test email has been sent to your email.', 'wpdef' ),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => __( 'Test email failed.', 'wpdef' ),
				)
			);
		}
	}

	/**
	 * @return array
	 */
	public function to_array() {
		$settings                = new Two_Fa();
		list( $routes, $nonces ) = Route::export_routes( 'two_fa' );

		return array(
			'enabled'   => $settings->enabled,
			'useable'   => $settings->enabled && count( $settings->user_roles ),
			'nonces'    => $nonces,
			'endpoints' => $routes,
		);
	}

	public function main_view() {
		$this->render( 'main' );
	}

	public function remove_settings() {
		( new Two_Fa() )->delete();
	}

	/*
	 * Remove all users meta. Keys need to remove:
	 * defenderAuthEmail,
	 * defenderAuthOn,
	 * defenderForceAuth,
	 * defenderBackupCode,
	 * defenderAuthSecret.
	 */
	public function remove_data() {
		global $wpdb;
		$sql = "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('defenderAuthEmail','defenderAuthOn','defenderForceAuth','defenderBackupCode','defenderAuthSecret');";
		$wpdb->query( $sql );
	}

	/**
	 * Filter users by 2FA option.
	 */
	public function filter_users_by_2fa( $query ) {
		global $pagenow;

		if ( is_admin()
			&& 'users.php' === $pagenow
			&& isset( $_GET['wpdef_two_fa'] )
			&& in_array( $_GET['wpdef_two_fa'], array( 'enabled', 'disabled' ), true )
		) {
			$two_fa = sanitize_text_field( $_GET['wpdef_two_fa'] );

			$query->set( 'meta_key', 'defenderAuthOn' );
			if ( 'enabled' !== $two_fa ) {
				$query->set( 'meta_compare', 'NOT EXISTS' );
			} else {
				$query->set(
					'meta_query',
					array(
						array(
							'key'   => 'defenderAuthOn',
							'value' => 1,
						),
					)
				);
			}
		}
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {

		return array_merge(
			array(
				'model'     => $this->model->export(),
				'all_roles' => wp_list_pluck( get_editable_roles(), 'name' ),
				'count'     => $this->service->count_2fa_enabled(),
				'notices'   => $this->compatibility_notices,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * @param $data array
	 */
	public function import_data( $data ) {
		$model = new Two_Fa();

		$model->import( $data );
		/**
		 * Sometime, the custom image broken when import, when that happen, we will fallback
		 * into default image.
		 */
		$model->custom_graphic_url = $this->service->get_custom_graphic_url( $model->custom_graphic_url );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		$settings = new Two_Fa();

		return array(
			$settings->enabled ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( $config, $is_pro ) {

		return array(
			$config['enabled'] ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Stop ajax login on membership 2.
	 *
	 * @return bool
	 */
	public function m2_no_ajax() {
		return false;
	}

	/**
	 * WooCommerce prevents any user who cannot 'edit_posts' (subscribers, customers etc.) from accessing admin.
	 * Here we are disabling WooCommerce default behavior, If force 2FA is enabled.
	 *
	 * @param bool $prevent Prevent admin access.
	 */
	public function handle_woocommerce_prevent_admin_access( $prevent ) {
		$user = wp_get_current_user();
		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return $prevent;
		}

		if ( false === $this->model->force_auth ) {
			return $prevent;
		}

		if ( $this->service->is_user_enabled_otp( $user->ID ) ) {
			return $prevent;
		}

		if ( $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return false;
		}

		return $prevent;
	}

	/**
	 * WooCommerce specific hooks.
	 *
	 * @return void
	 */
	private function woocommerce_hooks() {
		// This filter added only for disable WooCommerce default behavior.
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'handle_woocommerce_prevent_admin_access' ), 10, 1 );

		// Handle WooCommerce MyAccount page login redirect.
		add_filter( 'woocommerce_login_redirect', array( $this, 'handle_woocommerce_login_redirect' ), 10, 2 );

		add_action( 'woocommerce_login_form_end', array( $this, 'add_redirect_to_input' ) );
	}

	/**
	 * WooCommerce by default redirect users to My-account page.
	 * Here we are checking force 2FA is enabled or not.
	 *
	 * @param string   $redirect Redirect URL.
	 * @param \WP_User $user Logged-in user.
	 *
	 * @return void
	 */
	public function handle_woocommerce_login_redirect( $redirect, $user ) {

		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return $redirect;
		}

		if ( false === $this->model->force_auth ) {
			return $redirect;
		}

		if ( $this->service->is_user_enabled_otp( $user->ID ) ) {
			return $redirect;
		}

		if ( $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return admin_url( 'profile.php' ) . '#defender-security';
		}

		return $redirect;
	}

	/**
	 * Finds whether atleast anyone user role in enabled 2FA user roles array.
	 *
	 * @param \WP_User $user User instance object.
	 * @return bool Return true for atleast one role matches else false return.
	 */
	private function is_auth_enabled_for_user_role( \WP_User $user ) {
		return ! empty( array_intersect( $user->roles, $this->model->user_roles ) );
	}

	/**
	 * Return redirect URL after 2FA submit.
	 */
	private function redirect_url() {
		return HTTP::post( 'redirect_to', defender_get_request_url() );
	}

	/**
	 * Adds redirect_to hidden input to Woo login.
	 */
	public function add_redirect_to_input() {
		echo '<input type="hidden" name="redirect_to" value="' . defender_get_request_url() . '">';
	}

}
