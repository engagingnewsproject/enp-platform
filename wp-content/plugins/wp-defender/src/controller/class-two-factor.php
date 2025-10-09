<?php
/**
 * This class handles all the routes related to two-factor authentication.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_User;
use WP_Error;
use Exception;
use WP_User_Query;
use SodiumException;
use WP_Defender\Event;
use WP_Session_Tokens;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use WP_Defender\Component\Crypt;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Integrations\Woocommerce;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Component\Two_Factor\Providers\Totp;
use WP_Defender\Component\Two_Factor\Providers\Webauthn;
use WP_Defender\Component\Webauthn as Webauthn_Component;
use WP_Defender\Controller\Webauthn as Webauthn_Controller;
use WP_Defender\Component\Two_Factor\Providers\Backup_Codes;
use WP_Defender\Component\Two_Factor\Providers\Fallback_Email;
use WP_Defender\Component\Password_Protection as Password_Protection_Service;

/**
 * Handles all the routes related to two-factor authentication.
 */
class Two_Factor extends Event {

	use Webauthn_Trait;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-2fa';

	/**
	 * The model for handling the data.
	 *
	 * @var Two_Fa
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Two_Fa_Component
	 */
	protected $service;

	/**
	 * Compatibility notices for the frontend.
	 *
	 * @var array
	 */
	protected $compatibility_notices = array();

	/**
	 * Logic for handling the password protection.
	 *
	 * @var Password_Protection_Service
	 */
	protected $password_protection_service;

	/**
	 * Is the woocommerce plugin activated.
	 *
	 * @var bool
	 */
	protected $is_woo_activated;
	/**
	 * The current logged-in user.
	 *
	 * @var WP_User
	 */
	protected $current_user;

	/**
	 * Defender flush rules slug.
	 *
	 * @var string
	 */
	private $flush_slug = 'defender_flush_rules';

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_page(
			esc_html__( '2FA', 'wpdef' ),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug
		);
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		$this->register_routes();
		$this->service                     = wd_di()->get( Two_Fa_Component::class );
		$this->model                       = wd_di()->get( Two_Fa::class );
		$this->password_protection_service = wd_di()->get( Password_Protection_Service::class );
		$this->is_woo_activated            = wd_di()->get( Woocommerce::class )->is_activated();

		add_action( 'update_option_jetpack_active_modules', array( $this, 'listen_for_jetpack_option' ), 10, 2 );

