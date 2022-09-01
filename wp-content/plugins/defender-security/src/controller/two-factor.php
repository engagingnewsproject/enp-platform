<?php
declare( strict_types = 1 );

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Helper\Array_Cache;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Two_Factor\Providers\Webauthn;
use WP_Defender\Component\Two_Factor\Providers\Totp;
use WP_Defender\Controller;
use WP_Defender\Model\Setting\Two_Fa;
use Calotes\Component\Response;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Component\Two_Factor\Providers\Backup_Codes;
use WP_Defender\Component\Two_Factor\Providers\Fallback_Email;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_User;

class Two_Factor extends Controller {
	use Webauthn_Trait;

	/**
	 * Module slug and custom endpoint name.
	 *
	 * @var string
	 */
	public $slug = 'wdf-2fa';

	/**
	 * @var Two_Fa
	 */
	protected $model;

	/**
	 * @var mixed|Two_Fa_Component
	 */
	protected $service;

	/**
	 * @var array
	 */
	protected $compatibility_notices = [];

	/**
	 * @var \WP_Defender\Component\Password_Protection
	 */
	protected $password_protection_service;

	/**
	 * @var bool
	 */
	protected $is_woo_activated;

	protected $current_user;

	/**
	 * @var string
	 */
	private $flush_slug = 'defender_flush_rules';

