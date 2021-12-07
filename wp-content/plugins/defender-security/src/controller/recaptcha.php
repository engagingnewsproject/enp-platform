<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Traits\Hummingbird;
use WP_Error;
use WP_User;

/**
 * Class Recaptcha
 *
 * @package WP_Defender\Controller
 * @since 2.5.4
 */
class Recaptcha extends \WP_Defender\Controller2 {
	const DEFAULT_LOGIN_FORM       = 'login',
		DEFAULT_REGISTER_FORM      = 'register',
		DEFAULT_LOST_PASSWORD_FORM = 'lost_password',
		DEFAULT_COMMENT_FORM       = 'comments';

	const WOO_LOGIN_FORM       = 'woo_login',
		WOO_REGISTER_FORM      = 'woo_register',
		WOO_LOST_PASSWORD_FORM = 'woo_lost_password';

	use Hummingbird;

	/**
	 * Accepted values: v2_checkbox, v2_invisible, v3_recaptcha.
	 * @var string
	 */
	private $recaptcha_type;

	/**
	 * Accepted values: light and dark.
	 * @var string
	 */
	private $recaptcha_theme;

	/**
	 * Accepted values: normal and compact.
	 * @var string
	 */
	private $recaptcha_size;

	/**
	 * @var string
	 */
	private $public_key;

	/**
	 * @var string
	 */
	private $private_key;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $default_msg;

	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\Recaptcha
	 */
	public $model;

	/**
	 * @var bool
	 */
	private $is_woo_activated;

	public function __construct() {
		$this->model       = $this->get_model();
		// Use default msg to avoid empty message error.
		$default_values    = $this->model->get_default_values();
		$this->default_msg = $default_values['message'];
		$this->register_routes();
		$this->is_woo_activated = wd_di()->get( \WP_Defender\Integrations\Woocommerce::class )->is_activated();
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );

		if (
			$this->model->is_active()
			// No need the check by is_woo_activated because we use this below.
			&& ( $this->model->enable_default_locations() || $this->model->enable_woo_locations() )
			&& ! $this->exclude_recaptcha_for_requests()
		) {
			$this->declare_variables();
			$this->add_actions();

			add_filter( 'script_loader_tag', array( $this, 'script_loader_tag' ), 10, 2 );
		}
	}

	protected function declare_variables() {
		$this->recaptcha_type  = $this->model->active_type;
		$this->recaptcha_theme = 'light';
		$this->recaptcha_size  = 'invisible';
		$this->language        = ! empty( $this->model->language ) && 'automatic' !== $this->model->language
			? $this->model->language
			: get_locale();

		// Add the reCAPTCHA keys depending on the reCAPTCHA type.
		if ( 'v2_checkbox' === $this->recaptcha_type ) {
			$this->public_key      = $this->model->data_v2_checkbox['key'];
			$this->private_key     = $this->model->data_v2_checkbox['secret'];
			$this->recaptcha_theme = $this->model->data_v2_checkbox['style'];
			$this->recaptcha_size  = $this->model->data_v2_checkbox['size'];
		} elseif ( 'v2_invisible' === $this->recaptcha_type ) {
			$this->public_key  = $this->model->data_v2_invisible['key'];
			$this->private_key = $this->model->data_v2_invisible['secret'];
		} elseif ( 'v3_recaptcha' === $this->recaptcha_type ) {
			$this->public_key  = $this->model->data_v3_recaptcha['key'];
			$this->private_key = $this->model->data_v3_recaptcha['secret'];
		}
	}

	protected function add_actions() {
		$extra_conditions = is_admin() && ! defined( 'DOING_AJAX' );
		// @since 2.5.6
		do_action( 'wd_recaptcha_before_actions', $extra_conditions );
		if ( $extra_conditions ) {
			return;
		}
		$is_user_logged_in = is_user_logged_in();
		$locations         = $this->model->locations;
		// Default login form.
		if ( in_array( self::DEFAULT_LOGIN_FORM, $locations, true ) ) {
			add_action( 'login_form', array( $this, 'display_login_recaptcha' ) );
			add_filter( 'wp_authenticate_user', array( $this, 'validate_captcha_field_on_login' ), 8 );
		}
		// Default register form.
		if ( in_array( self::DEFAULT_REGISTER_FORM, $locations, true ) ) {
			if ( ! is_multisite() ) {
				add_action( 'register_form', array( $this, 'display_login_recaptcha' ) );
				add_filter( 'registration_errors', array( $this, 'validate_captcha_field_on_registration' ), 10 );
			} else {
				add_action( 'signup_extra_fields', array( $this, 'display_signup_recaptcha' ) );
				add_action( 'signup_blogform', array( $this, 'display_signup_recaptcha' ) );
				add_filter( 'wpmu_validate_user_signup', array( $this, 'validate_captcha_field_on_wpmu_registration' ), 10 );
			}
		}
		// Default lost password form.
		if ( in_array( self::DEFAULT_LOST_PASSWORD_FORM, $locations, true ) ) {
			add_action( 'lostpassword_form', array( $this, 'display_login_recaptcha' ) );
			if ( ! $this->is_woocommerce_page() ) {
				add_action( 'lostpassword_post', array( $this, 'validate_captcha_field_on_lostpassword' ) );
			}
		}
		// For Woo forms. Mandatory check for the activated Woo before.
		if ( $this->is_woo_activated ) {
			$woo_locations = $this->model->woo_checked_locations;
			// Woo login form.
			if ( in_array( self::WOO_LOGIN_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_login_form', array( $this, 'display_login_recaptcha' ) );
				add_filter( 'woocommerce_process_login_errors', array( $this, 'validate_captcha_field_on_woo_login' ), 10 );
			}
			// Woo register form.
			if ( in_array( self::WOO_REGISTER_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_register_form', array( $this, 'display_login_recaptcha' ) );
				add_filter( 'woocommerce_registration_errors', array( $this, 'validate_captcha_field_on_woo_registration' ), 10 );
			}
			// Woo lost password form.
			if ( in_array( self::WOO_LOST_PASSWORD_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_lostpassword_form', array( $this, 'display_login_recaptcha' ) );
				// Use default WP hook because Woo doesn't have own hook, so there's the extra check for Woo form.
				if ( isset( $_POST['wc_reset_password'], $_POST['user_login'] ) ) {
					add_action( 'lostpassword_post', array( $this, 'validate_captcha_field_on_lostpassword' ) );
				}
			}
		}
		// Default comment form.
		if ( ! $is_user_logged_in && in_array( self::DEFAULT_COMMENT_FORM, $locations, true ) ) {
			add_action( 'comment_form_after_fields', array( $this, 'display_comment_recaptcha' ) );
			add_action( 'pre_comment_on_post', array( $this, 'validate_captcha_field_on_comment' ) );
			// When comments are loaded via Hummingbird's lazy load feature.
			if ( $this->is_lazy_load_comments_enabled() ) {
				add_action( 'wp_footer', array( $this, 'add_scripts_for_lazy_load' ) );
			}
		}
		// @since 2.5.6
		do_action( 'wd_recaptcha_after_actions', $is_user_logged_in );
	}

	/**
	 * Add the async and defer tag.
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public function script_loader_tag( $tag, $handle ) {
		if ( 'wpdef_recaptcha_api' === $handle ) {
			$tag = str_replace( ' src', ' data-cfasync="false" async="async" defer="defer" src', $tag );
		}
		return $tag;
	}

	/**
	 * @return string
	 */
	protected function get_api_url() {
		if ( isset( $this->recaptcha_type ) &&
			in_array( $this->recaptcha_type, array( 'v2_checkbox', 'v2_invisible' ), true )
		) {
			$api_url = sprintf( 'https://www.google.com/recaptcha/api.js?hl=%s&render=explicit', $this->language );
		}
		if ( isset( $this->recaptcha_type ) && 'v3_recaptcha' === $this->recaptcha_type ) {
			$api_url = sprintf( 'https://www.google.com/recaptcha/api.js?hl=%s&render=%s', $this->language, $this->public_key );
		}
		return $api_url;
	}

	protected function remove_dublicate_scripts() {
		global $wp_scripts;

		if ( ! is_object( $wp_scripts ) || empty( $wp_scripts ) ) {
			return false;
		}

		foreach ( $wp_scripts->registered as $script_name => $args ) {
			if ( preg_match( '|google\.com/recaptcha/api\.js|', $args->src ) && 'wpdef_recaptcha_api' !== $script_name ) {
				// Remove a previously enqueued script.
				wp_dequeue_script( $script_name );
			}
		}
	}

	public function add_scripts() {
		if ( isset( $this->recaptcha_type ) ) {
			$this->remove_dublicate_scripts();
		}

		wp_enqueue_script( 'wpdef_recaptcha_script', plugins_url( 'assets/js/recaptcha_frontend.js', WP_DEFENDER_FILE ), array( 'jquery', 'wpdef_recaptcha_api' ), DEFENDER_VERSION, true );
		// @since 2.5.6
		do_action( 'wd_recaptcha_extra_assets' );

		$error_text = __( 'More than one reCAPTCHA has been found in the current form. Please remove all unnecessary reCAPTCHA fields to make it work properly.', 'wpdef' );
		$options    = array(
			'hl'      => $this->language,
			'size'    => $this->recaptcha_size,
			'version' => $this->recaptcha_type,
			'sitekey' => $this->public_key,
			'error'   => sprintf( '<strong>%s</strong>:&nbsp;%s', __( 'Warning', 'wpdef' ), $error_text ),
			// For default comment form.
			'disable' => '',
		);

		if ( 'v2_checkbox' === $this->recaptcha_type ) {
			$options['theme'] = $this->recaptcha_theme;
		}

		wp_localize_script(
			'wpdef_recaptcha_script',
			'WPDEF',
			array(
				'options' => $options,
				'vars'    => array(
					'visibility' => ( 'login_footer' === current_filter() ),
				),
			)
		);
	}

	/**
	 * Add scripts when comments are lazy loaded.
	 *
	 * @since 2.6.1
	 */
	public function add_scripts_for_lazy_load() {
		if (
			in_array( $this->recaptcha_type, array( 'v2_checkbox', 'v2_invisible' ), true )
			&& ( is_single() || is_page() )
			&& comments_open()
		) {
			if ( ! wp_script_is( 'wpdef_recaptcha_api', 'registered' ) ) {
				$api_url = $this->get_api_url();
				$deps    = array( 'jquery' );
				wp_register_script( 'wpdef_recaptcha_api', $api_url, $deps, DEFENDER_VERSION, true );
			}

			$this->add_scripts();
		}
	}

	/**
	 * Display the reCAPTCHA field.
	 */
	public function display_login_recaptcha() {
		if ( 'v2_checkbox' === $this->recaptcha_type ) {
				$from_width = 302; ?>
				<style media="screen">
					.login-action-login #loginform,
					.login-action-lostpassword #lostpasswordform,
					.login-action-register #registerform {
						width: <?php echo $from_width; ?>px !important;
					}
					#login_error,
					.message {
						width: <?php echo $from_width + 20; ?>px !important;
					}
					.login-action-login #loginform .recaptcha_wrap,
					.login-action-lostpassword #lostpasswordform .recaptcha_wrap,
					.login-action-register #registerform .recaptcha_wrap {
						margin-bottom: 10px;
					}
				</style>
			<?php
		} elseif ( 'v2_invisible' === $this->recaptcha_type ) {
			?>
			<style>
				.login-action-lostpassword #lostpasswordform .recaptcha_wrap, {
					margin-bottom: 10px;
				}
				#signup-content .recaptcha_wrap {
					margin-top: 10px;
				}
			</style>
			<?php
		}
		echo $this->display_recaptcha();
	}

	/**
	 * @since 2.5.6
	 * @return bool
	 */
	protected function exclude_recaptcha_for_requests() {
		$current_request   = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$excluded_requests = (array) apply_filters( 'wd_recaptcha_excluded_requests', array() );

		return in_array( $current_request, $excluded_requests, true );
	}

	/**
	 * Display the output of the recaptcha.
	 *
	 * @return string
	 */
	protected function display_recaptcha() {
		$content = '<div class="recaptcha_wrap wpdef_recaptcha_' . $this->recaptcha_type . '">';
		if ( ! $this->private_key || ! $this->public_key || empty( $this->recaptcha_type ) ) {
			// Display nothing.
			$content .= '</div>';

			return $content;
		}

		$api_url = $this->get_api_url();

		// Generate random id value if there's content with pagination plugin for not getting duplicate id values.
		$id = mt_rand();
		if ( in_array( $this->recaptcha_type, array( 'v2_checkbox', 'v2_invisible' ), true ) ) {
			$content .= '<div id="wpdef_recaptcha_' . $id . '" class="wpdef_recaptcha"></div>
			<noscript>
				<div style="width: 302px;">
					<div style="width: 302px; height: 422px; position: relative;">
						<div style="width: 302px; height: 422px; position: absolute;">
							<iframe src="https://www.google.com/recaptcha/api/fallback?k=' . $this->public_key . '" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;"></iframe>
						</div>
					</div>
					<div style="border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px; height: 60px; width: 300px;">
						<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px !important; height: 40px !important; border: 1px solid #c1c1c1 !important; margin: 10px 25px !important; padding: 0px !important; resize: none !important;"></textarea>
					</div>
				</div>
			</noscript>';
			$deps     = array( 'jquery' );
		} elseif ( 'v3_recaptcha' === $this->recaptcha_type ) {
			$content .= '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />';
		}
		$content .= '</div>';

		// Register reCAPTCHA script.
		$locations = $this->model->locations;
		if ( ! wp_script_is( 'wpdef_recaptcha_api', 'registered' ) ) {

			if ( 'v3_recaptcha' === $this->recaptcha_type ) {
				wp_register_script( 'wpdef_recaptcha_api', $api_url, false, null, false );
			} else {
				wp_register_script( 'wpdef_recaptcha_api', $api_url, $deps, DEFENDER_VERSION, true );
			}
			add_action( 'wp_footer', array( $this, 'add_scripts' ) );
			if (
				in_array( self::DEFAULT_LOGIN_FORM, $locations, true )
				|| in_array( self::DEFAULT_REGISTER_FORM, $locations, true )
				|| in_array( self::DEFAULT_LOST_PASSWORD_FORM, $locations, true )
			) {
				add_action( 'login_footer', array( $this, 'add_scripts' ) );
			}
		}

		return $content;
	}

	/**
	 * Check the current page from is from the Woo plugin.
	 *
	 * @retun bool
	 */
	protected function is_woocommerce_page() {
		if ( ! $this->is_woo_activated ) {
			return false;
		}

		$traces = debug_backtrace();
		foreach ( $traces as $trace ) {
			if ( isset( $trace['file'] ) && false !== strpos( $trace['file'], 'woocommerce' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Verify the recaptcha code on the Login page.
	 *
	 * @param WP_User|WP_Error $user
	 *
	 * @return WP_Error|WP_User
	 */
	public function validate_captcha_field_on_login( $user ) {
		if ( $this->is_woocommerce_page() ) {
			return $user;
		}
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $user;
		}

		if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
			return $user;
		}

		if ( ! $this->recaptcha_response( 'default_login' ) ) {
			if ( is_wp_error( $user ) ) {
				$user->add( 'invalid_captcha', $this->error_message() );
				return $user;
			} else {
				return new WP_Error( 'invalid_captcha', $this->error_message() );
			}
		}

		return $user;
	}

	/**
	 * Verify the recaptcha code on the Registration page.
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_registration( $errors ) {
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $errors;
		}

		if ( ! $this->recaptcha_response( 'default_registration' ) ) {
			$errors->add( 'invalid_captcha', $this->error_message() );
		}
		$_POST['g-recaptcha-response-check'] = true;

		return $errors;
	}

	/**
	 * Add google recaptcha to the multisite signup form.
	 *
	 * @param WP_Error $errors
	 */
	public function display_signup_recaptcha( $errors ) {
		$error_message = $errors->get_error_message( 'invalid_captcha' );
		if ( ! empty( $error_message ) ) {
			printf( '<p class="error">%s</p>', $error_message );
		}
		echo $this->display_recaptcha();
	}

	/**
	 * Verify the recaptcha code on the multisite signup page.
	 *
	 * @param array $result
	 *
	 * @return array|WP_user
	 */
	public function validate_captcha_field_on_wpmu_registration( $result ) {
		global $current_user;
		if ( is_admin() && ! defined( 'DOING_AJAX' ) && ! empty( $current_user->data->ID ) ) {
			return $result;
		}

		if ( ! $this->recaptcha_response( 'wpmu_registration' ) ) {
			if ( isset( $result['errors'] ) && ! empty( $result['errors'] ) ) {
				$errors = $result['errors'];
			} else {
				$errors = new WP_Error();
			}

			$errors->add( 'invalid_captcha', $this->error_message() );
			$result['errors'] = $errors;
			return $result;
		}

		return $result;
	}

	/**
	 * Verify the recaptcha code on Woo login page.
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_woo_login( $errors ) {
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $errors;
		}

		if ( ! $this->recaptcha_response( 'woo_login' ) ) {
			// Remove 'Error: ' because Woo has it by default.
			$message = str_replace( __( '<strong>Error:</strong> ', 'wpdef' ), '', $this->error_message() );
			$errors->add( 'invalid_captcha', $message );
		}

		return $errors;
	}

	/**
	 * Check recaptcha on Woo registration form.
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_woo_registration( $errors ) {
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return $errors;
		}
		if ( ! $this->recaptcha_response( 'woo_registration' ) ) {
			// Remove 'Error: ' because Woo has it by default.
			$message = str_replace( __( '<strong>Error:</strong> ', 'wpdef' ), '', $this->error_message() );
			$errors->add( 'invalid_captcha', $message );
		}

		return $errors;
	}

	/**
	 * Fires before errors are returned from a password reset request.
	 * Without 2nd `$user_data` parameter because it's since WP 5.4.0.
	 *
	 * @param WP_Error $errors
	 */
	public function validate_captcha_field_on_lostpassword( $errors ) {
		if ( ! $this->recaptcha_response( 'default_lost_password' ) ) {
			$errors->add( 'invalid_captcha', $this->error_message() );
		}
	}

	/**
	 * Add google recaptcha to the comment form.
	 */
	public function display_comment_recaptcha() {
		echo '<style>#commentform .recaptcha_wrap {margin: 0 0 10px;}</style>';
		echo $this->display_recaptcha();
		return true;
	}

	/**
	 * Check JS enabled for comment form.
	 *
	 * @return void
	 */
	public function validate_captcha_field_on_comment() {
		if ( $this->exclude_recaptcha_for_requests() ) {
			return;
		}

		if ( ! $this->recaptcha_response( 'default_comments' ) ) {
			// @since v2.5.6
			wp_die( (string) apply_filters( 'wd_recaptcha_require_valid_comment', $this->error_message() ) );
		}
	}

	/**
	 * @return \WP_Defender\Model\Setting\Recaptcha
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Recaptcha();
	}

	/**
	 * Get the reCAPTCHA API response.
	 *
	 * @param string $form
	 *
	 * @return bool
	 */
	protected function recaptcha_response( $form ) {
		if ( empty( $this->private_key ) || empty( $_POST['g-recaptcha-response'] ) ) {
			return false;
		}
		// reCAPTCHA response post data.
		$response  = stripslashes( sanitize_text_field( $_POST['g-recaptcha-response'] ) );
		$remote_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
		// @since v2.5.6
		$remote_ip = (string) apply_filters( 'wd_recaptcha_remote_ip', $remote_ip );

		$post_body = array(
			'secret'   => $this->private_key,
			'response' => $response,
			'remoteip' => $remote_ip,
		);

		$result = $this->recaptcha_post_request( $post_body );
		// @since 2.5.6
		return apply_filters( 'wd_recaptcha_check_result', $result, $form );
	}

	/**
	 * Display custom error message.
	 *
	 * @return string
	 */
	private function error_message() {

		return sprintf(
		/* translators: ... */
			__( '<strong>Error:</strong> %s', 'wpdef' ),
			empty( $this->model->message ) ? $this->default_msg : $this->model->message
		);
	}

	/**
	 * Send HTTP POST request and return the response.
	 * Also initialize the error text if an error response is received.
	 *
	 * @param array $post_body - HTTP POST body
	 *
	 * @return bool
	 */
	protected function recaptcha_post_request( $post_body ) {
		$args    = array(
			'body'      => $post_body,
			'sslverify' => false,
		);
		$url     = 'https://www.google.com/recaptcha/api/siteverify';
		$request = wp_remote_post( $url, $args );
		// Get the request response body
		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $request );
		$response_keys = json_decode( $response_body, true );
		if ( 'v3_recaptcha' === $this->recaptcha_type ) {
			if (
				$response_keys['success']
				&& isset( $this->model->data_v3_recaptcha['threshold'], $response_keys['score'] )
				&& is_numeric( $this->model->data_v3_recaptcha['threshold'] )
				&& is_numeric( $response_keys['score'] )
			) {
				$is_success = $response_keys['score'] >= (float) $this->model->data_v3_recaptcha['threshold'];
			} else {
				$is_success = false;
			}
		} else {
			$is_success = (bool) $response_keys['success'];
		}

		return $is_success;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function script_data( $data ) {
		$data['recaptcha'] = $this->data_frontend();

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
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message' => __( 'Google reCAPTCHA settings saved successfully.', 'wpdef' ),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			// Merge stored data to avoid errors.
			array_merge( array( 'message' => $this->model->get_formatted_errors() ), $this->data_frontend() )
		);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function load_recaptcha_preview( Request $request ) {
		$data                 = $request->get_data(
			array(
				'captcha_type' => array(
					'type' => 'string',
				),
			)
		);
		$this->recaptcha_type = $data['captcha_type'];

		$model    = $this->get_model();
		$language = ! empty( $model->language ) && 'automatic' !== $model->language ? $model->language : get_locale();

		$notice  = '<div class="sui-notice sui-notice-default">';
		$notice .= '<div class="sui-notice-content">';
		$notice .= '<div class="sui-notice-message">';
		$notice .= '<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>';
		$notice .= '<p>' . esc_html__( 'Save your API keys to load the reCAPTCHA preview.', 'wpdef' ) . '</p>';
		$notice .= '</div>';
		$notice .= '</div>';
		$notice .= '</div>';

		$theme        = 'light';
		$captcha_size = 'invisible';
		$data_args    = '';
		if ( 'v2_checkbox' === $this->recaptcha_type ) {
			$this->public_key = $model->data_v2_checkbox['key'];
			$theme            = $model->data_v2_checkbox['style'];
			$captcha_size     = $model->data_v2_checkbox['size'];
			$onload           = 'defender_render_admin_captcha_v2';
			// Onload method for Recaptcha works only for js 'var'.
			$js = "<script>var defender_render_admin_captcha_v2 = function () {
			setTimeout( function () {
			var captcha_v2 = jQuery( '.defender-g-recaptcha-v2_checkbox' ),
				sitekey_v2 = captcha_v2.data('sitekey'),
				theme_v2 = captcha_v2.data('theme'),
				size_v2 = captcha_v2.data('size')
			;
			window.grecaptcha.render( captcha_v2[0], {
				sitekey: sitekey_v2,
				theme: theme_v2,
				size: size_v2,
				'error-callback': function() {
					jQuery('#v2_checkbox_notice_1').hide();
					jQuery('#v2_checkbox_notice_2').show();
				}
			} );
			}, 100 );
			};</script>";
		} elseif ( 'v2_invisible' === $this->recaptcha_type ) {
			$this->public_key = $model->data_v2_invisible['key'];
			$onload           = 'defender_render_admin_captcha_v2_invisible';
			$data_args        = 'data-badge="inline" data-callback="setResponse"';
			$js               = "<script>var defender_render_admin_captcha_v2_invisible = function () {
			setTimeout( function () {
				var captcha = jQuery( '.defender-g-recaptcha-v2_invisible' ),
					sitekey = captcha.data('sitekey'),
					theme = captcha.data('theme'),
					size = captcha.data('size')
				;
				window.grecaptcha.render( captcha[0], {
					sitekey: sitekey,
					theme: theme,
					size: size,
					badge: 'inline',
					'error-callback': function() {
						jQuery('#v2_invisible_notice_1').hide();
						jQuery('#v2_invisible_notice_2').show();
					}
				} );
			}, 100 );
			};</script>";
		} elseif ( 'v3_recaptcha' === $this->recaptcha_type ) {
			$this->public_key = $model->data_v3_recaptcha['key'];
			$onload           = 'defender_render_admin_captcha_v3';
			$js               = "<script>var defender_render_admin_captcha_v3 = function () {
			setTimeout( function () {
				var captcha = jQuery( '.defender-g-recaptcha-v3_recaptcha' ),
					sitekey = captcha.data('sitekey'),
					theme = captcha.data('theme'),
					size = captcha.data('size')
				;
				window.grecaptcha.render( captcha[0], {
					sitekey: sitekey,
					theme: theme,
					size: size,
					badge: 'inline',
					'error-callback': function() {
						jQuery('#v3_recaptcha_notice_1').hide();
						jQuery('#v3_recaptcha_notice_2').show();
					}
				} );
			}, 100 );
			};</script>";
		}

		$html = '';
		if ( isset( $this->recaptcha_type ) && ! empty( $this->public_key ) ) {
			$html .= '<script src="https://www.google.com/recaptcha/api.js?hl=' . $language . '&render=explicit&onload=' . $onload . '" async defer></script>' . $js;

			$html .= sprintf(
				'<div class="%s" data-sitekey="%s" data-theme="%s" data-size="%s" %s></div>',
				'defender-g-recaptcha-' . $this->recaptcha_type,
				$this->public_key,
				$theme,
				$captcha_size,
				$data_args
			);
		} else {
			$html .= $notice;
		}

		return new Response(
			true,
			array(
				'preview' => true,
				'html'    => $html,
			)
		);
	}

	public function remove_settings() {}

	public function remove_data() {
		$this->get_model()->delete();
	}

	public function to_array() {}

	/**
	 * @return array
	 */
	public function data_frontend() {
		$model     = $this->get_model();
		$is_active = $model->is_active();
		/**
		 * Different cases for entered keys and locations:
		 * success - default or Woo location is checked,
		 * warning - default and Woo locations are unchecked,
		 * warning - default location is unchecked and Woo is deactivated,
		 * warning - non-entered keys.
		*/
		if ( $is_active ) {
			if ( $model->enable_default_locations() || $model->check_woo_locations( $this->is_woo_activated ) ) {
				switch ( $model->active_type ) {
					case 'v2_invisible':
						$type = 'V2 Invisible';
						break;
					case 'v3_recaptcha':
						$type = 'V3';
						break;
					case 'v2_checkbox':
					default:
						$type = 'V2 Checkbox';
						break;
				}
				$notice_type = 'success';
				$notice_text = sprintf(
				/* translators: */
					__( 'Google reCAPTCHA is currently active. %s type has been set successfully.', 'wpdef' ),
					$type
				);
			} elseif ( ! $this->is_woo_activated && ! $model->enable_default_locations() ) {
				$notice_type = 'warning';
				$notice_text = __( 'Google reCAPTCHA is currently inactive for all forms. You can deploy reCAPTCHA for specific forms in the <b>reCAPTCHA Locations</b> below.', 'wpdef' );
			} elseif ( $this->is_woo_activated && ! $model->enable_woo_locations() && ! $model->enable_default_locations() ) {
				$notice_type = 'warning';
				$notice_text = __( 'Google reCAPTCHA is currently inactive for all forms. You can deploy reCAPTCHA for specific forms in the <b>reCAPTCHA Locations</b> or <b>WooCommerce</b> settings below.', 'wpdef' );
			}
		} else {
			// Inactive case.
			$notice_type = 'warning';
			$notice_text = __( 'Google reCAPTCHA is currently inactive. Enter your Site and Secret keys and save your settings to finish setup.', 'wpdef' );
		}

		/**
		 * Cases:
		 * Invalid domain for Site Key,
		 * Google ReCAPTCHA is in localhost,
		 * Cannot contact reCAPTCHA. Check your connection.
		 */
		$ticket_text = __( 'If you see any errors in the preview, make sure the keys you’ve entered are valid, and you\'ve listed your domain name while generating the keys.', 'wpdef' );

		if ( ( new \WP_Defender\Behavior\WPMUDEV() )->show_support_links() ) {
			$ticket_text .= sprintf(
			/* translators: ... */
				__( 'Still having trouble? <a target="_blank" href="%s">Open a support ticket</a>.', 'wpdef' ),
				'https://wpmudev.com/forums/forum/support#question'
			);
		}

		return array_merge(
			array(
				'model'             => $model->export(),
				'is_active'         => $is_active,
				'default_message'   => $this->default_msg,
				'default_locations' => array(
					self::DEFAULT_LOGIN_FORM         => __( 'Login', 'wpdef' ),
					self::DEFAULT_REGISTER_FORM      => __( 'Register', 'wpdef' ),
					self::DEFAULT_LOST_PASSWORD_FORM => __( 'Lost Password', 'wpdef' ),
					self::DEFAULT_COMMENT_FORM       => __( 'Comments', 'wpdef' ),
				),
				'notice_type'       => $notice_type,
				'notice_text'       => $notice_text,
				'ticket_text'       => $ticket_text,
				'is_woo_active'     => $this->is_woo_activated,
				'woo_locations'     => array(
					self::WOO_LOGIN_FORM         => __( 'Login', 'wpdef' ),
					self::WOO_REGISTER_FORM      => __( 'Registration', 'wpdef' ),
					self::WOO_LOST_PASSWORD_FORM => __( 'Lost Password', 'wpdef' ),
				),
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

	/**
	 * @return array
	 */
	public function export_strings() {
		return array(
			$this->get_model()->is_active() ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}
}