		if ( $this->model->is_active() ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$is_jetpack_sso = $this->service->is_jetpack_sso();
			$is_tml         = $this->service->is_tml();
			add_action( 'admin_init', array( $this->service, 'get_providers' ) );
			add_action( 'pre_get_users', array( $this, 'filter_users_by_2fa' ) );
			add_action( 'show_user_profile', array( $this, 'show_user_profile' ) );
			add_action( 'profile_update', array( $this, 'profile_update' ) );

			if ( ! defined( 'DOING_AJAX' ) && ! $is_jetpack_sso && ! $is_tml ) {
				add_filter( 'authenticate', array( $this, 'maybe_show_otp_form' ), 30, 3 );
				add_action( 'set_logged_in_cookie', array( $this, 'store_session_key' ) );
				add_action( 'login_form_defender-verify-otp', array( $this, 'verify_otp_login_time' ) );
			} else {
				if ( $is_jetpack_sso ) {
					$this->compatibility_notices[] = esc_html__(
						'We`ve detected a conflict with Jetpack`s Wordpress.com Log In feature. Please disable it and return to this page to continue setup.',
						'wpdef'
					);
				}
				if ( $is_tml ) {
					$this->compatibility_notices[] = esc_html__(
						'We`ve detected a conflict with Theme my login. Please disable it and return to this page to continue setup.',
						'wpdef'
					);
				}
			}
			// Force auth redirect for admin area.
			add_action( 'current_screen', array( $this, 'maybe_redirect_to_show_2fa_enabler' ), 1 );

			$this->service->add_hooks();

			// Todo: add the verify for filter 'login_redirect'.
			if ( $this->is_woo_activated ) {
				// Todo: move to Woocommerce class.
				$this->current_user = wp_get_current_user();
				$this->woocommerce_hooks();

				// Display 2FA content on Woo My Account page for enabled user roles.
				if ( $this->model->detect_woo && is_object( $this->current_user ) && $this->current_user->exists()
					&& $this->service->is_auth_enable_for( $this->current_user, $this->model->user_roles )
				) {
					// Show a new Woo submenu.
					add_action( 'init', array( $this, 'wp_defender_2fa_endpoint' ) );
					add_filter( 'query_vars', array( $this, 'wp_defender_2fa_query_vars' ), 0 );
					add_filter( 'woocommerce_account_menu_items', array( $this, 'wp_defender_2fa_link_my_account' ) );
					add_action(
						"woocommerce_account_{$this->slug}_endpoint",
						array(
							&
							$this,
							'wp_defender_2fa_content',
						)
					);
					// Display Woo content for 2FA user settings.
					add_shortcode( 'wp_defender_2fa_user_settings', array( $this, 'display_2fa_user_settings' ) );
					// Form processing.
					add_action( 'template_redirect', array( $this, 'save_2fa_details' ) );
				}
			}
			// Fires when 2FA methods are enabled.
			add_action( 'wd_2fa_enabled_provider_slugs', array( $this, 'enable_provider_slugs' ) );
		}
	}

	/**
	 * Checks if WooCommerce integration is enabled.
	 *
	 * @return bool Whether WooCommerce integration is enabled.
	 */
	public function woo_integration_enabled(): bool {
		return $this->is_woo_activated && $this->model->detect_woo;
	}

	/**
	 * We have some feature conflict with jetpack, so listen to know when Defender can on.
	 *
	 * @param  mixed $old_value  Old value of `jetpack_active_modules`.
	 * @param  mixed $value  New value of `jetpack_active_modules`.
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
	 * @return void
	 */
	public function maybe_redirect_to_show_2fa_enabler() {
		$user = wp_get_current_user();
		if ( ! is_object( $user ) ) {
			return;
		}
		// Is User role from common list checked?
		if ( false === $this->service->is_auth_enable_for( $user, $this->model->user_roles ) ) {
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
		if ( array() !== $this->service->get_available_providers_for_user( $user ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( 'profile' !== $screen->id ) {
			wp_safe_redirect( admin_url( 'profile.php' ) . '#defender-security' );
			exit;
		}
	}

	/**
	 * Retrieve the backup code if lost phone.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function send_backup_code( Request $request ): Response {
		$data    = $request->get_data();
		$token   = $data['token'];
		$user_id = (int) $data['requested_user'];
		$ret     = $this->service->send_otp_to_email( $token, $user_id );
		if ( false === $ret ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Please try again.', 'wpdef' ) )
			);
		}

		if ( is_wp_error( $ret ) ) {
			return new Response(
				false,
				array( 'message' => $ret->get_error_message() )
			);
		}

		return new Response(
			true,
			array( 'message' => esc_html__( 'Your code has been sent to your email.', 'wpdef' ) )
		);
	}

	/**
	 * Verify the OTP after user login successful.
	 *
	 * @return void
	 */
	public function verify_otp_login_time() {
		if ( 'POST' !== defender_get_data_from_request( 'REQUEST_METHOD', 's' ) ) {
			return;
		}

		$post = defender_get_data_from_request( null, 'p' );
		if ( '' === $post['_wpnonce'] || ! wp_verify_nonce( $post['_wpnonce'], 'verify_otp' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'wpdef' ) );
		}

		$token       = HTTP::post( 'login_token' );
		$user_id     = (int) HTTP::post( 'requested_user', 0 );
		$auth_method = HTTP::post( 'auth_method' );
		$password    = HTTP::post( 'password' );
		if ( '' === $token || 0 === $user_id || '' === $auth_method || '' === $password ) {
			wp_die( esc_html__( 'Missing parameter(s)', 'wpdef' ) );
		}

		$user = get_user_by( 'id', $user_id );
		// Spoofed data? E.g. a hidden field user is changed.
		if ( ! is_object( $user ) ) {
			wp_die( esc_html__( 'Invalid user.', 'wpdef' ) );
		}

		$hashed_token = get_user_meta( $user_id, Two_Fa_Component::TOKEN_USER_KEY, true );
		// Spoofed data again?
		if ( ! Crypt::compare_lines( $hashed_token, wp_hash( $user_id . $token ) ) ) {
			wp_die( esc_html__( 'Invalid request.', 'wpdef' ) );
		}

		// Base params.
		$params = array(
			'password'     => $this->password_protection_service->get_submitted_password(),
			'user_id'      => $user->ID,
			'token'        => $this->get_token( $user_id ),
			'default_slug' => $auth_method,
		);

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
			delete_user_meta( $user->ID, Two_Fa_Component::TOKEN_USER_KEY );

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
				 * @param  int  $user_id  @since 2.6.1
				 * @param  string  $auth_method  @since 3.4.0
				 */
				do_action( 'wpmu_2fa_login', $user_id, $auth_method );

				if ( ! empty( defender_get_data_from_request( 'interim-login', 'r' ) ) ) {
					$params['interim_login'] = 'success';
					$params['message']       = '<p class="message">' . esc_html__( 'You have logged in successfully.', 'wpdef' ) . '</p>';
					$this->render_otp_screen( $params );
					exit;
				} else {
					// Usual success.
					$redirect = apply_filters(
						'login_redirect',
						HTTP::post( 'redirect_to', admin_url() ),
						$this->redirect_url(),
						$user
					);
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}
		$lockout_message = $this->service->verify_attempt( $user->ID, Totp::$slug );

		$params['error'] = new WP_Error(
			'opt_fail',
			empty( $lockout_message )
				? esc_html__( 'Whoops, the passcode you entered was incorrect or expired.', 'wpdef' )
				: $lockout_message
		);
		$this->render_otp_screen( $params );
		exit;
	}

	/**
	 * Generate a unique token for user.
	 *
	 * @param  int $user_id  User ID.
	 *
	 * @return string Unique token.
	 */
	public function get_token( int $user_id ): string {
		$token = bin2hex( Crypt::random_bytes( 32 ) );
		update_user_meta( $user_id, Two_Fa_Component::TOKEN_USER_KEY, wp_hash( $user_id . $token ) );

		return $token;
	}

	/**
	 * Render otp form. Required conditions for the current user:
	 * - is not logged in,
	 * - user data is not empty,
	 * - password matches the user,
	 * - user role is checked on 2FA settings,
	 * - user has at least one 2FA auth method available.
	 *
	 * @param  null|WP_User|WP_Error $user  Object of the logged-in user.
	 * @param  string                $username  Username or email address.
	 * @param  string                $password  Plain password string.
	 */
	public function maybe_show_otp_form( $user, string $username, string $password ) {
		if (
			! is_user_logged_in()
			&& ! empty( $user ) && ! empty( $password ) && $user instanceof WP_User
			&& wp_check_password( $password, $user->data->user_pass, $user->ID )
			&& $this->service->is_auth_enable_for( $user, $this->model->user_roles )
			&& ! empty( $this->service->get_available_providers_for_user( $user ) )
		) {
			$params = array();
			$cookie = Array_Cache::get( 'auth_cookie', 'two_fa' );
			if ( null !== $cookie ) {
				// Clear all session data if any.
				$session = WP_Session_Tokens::get_instance( $user->ID );
				$session->destroy( $cookie['token'] );
			}
			// Prevent user to login, and show otp screen.
			wp_clear_auth_cookie();
			// All goods, we'll need to create a unique token to mark this user.
			$params['token']    = $this->get_token( $user->ID );
			$params['password'] = $password;
			$params['user_id']  = $user->ID;
			// Get default provider.
			$params['default_slug'] = $this->service->get_default_provider_slug_for_user( $user->ID );
			if ( Fallback_Email::$slug === $params['default_slug'] ) {
				$result = $this->service->send_otp_to_email( $params['token'], $user->ID );
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
	 * @param  array $params  Additional parameters.
	 *
	 * @return void|null
	 */
	private function render_otp_screen( array $params = array() ) {
		// Add common styles and scripts to enqueue.
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'defender-otp-screen', defender_asset_url( '/assets/css/otp.css' ), array(), DEFENDER_VERSION );

		$params['redirect_to'] = $this->redirect_url();
		if ( ! isset( $params['error'] ) ) {
			$params['error'] = null;
		}

		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$custom_graphic      = '';
		$custom_graphic_type = '';
		if ( $this->is_pro() && $this->model->custom_graphic ) {
			$custom_graphic_type = $this->model->custom_graphic_type;
			if ( Two_Fa::CUSTOM_GRAPHIC_TYPE_UPLOAD === $custom_graphic_type && ! empty( $this->model->custom_graphic_url ) ) {
				$custom_graphic = $this->model->custom_graphic_url;
			} elseif ( Two_Fa::CUSTOM_GRAPHIC_TYPE_LINK === $custom_graphic_type && ! empty( $this->model->custom_graphic_link ) ) {
				$custom_graphic = $this->model->custom_graphic_link;
			}
		}

		$params['custom_graphic']      = $custom_graphic;
		$params['custom_graphic_type'] = $custom_graphic_type;

		$collection = $this->dump_routes_and_nonces();
		$routes     = $collection['routes'];
		$nonces     = $collection['nonces'];

		$params['providers'] = array();
		$user                = null;
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

		$this->service->remove_actions_for_2fa_screen();

		if (
			isset( $params['providers'][ Webauthn::$slug ] ) &&
			false === $params['providers'][ Webauthn::$slug ]->is_otp_screen_available( $user )
		) {
			unset( $params['providers'][ Webauthn::$slug ] );
			$params['default_slug'] = Webauthn::$slug !== $params['default_slug'] ? $params['default_slug'] : null;
		}

		if ( 0 === count( $params['providers'] ) ) {
			// Since 3.5.0.
			$error_msg       = esc_html__( 'No providers.', 'wpdef' );
			$params['error'] = new WP_Error( 'opt_fail', $error_msg );
			do_action( 'wd_2fa_otp_params', $params );

			wp_die( esc_html( $error_msg ) );
		}
		// Add WebAuthn styles and scripts to enqueue.
		if ( true === array_key_exists( Webauthn::$slug, $params['providers'] ) ) {
			wp_enqueue_style(
				'defender-biometric-login-screen',
				defender_asset_url( '/assets/css/biometric.css' ),
				array(),
				DEFENDER_VERSION
			);
			wp_enqueue_script(
				'wpdef_webauthn_common_script',
				plugins_url( 'assets/js/webauthn-common.js', WP_DEFENDER_FILE ),
				array(),
				DEFENDER_VERSION,
				true
			);
			wp_enqueue_script(
				'defender-biometric-login-script',
				plugins_url( 'assets/js/biometric-login.js', WP_DEFENDER_FILE ),
				array(
					'jquery',
					'wpdef_webauthn_common_script',
				),
				DEFENDER_VERSION,
				true
			);
			$webauthn_controller = wd_di()->get( Webauthn_Controller::class );
			wp_localize_script(
				'defender-biometric-login-script',
				'webauthn',
				array(
					'admin_url'     => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'wpdef_webauthn' ),
					'i18n'          => $webauthn_controller->get_translations(),
					'username'      => ! empty( $user->user_login ) ? $user->user_login : '',
					'provider_slug' => Webauthn::$slug,
				)
			);
		}
		// Prepare data.
		$args = array(
			'action'     => defender_base_action(),
			'_def_nonce' => $nonces['send_backup_code'],
			// Add a dummy values to avoid displaying errors, e.g. for the case with null.
			'route'      => $this->check_route( $routes['send_backup_code'] ?? 'test' ),
		);
		// If user's session has expired add a new 'interimlogin'-arg.
		if ( ! empty( defender_get_data_from_request( 'interim-login', 'r' ) ) ) {
			$args['interimlogin'] = 'yes';
		}
		$params['action_fallback_email'] = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );

		// Since 3.5.0.
		do_action( 'wd_2fa_otp_params', $params );

		$this->render_partial( 'two-fa/otp', $params );
		exit;
	}

	/**
	 * Store the session key in the array cache for two-factor authentication.
	 *
	 * @param  string $cookie  The authentication cookie.
	 *
	 * @return void
	 */
	public function store_session_key( $cookie ): void {
		// Clear login cookie to ensure nonce consistency.
		if ( ! is_user_logged_in() && isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		}

		$cookie = wp_parse_auth_cookie( $cookie, 'logged_in' );
		Array_Cache::set( 'auth_cookie', $cookie, 'two_fa' );
	}

	/**
	 * Disable 2FA TOTP method for the current user. It's not from the list of routes.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function disable_totp(): Response {
		$user_id = get_current_user_id();
		// Remove TOTP flag.
		delete_user_meta( $user_id, Totp::TOTP_AUTH_KEY );
		// Remove old secret key.
		delete_user_meta( $user_id, Totp::TOTP_SECRET_KEY );
		// Remove new secret key.
		delete_user_meta( $user_id, Totp::TOTP_SODIUM_SECRET_KEY );
		// Remove TOTP from enabled providers.
		$enabled_providers = get_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, true );
		if ( ! empty( $enabled_providers ) ) {
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
		// Check the default provider. If it's TOTP then clear the value.
		$default_provider = get_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true );
		if ( ! empty( $default_provider ) && Totp::$slug === $default_provider ) {
			update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, '' );
		}

		return new Response( true, array() );
	}

	/**
	 * Verify the OTP and enable 2-factor authentication for the currently logged in user.
	 *
	 * @param  Request $request  The request object containing the OTP and setup key.
	 *
	 * @return Response A response object indicating the success or failure of the operation.
	 *                 If successful, the response does not contain any data.
	 *                 If failed, the response contains an error message.
	 * @defender_route
	 * @is_public
	 * @throws SodiumException Exceptions thrown by the sodium functions.
	 */
	public function verify_otp_for_enabling( Request $request ) {
		if ( is_user_logged_in() ) {
			$data = $request->get_data();
			$otp  = isset( $data['otp'] ) ? sanitize_text_field( $data['otp'] ) : false;
			if ( false === $otp || strlen( $otp ) < 6 ) {
				return new Response(
					false,
					array( 'message' => esc_html__( 'Please input a valid OTP code.', 'wpdef' ) )
				);
			}
			// Get the setup key.
			$setup_key = $data['setup_key'] ?? false;
			if ( ! $setup_key ) {
				return new Response(
					false,
					array( 'message' => esc_html__( 'The setup key is incorrect.', 'wpdef' ) )
				);
			}
			$user_id = get_current_user_id();
			$result  = TOTP::verify_otp( $otp, $user_id, $setup_key );
			// OTP result can be a boolean value or WP error.
			if ( is_wp_error( $result ) ) {
				return new Response(
					false,
					array( 'message' => $result->get_error_message() )
				);
			}
			if ( $result ) {
				// Save a setup key.
				$result = Totp::save_setup_key( $user_id, $setup_key );
				if ( is_wp_error( $result ) ) {
					return new Response(
						false,
						array( 'message' => $result->get_error_message() )
					);
				}
				// Enable OTP.
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
					update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, array( $totp_slug ) );
				}
				// If no default provider then add TOTP as it.
				$default_provider = get_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, true );
				if ( empty( $default_provider ) ) {
					update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, $totp_slug );
				}

				return new Response( true, array() );
			} else {
				return new Response(
					false,
					array( 'message' => esc_html__( 'Your OTP code is incorrect. Please try again.', 'wpdef' ) )
				);
			}
		}
	}

	/**
	 * Clear 2FA providers for the given user.
	 *
	 * @param  int $user_id  User ID.
	 */
	protected function clear_providers( int $user_id ): void {
		update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, '' );
		update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, '' );
	}

	/**
	 * Updates the user's 2FA profile with the selected providers and default provider.
	 *
	 * @param  int $user_id  The ID of the user whose profile is being updated.
	 *
	 * @return void
	 */
	public function profile_update( int $user_id ) {
		$post_data = defender_get_data_from_request( null, 'p' );
		if ( isset( $post_data['_wpdef_2fa_nonce_user_options'] ) ) {
			check_admin_referer( 'wpdef_2fa_user_options', '_wpdef_2fa_nonce_user_options' );

			if (
				! isset( $post_data[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ] )
				|| ! is_array( $post_data[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ] )
			) {
				return;
			}
			// Remove empty elements.
			$checked_providers = array_diff( $post_data[ Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY ], array( '' ) );
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
				$user_authenticators = wd_di()->get( Webauthn_Controller::class )->get_current_user_authenticators();
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
			$enabled_providers = array();
			foreach ( $providers as $slug => $provider ) {
				if ( in_array( $slug, $checked_providers, true ) && $provider->is_available_for_user( $user ) ) {
					$enabled_providers[] = $slug;
				}
			}
			update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );
			/**
			 * Fires when 2fa providers are enabled.
			 *
			 * @since 4.3.0
			 */
			do_action( 'wd_2fa_enabled_provider_slugs', $enabled_providers );
			// Default provider must be enabled.
			$default_provider = $post_data[ Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY ] ?? '';
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
	 * A simple filter to show activate 2fa screen on profile page.
	 *
	 * @param  WP_User $user  The current WP_User object.
	 *
	 * @return void
	 */
	public function show_user_profile( WP_User $user ): void {
		$user_roles = $this->get_roles( $user );
		// This method is better than is_intersected_arrays() because it is flexibly controlled with a nested hook.
		if ( ! empty( $user_roles ) && $this->service->is_auth_enable_for( $user, $this->model->user_roles ) ) {
			wp_enqueue_style( 'defender-profile-2fa', defender_asset_url( '/assets/css/two-factor.css' ), array(), DEFENDER_VERSION );

			$webauthn_controller   = wd_di()->get( Webauthn_Controller::class );
			$webauthn_requirements = $this->check_webauthn_requirements();
			if ( $this->service->is_checked_enabled_provider_by_slug(
				$user,
				Webauthn::$slug
			) && ! $webauthn_requirements ) {
				$this->service->remove_enabled_provider_for_user( Webauthn::$slug, $user );
			}

			$webauthn_user_handle_match_failed = wd_di()->get( Webauthn_Component::class )->getUserHandleMatchFailed( $user->ID );

			wp_enqueue_script(
				'wpdef_webauthn_common_script',
				plugins_url( 'assets/js/webauthn-common.js', WP_DEFENDER_FILE ),
				array(),
				DEFENDER_VERSION,
				true
			);
			wp_enqueue_script(
				'wpdef_webauthn_script',
				plugins_url( 'assets/js/webauthn.js', WP_DEFENDER_FILE ),
				array(
					'jquery',
					'wpdef_webauthn_common_script',
				),
				DEFENDER_VERSION,
				true
			);
			wp_localize_script(
				'wpdef_webauthn_script',
				'webauthn',
				array(
					'admin_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                    => wp_create_nonce( 'wpdef_webauthn' ),
					'i18n'                     => $webauthn_controller->get_translations(),
					'registered_auths'         => $webauthn_controller->get_current_user_authenticators(),
					'username'                 => ! empty( $user->user_login ) ? $user->user_login : '',
					'user_handle_match_failed' => $webauthn_user_handle_match_failed,
				)
			);

			$forced_auth            = $this->service->is_intersected_arrays(
				$user_roles,
				$this->model->force_auth_roles
			);
			$default_values         = $this->model->get_default_values();
			$enabled_providers      = $this->service->get_available_providers_for_user( $user );
			$enabled_provider_slugs = ! empty( $enabled_providers ) ? array_keys( $enabled_providers ) : array();
			$default_provider_slug  = $this->service->get_default_provider_slug_for_user( $user->ID );
			$webauthn_enabled       = $this->service->is_checked_enabled_provider_by_slug( $user, Webauthn::$slug );

			$this->render_partial(
				'two-fa/user-options',
				array(
					'is_force_auth'             => $forced_auth && $this->model->force_auth && empty( $enabled_providers ),
					'force_auth_message'        => $this->model->force_auth_mess,
					'default_message'           => $default_values['message'],
					'user'                      => $user,
					'all_providers'             => $this->service->get_providers(),
					'enabled_providers_key'     => Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY,
					'default_provider_key'      => Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY,
					'checked_provider_slugs'    => $enabled_provider_slugs,
					'checked_def_provider_slug' => ! empty( $default_provider_slug ) ? $default_provider_slug : null,
					'webauthn_requirements'     => $webauthn_requirements,
					'webauthn_enabled'          => $webauthn_enabled,
					'webauthn_slug'             => Webauthn::$slug,
					'is_admin'                  => is_admin(),
				)
			);
		}
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
						'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array( 'message' => $model->get_formatted_errors() )
		);
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
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
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function send_test_email( Request $request ): Response {
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
		$body    = $this->render_partial(
			'email/2fa-lost-phone',
			array(
				'body' => $data['email_body'],
			),
			false
		);

		$params = array(
			'passcode'     => '[a-sample-passcode]',
			'display_name' => $this->get_user_display( get_current_user_id() ),
		);

		foreach ( $params as $key => $param ) {
			if ( 'passcode' === $key ) {
				$body = str_replace( "{{{$key}}}", '<span class="defender-otp">' . $param . '</span>', $body );
			} else {
				$body = str_replace( "{{{$key}}}", $param, $body );
			}
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $sender ) ) {
			// Since v5.2.0.
			$from_email = defender_noreply_email( 'wd_two_fa_totp_noreply_email' );
			$headers[]  = sprintf( 'From: %s <%s>', $sender, $from_email );
		} else {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Sender value cannot be empty.', 'wpdef' ) )
			);
		}
		// Main email template.
		$body = $this->render_partial(
			'email/index',
			array(
				'title'            => Two_Fa::get_module_name(),
				'content_body'     => $body,
				// An empty value because 2FA-email is sent after a manual click from the user.
				'unsubscribe_link' => '',
			),
			false
		);

		$send_mail = wp_mail( Fallback_Email::get_backup_email(), $subject, $body, $headers );
		if ( $send_mail ) {
			return new Response(
				true,
				array( 'message' => esc_html__( 'Test email has been sent to your email.', 'wpdef' ) )
			);
		} else {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Test email failed.', 'wpdef' ) )
			);
		}
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		$settings            = new Two_Fa();
		[ $routes, $nonces ] = Route::export_routes( 'two_fa' );

		return array(
			'enabled'   => $settings->enabled,
			'useable'   => $settings->enabled && count( $settings->user_roles ),
			'nonces'    => $nonces,
			'endpoints' => $routes,
		);
	}

	/**
	 * Renders the main view.
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
		( new Two_Fa() )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		global $wpdb;

		$keys  = array(
			Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY,
			Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY,
			// From Totp.
			'wd_2fa_attempt_' . TOTP::$slug,
			TOTP::TOTP_AUTH_KEY,
			// For backward compatible with the def.key file. We'll remove this key in future versions and use the key for Sodium.
			TOTP::TOTP_SECRET_KEY,
			TOTP::TOTP_SODIUM_SECRET_KEY,
			TOTP::TOTP_FORCE_KEY,
			// From Backup_Codes.
			'wd_2fa_attempt_' . Backup_Codes::$slug,
			Backup_Codes::BACKUP_CODE_START,
			Backup_Codes::BACKUP_CODE_VALUES,
			// From Fallback_Email.
			'wd_2fa_attempt_' . Fallback_Email::$slug,
			Fallback_Email::FALLBACK_EMAIL_KEY,
			Fallback_Email::FALLBACK_BACKUP_CODE_KEY,
		);
		$sql   = "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (" . implode(
			',',
			array_fill( 0, count( $keys ), '%s' )
		) . ');';
		$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $keys ) );
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		// From Webauthn.
		wd_di()->get( Webauthn_Controller::class )->remove_data();
		// Check if 2fa file exists.
		$file = $this->get_2fa_lock_path();
		if ( is_file( $file ) && is_readable( $file ) ) {
			// Delete 2fa file. It's actual for prev v3.3.1.
			wp_delete_file( $file );
		}
		// Check if the file with a random key exists.
		$file = Crypt::get_path_to_key_file();
		if ( is_file( $file ) && is_readable( $file ) ) {
			wp_delete_file( $file );
		}
		// Remove cached data.
		Array_Cache::remove( 'auth_cookie', 'two_fa' );
		Array_Cache::remove( 'providers', 'two_fa' );
	}

	/**
	 * Filters users by 2FA option.
	 *
	 * @param  WP_User_Query $query  The user query object.
	 *
	 * @return void
	 */
	public function filter_users_by_2fa( $query ): void {
		global $pagenow;

		$is_enabled = defender_get_data_from_request( 'wpdef_two_fa', 'g' );
		if ( is_admin() && 'users.php' === $pagenow && 'enabled' === $is_enabled ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY,
						'value'   => array_keys( $this->service->get_providers() ),
						'compare' => 'IN',
					),
				)
			);
		}
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array_merge(
			array(
				'model'               => $this->model->export(),
				'all_roles'           => $this->get_all_editable_roles(),
				'count'               => $this->service->count_users_with_enabled_2fa(),
				'notices'             => $this->compatibility_notices,
				'count_checked_roles' => count( $this->model->user_roles ),
				'is_woo_active'       => $this->is_woo_activated,
				// The multisite check is an isolated case now. If it will be needed for several modules, then a more global scope is needed.
				'is_multisite'        => is_multisite(),
				'module_name'         => Two_Fa::get_module_name(),
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
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		$settings = new Two_Fa();

		return array(
			$settings->enabled ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Generates configuration strings based on the provided configuration and
	 * whether the product is a pro version.
	 *
	 * @param  array $config  Configuration data.
	 * @param  bool  $is_pro  Indicates if the product is a pro version.
	 *
	 * @return array Returns an array of configuration strings.
	 */
	public function config_strings( array $config, bool $is_pro ): array {
		return array(
			$config['enabled'] ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * WooCommerce prevents any user who cannot 'edit_posts' (subscribers, customers etc.) from accessing admin.
	 * Here we are disabling WooCommerce default behavior, if force 2FA is enabled.
	 *
	 * @param  bool $prevent  Prevent admin access.
	 *
	 * @return bool|null
	 */
	public function handle_woocommerce_prevent_admin_access( bool $prevent ) {
		$user = $this->current_user;
		if ( ! is_object( $user ) ) {
			return;
		}
		// Is User role from common list checked?
		if ( false === $this->service->is_auth_enable_for( $user, $this->model->user_roles ) ) {
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
		add_filter(
			'woocommerce_prevent_admin_access',
			array(
				$this,
				'handle_woocommerce_prevent_admin_access',
			),
			10,
			1
		);
		// Handle WooCommerce MyAccount page login redirect.
		add_filter( 'woocommerce_login_redirect', array( $this, 'handle_woocommerce_login_redirect' ), 10, 2 );
		// Add field.
		add_action( 'woocommerce_login_form_end', array( $this, 'add_redirect_to_input' ) );
	}

	/**
	 * WooCommerce by default redirect users to My-account page.
	 * Here we are checking force 2FA is enabled or not.
	 *
	 * @param  string  $redirect  Redirect URL.
	 * @param  WP_User $user  Logged-in user.
	 *
	 * @return string
	 */
	public function handle_woocommerce_login_redirect( string $redirect, WP_User $user ) {
		// Is User role from common list checked?
		if ( false === $this->service->is_auth_enable_for( $user, $this->model->user_roles ) ) {
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
		echo '<input type="hidden" name="redirect_to" value="' . esc_url_raw( defender_get_request_url() ) . '">';
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
			array(
				'codes'       => Backup_Codes::generate_codes( $user ),
				'count'       => Backup_Codes::display_number_of_codes( Backup_Codes::get_unused_codes_for_user( $user ) ),
				'title'       => sprintf(
				/* translators: %s: count */
					esc_html__( '2FA Backup Codes for %s:', 'wpdef' ),
					get_bloginfo( 'url' )
				),
				'button_text' => esc_html__( 'Get New Codes', 'wpdef' ),
				'description' => esc_html__( 'Each backup code can only be used to log in once.', 'wpdef' ),
			)
		);
	}

	/**
	 * Shortcode to display 2FA user settings.
	 *
	 * @return void
	 * @since 3.2.0
	 */
	public function display_2fa_user_settings(): void {
		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) ) {
			wp_enqueue_script( 'wp-i18n' );

			do_action( 'wd_2fa_form_before' );

			echo '<form class="wpdef-2fa-wrap" action="" method="post">';

			$this->show_user_profile( $this->current_user );

			echo '<input type="hidden" name="action" value="save_def_2fa_user_settings" />';
			echo '<button type="submit" class="button" name="save_def_2fa_user_settings" value="' . esc_attr__(
				'Save changes',
				'wpdef'
			) . '">'
				. esc_html__( 'Save changes', 'wpdef' ) . '</button>';
			echo '</form>';

			do_action( 'wd_2fa_form_after' );
		} else {
			apply_filters( 'wd_2fa_form_when_not_logged_in', '' );
		}
	}

	/**
	 * 1. Register new endpoint (URL) for My Account page. Re-save Permalinks or it will give 404 error.
	 *
	 * @return void
	 */
	public function wp_defender_2fa_endpoint(): void {
		add_rewrite_endpoint( $this->slug, EP_PERMALINK | EP_PAGES );
		flush_rewrite_rules();
	}

	/**
	 * 2. dds the slug of the current instance to the given array of query variables.
	 *
	 * @param  array $vars  The array of query variables.
	 *
	 * @return array The updated array of query variables.
	 */
	public function wp_defender_2fa_query_vars( $vars ) {
		$vars[] = $this->slug;

		return $vars;
	}

	/**
	 * 3. Inserts the new endpoint into the My Account menu.
	 *
	 * @param  array $items  The array of items in the My Account menu.
	 *
	 * @return array The updated array of items with the new endpoint inserted.
	 */
	public function wp_defender_2fa_link_my_account( $items ) {
		$needed_place = is_array( $items ) && ! empty( $items ) ? ( count( $items ) - 1 ) : 0;

		return array_slice( $items, 0, $needed_place, true )
				+ array( $this->slug => esc_html__( '2FA', 'wpdef' ) )
				+ array_slice( $items, $needed_place, null, true );
	}

	/**
	 * 4. Add content to the new tab.
	 *
	 * @return void
	 */
	public function wp_defender_2fa_content(): void {
		echo do_shortcode( '[wp_defender_2fa_user_settings]' );
	}

	/**
	 * Save the 2fa details and redirect back to 'My Account' page.
	 *
	 * @return void
	 */
	public function save_2fa_details() {
		$action = defender_get_data_from_request( 'action', 'p' );
		if ( empty( $action ) || 'save_def_2fa_user_settings' !== $action ) {
			return;
		}

		wc_nocache_headers();

		$user_id = $this->current_user->ID;
		if ( $user_id <= 0 ) {
			return;
		}
		// Verify nonce and other two-factor arguments passed.
		$this->profile_update( $user_id );

		wc_add_notice( esc_html__( 'Two-Factor settings updated successfully.', 'wpdef' ) );
		// @since 3.2.0
		do_action( 'wd_woocommerce_save_2fa_details', $user_id );

		wp_safe_redirect( wc_get_endpoint_url( $this->slug, '', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Enable provider slugs.
	 *
	 * @param  array $provider_slugs  The array of provider slugs to enable.
	 *
	 * @return void
	 */
	public function enable_provider_slugs( array $provider_slugs ) {
		// Track conditions.
		if ( ! empty( $provider_slugs ) ) {
			$methods = array();
			foreach ( $this->service->get_providers() as $slug => $object ) {
				if ( in_array( $slug, $provider_slugs, true ) ) {
					$methods[] = $object->get_label();
				}
			}
			// Run track.
			$this->track_feature(
				'def_2fa_method_activated',
				array(
					'Method name' => $methods,
				)
			);
		}
	}
}