	public function __construct() {
		$title = esc_html__( '2FA', 'wpdef' );

		$this->register_page(
			$title,
			$this->slug,
			[ &$this, 'main_view' ],
			$this->parent_slug,
			null,
			$this->menu_title( $title )
		);
		add_action( 'defender_enqueue_assets', [ &$this, 'enqueue_assets' ] );
		$this->register_routes();
		$this->service = wd_di()->get( Two_Fa_Component::class );
		$this->model = wd_di()->get( Two_Fa::class );
		$this->password_protection_service = wd_di()->get( \WP_Defender\Component\Password_Protection::class );
		$this->is_woo_activated = wd_di()->get( \WP_Defender\Integrations\Woocommerce::class )->is_activated();

		add_action( 'update_option_jetpack_active_modules', [ &$this, 'listen_for_jetpack_option' ], 10, 2 );

		if ( $this->model->enabled ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$is_jetpack_sso = $this->service->is_jetpack_sso();
			$is_tml = $this->service->is_tml();
			add_action( 'admin_init', [ $this->service, 'get_providers' ] );
			add_action( 'pre_get_users', [ &$this, 'filter_users_by_2fa' ] );
			add_action( 'show_user_profile', [ &$this, 'show_user_profile' ] );
			add_action( 'profile_update', [ &$this, 'profile_update' ] );
			add_action( 'wp_loaded', [ &$this, 'flush_rewrite_rules' ] );

			if ( ! defined( 'DOING_AJAX' ) && ! $is_jetpack_sso && ! $is_tml ) {
				add_filter( 'wp_authenticate_user', [ &$this, 'maybe_show_otp_form' ], 9, 2 );
				add_action( 'set_logged_in_cookie', [ &$this, 'store_session_key' ] );
				add_action( 'login_form_defender-verify-otp', [ &$this, 'verify_otp_login_time' ] );
			} else {
				if ( $is_jetpack_sso ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Jetpack's Wordpress.com Log In feature. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
				if ( $is_tml ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Theme my login. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
			}
			// Force auth redirect for admin area.
			add_action( 'current_screen', [ &$this, 'maybe_redirect_to_show_2fa_enabler' ], 1 );
			// This will be only displayed on a single site and the main site of MU.
			$is_multisite = is_multisite();
			if ( $is_multisite ) {
				add_filter( 'wpmu_users_columns', [ &$this, 'alter_users_table' ] );
				add_action( 'network_admin_notices', [ &$this, 'admin_notices' ] );
				add_filter( 'ms_user_row_actions', [ &$this, 'display_user_actions' ], 10, 2 );
			} else {
				add_filter( 'manage_users_columns', [ &$this, 'alter_users_table' ] );
				add_action( 'admin_notices', [ &$this, 'admin_notices' ] );
				add_filter( 'user_row_actions', [ &$this, 'display_user_actions' ], 10, 2 );
			}
			add_filter( 'manage_users_custom_column', [ &$this, 'alter_user_table_row' ], 10, 3 );
			add_filter( 'ms_shortcode_ajax_login', [ &$this, 'm2_no_ajax' ] );
			// Todo: add the verify for filter 'login_redirect'.
			if ( $this->is_woo_activated ) {
				$this->current_user = wp_get_current_user();
				$this->woocommerce_hooks();

				// Display 2FA content on Woo My Account page for enabled user roles.
				if ( $this->model->detect_woo && is_object( $this->current_user ) && $this->current_user->exists()
					&& $this->is_auth_enabled_for_user_role( $this->current_user )
				) {
					// Show a new Woo submenu.
					add_action( 'init', [ &$this, 'wp_defender_2fa_endpoint' ] );
					add_filter( 'query_vars', [ &$this, 'wp_defender_2fa_query_vars' ], 0 );
					add_filter( 'woocommerce_account_menu_items', [ &$this, 'wp_defender_2fa_link_my_account' ] );
					add_action( "woocommerce_account_{$this->slug}_endpoint", [ &$this, 'wp_defender_2fa_content' ] );
					// Display Woo content for 2FA user settings.
					add_shortcode( 'wp_defender_2fa_user_settings', [ $this, 'display_2fa_user_settings' ] );
					// Form processing.
					add_action( 'template_redirect', [ $this, 'save_2fa_details' ] );
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function woo_integration_enabled(): bool {
		return $this->is_woo_activated && $this->model->detect_woo;
	}

	/**
	 * Get menu title.
	 *
	 * @param string $title
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function menu_title( string $title ): string {
		$info = defender_white_label_status();
		$suffix = '<span style="padding: 2px 6px;border-radius: 9px;background-color: #17A8E3;color: #FFF;font-size: 8px;letter-spacing: -0.25px;text-transform: uppercase;vertical-align: middle;">' . __( 'NEW', 'wpdef' ) . '</span>';
		if ( ! $info['hide_doc_link'] ) {
			$title .= ' ' . $suffix;
		}

		return $title;
	}

	/**
	 * We have some feature conflict with jetpack, so listen to know when Defender can on.
	 *
	 * @param $old_value
	 * @param $value
	 *
	 * @return void
	 */
	public function listen_for_jetpack_option( $old_value, $value ): void {
		if ( false !== array_search( 'sso', $value, true ) ) {
			$this->model->mark_as_conflict( 'jetpack/jetpack.php' );
		} else {
			$this->model->mark_as_un_conflict( 'jetpack/jetpack.php' );
		}
	}

	/**
	 * If force redirect enabled, then we should check and redirect to profile page until the 2FA enabled.
	 *
	 * @return null|void
	 */
	public function maybe_redirect_to_show_2fa_enabler() {
		$user = wp_get_current_user();
		if ( ! is_object( $user ) ) {
			return;
		}
		// Is User role from common list checked?
		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return;
		}
		// Is 'Force Authentication' checked?
		if ( false === $this->model->force_auth ) {
			return;
		}
		// Is User role from forced list checked?
		if ( ! $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return;
		}
		// Is TOTP saved with a passcode?
		if ( ! empty( $this->service->get_available_providers_for_user( $user ) ) ) {
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
	public function alter_users_table( array $columns ): array {
		$columns = array_slice( $columns, 0, count( $columns ) - 1 )
			+ [ 'defender-two-fa' => __( 'Two Factor', 'wpdef' ) ]
			+ array_slice( $columns, count( $columns ) - 1 );

		return $columns;
	}

	/**
	 * @param string $val
	 * @param string $column_name
	 * @param int $user_id
	 *
	 * @return string
	 * @since 2.8.1 Update return value.
	 */
	public function alter_user_table_row( string $val, string $column_name, int $user_id ): string {
		if ( 'defender-two-fa' !== $column_name ) {
			return $val;
		}
		$provider_slug = get_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true );
		$provider = $this->service->get_provider_by_slug( $provider_slug );
		if ( is_wp_error( $provider ) ) {
			return '';
		}

		return $provider->get_user_label();
	}

	/**
	 * Retrieve the backup code if lost phone.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function send_backup_code( Request $request ): Response {
		$data = $request->get_data();
		$token = $data['token'];
		$ret = $this->service->send_otp_to_email( $token );
		if ( false === $ret ) {
			return new Response(
				false,
				[ 'message' => __( 'Please try again.', 'wpdef' ) ]
			);
		}

		if ( is_wp_error( $ret ) ) {
			return new Response(
				false,
				[ 'message' => $ret->get_error_message() ]
			);
		}

		return new Response(
			true,
			[ 'message' => __( 'Your code has been sent to your email.', 'wpdef' ) ]
		);
	}

	/**
	 * Verify the OTP after user login successful.
	 *
	 * @return null|void
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
		// Base params.
		$params = [
			'password' => $this->password_protection_service->get_submitted_password(),
			'user_id' => null,
		];
		// Find the user first.
		$token = HTTP::post( 'login_token' );
		$query = new \WP_User_Query(
			[
				'blog_id' => 0,
				'meta_key' => 'defender_two_fa_token',
				'meta_value' => $token,
			]
		);

		if ( 0 === $query->get_total() ) {
			$params['error'] = new \WP_Error( 'opt_fail', __( 'Invalid request', 'wpdef' ) );
			$this->render_otp_screen( $params );
		}
		$user = $query->get_results()[0];
		$params['user_id'] = $user->ID;
		$redirect = HTTP::post( 'redirect_to', admin_url() );
		$params['token'] = uniqid( 'two_fa' );
		update_user_meta( $user->ID, 'defender_two_fa_token', $params['token'] );
		// Get Auth method.
		$auth_method = HTTP::post( 'auth_method' );
		if ( empty( $auth_method ) ) {
			$auth_method = $this->service->get_default_provider_slug_for_user( $user->ID );
		}
		$params['default_slug'] = $auth_method;
		// Get provider object.
		$provider = $this->service->get_provider_by_slug( $auth_method );
		if ( is_wp_error( $provider ) ) {
			$params['error'] = $provider;
			$this->render_otp_screen( $params );
		}
		$result = $provider->validate_authentication( $user );
		if ( is_wp_error( $result ) ) {
			$params['error'] = $result;
			$this->render_otp_screen( $params );
		}
		if ( $result ) {
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
				// Todo: add code for 'rememberme'-option.
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
					$params['message'] = '<p class="message">' . __( 'You have logged in successfully.', 'wpdef' ) . '</p>';
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
	 * Render otp form. Required conditions for the current user:
	 * user data is not empty,
	 * password matches the user,
	 * user role is checked on 2FA settings,
	 * user has at least one 2FA auth method available.
	 *
	 * @param \WP_User|\WP_Error $user     Object of the logged-in user.
	 * @param string             $password Plain password string.
	 */
	public function maybe_show_otp_form( $user, string $password ) {
		$params = [];
		if (
			! empty( $user ) && ! empty( $password ) && $user instanceof WP_User
			&& wp_check_password( $password, $user->data->user_pass, $user->ID )
			&& $this->is_auth_enabled_for_user_role( $user )
			&& ! empty( $this->service->get_available_providers_for_user( $user ) )
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
			$params['user_id'] = $user->ID;
			// Get default provider.
			$params['default_slug'] = $this->service->get_default_provider_slug_for_user( $user->ID );
			if ( Fallback_Email::$slug === $params['default_slug'] ) {
				$result = $this->service->send_otp_to_email( $params['token'] );
				if ( is_wp_error( $result ) ) {
					$params['error'] = $result;
					$this->render_otp_screen( $params );
				}
			}
			$this->render_otp_screen( $params );
		}

		return $user;
	}

	/**
	 * Render the OTP screen after login successful.
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	private function render_otp_screen( array $params = [] ): void {
		$user = null;
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'defender-otp-screen', defender_asset_url( '/assets/css/otp.css' ) );
		$params['redirect_to'] = $this->redirect_url();
		if ( ! isset( $params['error'] ) ) {
			$params['error'] = null;
		}
		// If this goes here then the current user is ok, need to show the 2 auth.
		$this->attach_behavior( 'wpmudev', WPMUDEV::class );
		$custom_graphic = '';
		$custom_graphic_type = '';
		$settings = new Two_Fa();
		if ( $this->is_pro() && $settings->custom_graphic ) {
			$custom_graphic_type = $settings->custom_graphic_type;
			if ( $custom_graphic_type === Two_Fa::CUSTOM_GRAPHIC_TYPE_UPLOAD && ! empty( $settings->custom_graphic_url ) ) {
				$custom_graphic = $settings->custom_graphic_url;
			} elseif ( $custom_graphic_type === Two_Fa::CUSTOM_GRAPHIC_TYPE_LINK && ! empty( $settings->custom_graphic_link ) ) {
				$custom_graphic = $settings->custom_graphic_link;
			}
		}
		$this->detach_behavior( 'wpmudev' );
		$params['custom_graphic'] = $custom_graphic;
		$params['custom_graphic_type'] = $custom_graphic_type;

		$routes = $this->dump_routes_and_nonces();
		$params['providers'] = [];
		if ( isset( $params['user_id'] ) ) {
			$user = get_user_by( 'id', $params['user_id'] );
			if ( is_object( $user ) ) {
				$params['providers'] = $this->service->get_available_providers_for_user( $user );
				// Get default provider.
				if ( empty( $params['default_slug'] ) ) {
					$params['default_slug'] = $this->service->get_default_provider_slug_for_user( $user->ID );
				}
			}
		}
		if ( true === array_key_exists( Webauthn::$slug, $params['providers'] ) ) {
			wp_enqueue_style( 'defender-biometric-login-screen', defender_asset_url( '/assets/css/biometric.css' ), [], DEFENDER_VERSION );
			wp_enqueue_script(
				'wpdef_webauthn_common_script',
				plugins_url( 'assets/js/webauthn-common.js', WP_DEFENDER_FILE ),
				[],
				DEFENDER_VERSION,
				true
			);
			wp_enqueue_script(
				'defender-biometric-login-script',
				plugins_url( 'assets/js/biometric-login.js', WP_DEFENDER_FILE ),
				[
					'jquery',
					'wpdef_webauthn_common_script',
				],
				DEFENDER_VERSION,
				true
			);
			$webauthn_controller = wd_di()->get( \WP_Defender\Controller\Webauthn::class );
			wp_localize_script(
				'defender-biometric-login-script',
				'webauthn',
				[
					'admin_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wpdef_webauthn' ),
					'i18n' => $webauthn_controller->get_translations(),
					'username' => ! empty( $user->user_login ) ? $user->user_login : '',
					'provider_slug' => Webauthn::$slug,
					'error' => $params['error'],
				]
			);
		}
		$params['action_fallback_email'] = admin_url( 'admin-ajax.php' ) . sprintf(
			'?action=%s&route=%s&_def_nonce=%s',
			defender_base_action(),
			$this->check_route( $routes['routes']['send_backup_code'] ),
			$routes['nonces']['send_backup_code']
		);
		$this->render_partial( 'two-fa/otp', $params );
		exit;
	}

	/**
	 * Cache the auth cookie.
	 *
	 * @param $cookie
	 *
	 * @return void
	 */
	public function store_session_key( $cookie ): void {
		// Clear login cookie to ensure nonce consistency.
		if ( ! is_user_logged_in() && isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		}

		$cookie = wp_parse_auth_cookie( $cookie, 'logged_in' );
		Array_Cache::set( 'auth_cookie', $cookie, '2fa' );
	}

	/**
	 * Disable 2FA TOTP method for the current user. It's not from the list of routes.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function disable_totp() {
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'defenderAuthOn', 0 );
		delete_user_meta( $user_id, 'defenderAuthSecret' );
		// Remove TOTP from enabled providers.
		$enabled_providers = get_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, true );
		if ( isset( $enabled_providers ) && ! empty( $enabled_providers ) ) {
			foreach ( $enabled_providers as $key => $slug ) {
				if ( Totp::$slug === $slug ) {
					unset( $enabled_providers[ $key ] );
					break;
				}
			}
		} else {
			$enabled_providers = '';
		}
		update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );

		return new Response( true, [] );
	}

	/**
	 * Verify the OTP and enable 2fa, use in profile.php. It's not from the list of routes.
	 *
	 * @param Request $request
	 *
	 * @return void|Response
	 * @defender_route
	 * @is_public
	 */
	public function verify_otp_for_enabling( Request $request ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$data = $request->get_data();
		$otp = $data['otp'] ?? false;
		if ( false === $otp || strlen( $otp ) < 6 ) {
			return new Response(
				false,
				[ 'message' => __( 'Please input a valid OTP code.', 'wpdef' ) ]
			);
		}
		if ( $this->service->verify_otp( $otp ) ) {
			$user_id = get_current_user_id();
			$this->service->enable_otp( $user_id );
			$totp_slug = Totp::$slug;
			// Add TOTP to enabled providers.
			$enabled_providers = get_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, true );
			if ( isset( $enabled_providers ) && ! empty( $enabled_providers ) ) {
				// Array of enabled providers is not empty now.
				if ( ! in_array( Totp::$slug, $enabled_providers, true ) ) {
					$enabled_providers[] = $totp_slug;
					update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );
				}
			} else {
				// Array of enabled providers is empty now.
				update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, [ $totp_slug ] );
			}
			// If no default provider then add TOTP as it.
			$default_provider = get_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true );
			if ( empty( $default_provider ) ) {
				update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, $totp_slug );
			}

			return new Response( true, [] );
		} else {
			return new Response(
				false,
				[ 'message' => __( 'Your OTP code is incorrect. Please try again.', 'wpdef' ) ]
			);
		}
	}

	/**
	 * @param int $user_id
	 *
	 * @return void
	 */
	protected function clear_providers( int $user_id ): void {
		update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, '' );
		update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, '' );
	}

	/**
	 * Triggers ONLY when a user is viewing their own profile page.
	 * For all users need to use the hook 'edit_user_profile_update'.
	 *
	 * @param int $user_id
	 *
	 * @return null|void
	 */
	public function profile_update( int $user_id ) {
		if ( isset( $_POST['_wpdef_2fa_nonce_user_options'] ) ) {
			check_admin_referer( 'wpdef_2fa_user_options', '_wpdef_2fa_nonce_user_options' );

			if (
				! isset( $_POST[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ] )
				|| ! is_array( $_POST[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ] )
			) {
				return;
			}
			// Remove empty elements.
			$checked_providers = array_diff( $_POST[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ], [ '' ] );
			// If no option is checked then the values for default provider and enabled providers are cleared.
			if ( empty( $checked_providers ) ) {
				$this->clear_providers( $user_id );

				return;
			}

			$providers = $this->service->get_providers();
			// For Fallback-Email method: the email value should be not empty and valid.
			if ( in_array( Fallback_Email::$slug, $checked_providers, true ) ) {
				$email = HTTP::post( 'def_2fa_backup_email' );
				if ( ! empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
					update_user_meta( $user_id, Fallback_Email::FALLBACK_EMAIL_KEY, $email );
				} else {
					unset( $checked_providers[ Fallback_Email::$slug ] );
				}
			}

			// For Webauthn method: a user must have at least once device registered.
			$key = array_search( Webauthn::$slug, $checked_providers, true );
			if ( false !== $key ) {
				$user_authenticators = wd_di()->get( \WP_Defender\Controller\Webauthn::class )->get_current_user_authenticators( $user_id );
				if ( 0 === count( $user_authenticators ) ) {
					unset( $checked_providers[ $key ] );
				}
			}
			// Case when WebAuthn is checked but no registered devices OR Fallback_Email has an invalid email value.
			if ( empty( $checked_providers ) ) {
				$this->clear_providers( $user_id );

				return;
			}

			// Current user.
			$user = get_user_by( 'id', $user_id );
			// Enable only the available providers.
			$enabled_providers = [];
			foreach ( $providers as $slug => $provider ) {
				if ( in_array( $slug, $checked_providers, true ) && $provider->is_available_for_user( $user ) ) {
					$enabled_providers[] = $slug;
				}
			}
			update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );
			// Default provider must be enabled.
			$default_provider = $_POST[ Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY ] ?? '';
			// The case#1 when all 2fa providers were deactivated before.
			if ( empty( $default_provider ) ) {
				$default_provider = $enabled_providers[0];
			}
			// The case#2 when prev default provider is deactivated and another one is activated.
			if ( ! in_array( $default_provider, $checked_providers, true ) ) {
				$default_provider = $enabled_providers[0];
			}
			// Save default provider.
			update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, $default_provider );
		}
	}

	/**
	 * Check if DEFENDER_DEBUG is enabled for the route.
	 *
	 * @param string $route
	 *
	 * @return string|array
	 */
	public function check_route( string $route ) {
		return defined( 'DEFENDER_DEBUG' ) && true === constant( 'DEFENDER_DEBUG' )
			? wp_slash( $route )
			: $route;
	}

	/**
	 * A simple filter to show activate 2fa screen on profile page.
	 *
	 * @param WP_User $user The current WP_User object.
	 *
	 * @return void
	 */
	public function show_user_profile( WP_User $user ): void {
		$user_roles = $this->get_roles( $user );
		// This method is better than is_intersected_arrays() because it is flexibly controlled with a nested hook.
		if ( ! empty( $user_roles ) && $this->is_auth_enabled_for_user_role( $user ) ) {
			wp_enqueue_style( 'defender-profile-2fa', defender_asset_url( '/assets/css/two-factor.css' ) );

			$webauthn_controller = wd_di()->get( \WP_Defender\Controller\Webauthn::class );
			$webauthn_requirements = $this->check_webauthn_requirements();
			if ( $this->service->is_checked_enabled_provider_by_slug( $user, Webauthn::$slug ) && ! $webauthn_requirements ) {
				$this->service->remove_enabled_provider_for_user( Webauthn::$slug, $user );
			}

			wp_enqueue_script(
				'wpdef_webauthn_common_script',
				plugins_url( 'assets/js/webauthn-common.js', WP_DEFENDER_FILE ),
				[],
				DEFENDER_VERSION,
				true
			);
			wp_enqueue_script(
				'wpdef_webauthn_script',
				plugins_url( 'assets/js/webauthn.js', WP_DEFENDER_FILE ),
				[
					'jquery',
					'wpdef_webauthn_common_script',
				],
				DEFENDER_VERSION,
				true
			);
			wp_localize_script(
				'wpdef_webauthn_script',
				'webauthn',
				[
					'admin_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wpdef_webauthn' ),
					'i18n' => $webauthn_controller->get_translations(),
					'registered_auths' => $webauthn_controller->get_current_user_authenticators( $user->ID ),
					'username' => ! empty( $user->user_login ) ? $user->user_login : '',
				]
			);

			$forced_auth = $this->service->is_intersected_arrays( $user_roles, $this->model->force_auth_roles );
			$default_values = $this->model->get_default_values();
			$enabled_providers = $this->service->get_available_providers_for_user( $user );
			$enabled_provider_slugs = ! empty( $enabled_providers ) ? array_keys( $enabled_providers ) : [];
			$default_provider_slug = $this->service->get_default_provider_slug_for_user( $user->ID );
			$webauthn_enabled = $this->service->is_checked_enabled_provider_by_slug( $user, Webauthn::$slug );

			$this->render_partial(
				'two-fa/user-options',
				[
					'is_force_auth' => $forced_auth && $this->model->force_auth && empty( $enabled_providers ),
					'force_auth_message' => $this->model->force_auth_mess,
					'default_message' => $default_values['message'],
					'user' => $user,
					'all_providers' => $this->service->get_providers(),
					'enabled_providers_key' => Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY,
					'default_provider_key' => Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY,
					'checked_provider_slugs' => $enabled_provider_slugs,
					'checked_def_provider_slug' => ! empty( $default_provider_slug ) ? $default_provider_slug : null,
					'webauthn_requirements' => $webauthn_requirements,
					'webauthn_enabled' => $webauthn_enabled,
					'webauthn_slug' => Webauthn::$slug,
					'is_admin' => is_admin(),
				]
			);
		}
	}

	/**
	 * Save settings.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$model = $this->model;
		$data = $request->get_data();
		$woo_toggle_change = false;
		// Woo is activated and Woo-toggle is changed from 'false' to 'true'.
		if ( $this->is_woo_activated && false === $model->detect_woo && true === $data['detect_woo'] ) {
			$woo_toggle_change = true;
		}
		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
			Config_Hub_Helper::set_clear_active_flag();

			if ( $woo_toggle_change ) {
				set_site_transient( $this->flush_slug, true, 3600 );
			}

			return new Response(
				true,
				array_merge(
					[ 'message' => __( 'Your settings have been updated.', 'wpdef' ) ],
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			[ 'message' => $model->get_formatted_errors() ]
		);
	}

	/**
	 * Flush rewrite rules to make the plugin custom endpoint available.
	 */
	public function flush_rewrite_rules() {
		if ( get_site_transient( $this->flush_slug ) ) {
			flush_rewrite_rules();
			delete_site_transient( $this->flush_slug );
		}
	}

	/**
	 * @return null|void
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
	public function send_test_email( Request $request ): Response {
		$data = $request->get_data(
			[
				'email_subject' => [
					'type' => 'string',
					'sanitize' => 'sanitize_text_field',
				],
				'email_sender' => [
					'type' => 'string',
					'sanitize' => 'sanitize_text_field',
				],
				'email_body' => [
					'type' => 'string',
					'sanitize' => 'wp_kses_post',
				],
			]
		);

		$subject = $data['email_subject'];
		$sender = $data['email_sender'];
		$body = $this->render_partial(
			'email/2fa-lost-phone',
			[
				'body' => nl2br( $data['email_body'] ),
			],
			false
		);

		$params = [
			'passcode' => '[a-sample-passcode]',
			'display_name' => $this->get_user_display( get_current_user_id() ),
		];

		foreach ( $params as $key => $param ) {
			$body = str_replace( "{{{$key}}}", $param, $body );
		}
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		if ( $sender ) {
			$from_email = get_bloginfo( 'admin_email' );
			$headers[] = sprintf( 'From: %s <%s>', $sender, $from_email );
		}
		// Main email template.
		$body = $this->render_partial(
			'email/index',
			[
				'title' => __( 'Two-Factor Authentication', 'wpdef' ),
				'content_body' => $body,
				// An empty value because 2FA-email is sent after a manual click from the user.
				'unsubscribe_link' => '',
			],
			false
		);

		$send_mail = wp_mail( Fallback_Email::get_backup_email(), $subject, $body, $headers );
		if ( $send_mail ) {
			return new Response(
				true,
				[ 'message' => __( 'Test email has been sent to your email.', 'wpdef' ) ]
			);
		} else {
			return new Response(
				false,
				[ 'message' => __( 'Test email failed.', 'wpdef' ) ]
			);
		}
	}

	/**
	 * @return array
	 */
	public function to_array(): array {
		$settings = new Two_Fa();
		[$routes, $nonces] = Route::export_routes( 'two_fa' );

		return [
			'enabled' => $settings->enabled,
			'useable' => $settings->enabled && count( $settings->user_roles ),
			'nonces' => $nonces,
			'endpoints' => $routes,
		];
	}

	/**
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * @return void
	 */
	public function remove_settings(): void {
		( new Two_Fa() )->delete();
	}

	/**
	 * Remove all users meta. Keys need to remove:
	 * defenderAuthEmail, defenderAuthOn, defenderForceAuth, defenderBackupCode, defenderAuthSecret,
	 * Two_Fa::DEFAULT_PROVIDER_USER_KEY, Two_Fa::ENABLED_PROVIDERS_USER_KEY,
	 * Backup_Codes::BACKUP_CODE_VALUES, Backup_Codes::BACKUP_CODE_START.
	 *
	 * @return void
	 */
	public function remove_data(): void {
		global $wpdb;
		$sql = "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('defenderAuthEmail','defenderAuthOn','defenderForceAuth','defenderBackupCode','defenderAuthSecret', 'wd_2fa_default_provider', 'wd_2fa_enabled_providers', 'wd_2fa_backup_codes', 'wd_2fa_backup_codes_is_activated');";
		$wpdb->query( $sql );
	}

	/**
	 * Filter users by 2FA option.
	 *
	 * @return void
	 */
	public function filter_users_by_2fa( $query ): void {
		global $pagenow;

		if ( is_admin()
			&& 'users.php' === $pagenow
			&& isset( $_GET['wpdef_two_fa'] )
			&& 'enabled' === sanitize_text_field( $_GET['wpdef_two_fa'] )
		) {
			$query->set(
				'meta_query',
				[
					[
						'key' => Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY,
						'value' => array_keys( $this->service->get_providers() ),
						'compare' => 'IN',
					],
				]
			);
		}
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend(): array {
		return array_merge(
			[
				'model' => $this->model->export(),
				'all_roles' => $this->get_all_editable_roles(),
				'count' => $this->service->count_users_with_enabled_2fa(),
				'notices' => $this->compatibility_notices,
				'new_feature' => '<span class="sui-tag sui-tag-beta margin-right-10">' . __( 'Beta', 'wpdef' ) . '</span>'
					. sprintf(
					/* translators: %s: link */
						__( 'Web Authentication is now available. <a target="_blank" href="%s">Click here</a> to find out more.', 'wpdef' ),
						'https://wpmudev.com/docs/wpmu-dev-plugins/defender/#web-authentication'
					),
				'count_checked_roles' => count( $this->model->user_roles ),
				'is_woo_active' => $this->is_woo_activated,
			],
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function import_data( $data ): void {
		$model = new Two_Fa();

		$model->import( $data );
		/**
		 * Sometime, the custom image broken on import. When that happen, we will revert to the default image.
		 */
		$model->custom_graphic_url = $this->service->get_custom_graphic_url( $model->custom_graphic_url );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function export_strings(): array {
		$settings = new Two_Fa();

		return [
			$settings->enabled ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		];
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( array $config, bool $is_pro ): array {
		return [
			$config['enabled'] ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		];
	}

	/**
	 * Stop ajax login on membership 2.
	 *
	 * @return bool
	 */
	public function m2_no_ajax(): bool {
		return false;
	}

	/**
	 * WooCommerce prevents any user who cannot 'edit_posts' (subscribers, customers etc.) from accessing admin.
	 * Here we are disabling WooCommerce default behavior, if force 2FA is enabled.
	 *
	 * @param bool $prevent Prevent admin access.
	 *
	 * @return bool|null
	 */
	public function handle_woocommerce_prevent_admin_access( bool $prevent ) {
		$user = $this->current_user;
		if ( ! is_object( $user ) ) {
			return;
		}
		// Is User role from common list checked?
		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return $prevent;
		}
		// Is 'Force Authentication' checked?
		if ( false === $this->model->force_auth ) {
			return $prevent;
		}
		// Is User role from forced list checked?
		if ( $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return false;
		}
		// Is TOTP saved with a passcode?
		if ( ! empty( $this->service->get_available_providers_for_user( $user ) ) ) {
			return $prevent;
		}

		return $prevent;
	}

	/**
	 * WooCommerce specific hooks.
	 *
	 * @return void
	 */
	private function woocommerce_hooks(): void {
		// This filter added only for disable WooCommerce default behavior.
		add_filter( 'woocommerce_prevent_admin_access', [ $this, 'handle_woocommerce_prevent_admin_access' ], 10, 1 );
		// Handle WooCommerce MyAccount page login redirect.
		add_filter( 'woocommerce_login_redirect', [ $this, 'handle_woocommerce_login_redirect' ], 10, 2 );
		// Add field.
		add_action( 'woocommerce_login_form_end', [ $this, 'add_redirect_to_input' ] );
	}

	/**
	 * WooCommerce by default redirect users to My-account page.
	 * Here we are checking force 2FA is enabled or not.
	 *
	 * @param string  $redirect Redirect URL.
	 * @param WP_User $user     Logged-in user.
	 *
	 * @return string
	 */
	public function handle_woocommerce_login_redirect( string $redirect, WP_User $user ): string {
		// Is User role from common list checked?
		if ( false === $this->is_auth_enabled_for_user_role( $user ) ) {
			return $redirect;
		}
		// Is 'Force Authentication' checked?
		if ( false === $this->model->force_auth ) {
			return $redirect;
		}
		// Is User role from forced list checked?
		if ( ! $this->service->is_force_auth_enable_for( $user->ID, $this->model->force_auth_roles ) ) {
			return $redirect;
		}
		// Is TOTP saved with a passcode?
		if ( empty( $this->service->get_available_providers_for_user( $user ) ) ) {
			return admin_url( 'profile.php' ) . '#defender-security';
		}

		return $redirect;
	}

	/**
	 * Finds whether at least anyone user role in enabled 2FA user roles array.
	 *
	 * @param WP_User $user User instance object.
	 *
	 * @return bool Return true for at least one role matches else false return.
	 */
	private function is_auth_enabled_for_user_role( WP_User $user ): bool {
		/**
		 * Filter 2FA option for a specific user.
		 *
		 * @param bool   $flag
		 * @param string $user_id
		 */
		if ( false === apply_filters( 'wp_defender_2fa_user_enabled', true, $user->ID ) ) {
			return false;
		}
		return ! empty( array_intersect( $this->get_roles( $user ), $this->model->user_roles ) );
	}

	/**
	 * Return redirect URL after 2FA submit.
	 */
	private function redirect_url() {
		return HTTP::post( 'redirect_to', defender_get_request_url() );
	}

	/**
	 * Adds redirect_to hidden input to Woo login.
	 *
	 * @return void
	 */
	public function add_redirect_to_input(): void {
		echo '<input type="hidden" name="redirect_to" value="' . defender_get_request_url() . '">';
	}

	/**
	 * Generate Backup codes on Profile page.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function generate_backup_codes(): Response {
		$user = wp_get_current_user();

		return new Response(
			true,
			[
				'codes' => Backup_Codes::generate_codes( $user ),
				'count' => Backup_Codes::display_number_of_codes( Backup_Codes::get_unused_codes_for_user( $user ) ),
				'title' => sprintf(
				/* translators: %s: count */
					__( '2FA Backup Codes for %s:', 'wpdef' ),
					get_bloginfo( 'url' )
				),
				'button_text' => __( 'Get New Codes', 'wpdef' ),
				'description' => __( 'Each backup code can only be used to log in once.', 'wpdef' ),
			]
		);
	}

	/**
	 * Add the link to reset 2FA settings for specific user. It's without bulk actions.
	 *
	 * @param string[] $actions
	 * @param WP_User $user WP_User object for the currently listed user.
	 *
	 * @return array
	 */
	public function display_user_actions( $actions, WP_User $user ): array {
		// Only for users that have one enabled 2fa method at least.
		if ( empty( get_user_meta( $user->ID, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true ) ) ) {
			return $actions;
		}

		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		if ( current_user_can( $cap ) ) {
			$actions['disable_wpdef_2fa_methods'] = sprintf(
			/* translators: %s: URL , %s: title link */
				'<a href="%s">%s</a>',
				wp_nonce_url( "users.php?action=disable_2fa_methods&amp;user={$user->ID}", 'bulk-users' ),
				__( 'Reset two factor', 'wpdef' )
			);
		}

		return $actions;
	}

	/**
	 * Reset 2FA methods for specific user and display notice.
	 *
	 * @return null|void
	 */
	public function admin_notices() {
		$screen = get_current_screen();
		$screen_id = is_multisite() ? 'users-network' : 'users';

		if ( $screen_id !== $screen->id || ! current_user_can( 'edit_users' ) ) {
			return;
		}
		if ( ! isset( $_GET['action'], $_GET['user'] ) ) {
			return;
		}
		$action = sanitize_text_field( $_GET['action'] );
		if ( 'disable_2fa_methods' !== $action ) {
			return;
		}
		$user_id = sanitize_text_field( $_GET['user'] );
		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return;
		}
		// Maybe the value has already been cleared.
		$default_provider = get_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true );
		if ( empty( $default_provider ) ) {
			return;
		}
		// Default and enabled 2fa providers are cleared for user.
		update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, '' );
		update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, '' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: URL to regenerate code */
					__( 'Two factor authentication has been reset for <b>%s.</b>', 'wpdef' ),
					$user->display_name
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Shortcode to display 2FA user settings.
	 *
	 * @since 3.2.0
	 */
	public function display_2fa_user_settings() {
		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) ) {
			wp_enqueue_script( 'wp-i18n' );

			do_action( 'wd_2fa_form_before' );

			echo '<form class="wpdef-2fa-wrap" action="" method="post">';

			$this->show_user_profile( $this->current_user );

			echo '<input type="hidden" name="action" value="save_def_2fa_user_settings" />';
			echo '<button type="submit" class="button" name="save_def_2fa_user_settings" value="' . esc_attr__( 'Save changes', 'wpdef' ) . '">'
				. esc_html__( 'Save changes', 'wpdef' ) . '</button>';
			echo '</form>';

			do_action( 'wd_2fa_form_after' );
		} else {
			apply_filters( 'wd_2fa_form_when_not_logged_in', '' );
		}
	}

	// 1. Register new endpoint (URL) for My Account page. Re-save Permalinks or it will give 404 error.
	public function wp_defender_2fa_endpoint() {
		add_rewrite_endpoint( $this->slug, EP_ROOT | EP_PAGES );
	}

	// 2. Add new query var.
	public function wp_defender_2fa_query_vars( $vars ): array {
		$vars[] = $this->slug;

		return $vars;
	}

	// 3. Insert the new endpoint into the My Account menu.
	public function wp_defender_2fa_link_my_account( $items ): array {
		$needed_place = is_array( $items ) && ! empty( $items ) ? ( count( $items ) - 1 ) : 0;

		return array_slice( $items, 0, $needed_place, true )
			+ [ $this->slug => __( '2FA', 'wpdef' ) ]
			+ array_slice( $items, $needed_place, null, true );
	}

	// 4. Add content to the new tab.
	public function wp_defender_2fa_content() {
		echo do_shortcode( '[wp_defender_2fa_user_settings]' );
	}

	/**
	 * Save the 2fa details and redirect back to 'My Account' page.
	 */
	public function save_2fa_details() {
		if ( empty( $_POST['action'] ) || 'save_def_2fa_user_settings' !== $_POST['action'] ) {
			return;
		}

		wc_nocache_headers();

		$user_id = $this->current_user->ID;
		if ( $user_id <= 0 ) {
			return;
		}
		// Verify nonce and other two-factor arguments passed.
		$this->profile_update( $user_id );

		wc_add_notice( __( 'Two-Factor settings updated successfully.', 'wpdef' ) );
		// @since 3.2.0
		do_action( 'wd_woocommerce_save_2fa_details', $user_id );

		wp_safe_redirect( wc_get_endpoint_url( $this->slug, '', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}
}
