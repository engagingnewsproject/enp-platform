<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Error;
use WP_User;

/**
 * Class Recaptcha
 *
 * @package WP_Defender\Controller
 * @since 2.5.4
 */
class Recaptcha extends \WP_Defender\Controller2 {

	/**
	 * Accepted values: v2_checkbox, v2_invisible, v3_recaptcha.
	 */
	private $recaptcha_type;

	/**
	 * Accepted values: light and dark.
	 */
	private $recaptcha_theme;

	/**
	 * Accepted values: normal and compact.
	 */
	private $recaptcha_size;

	private $public_key;

	private $private_key;

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

	public function __construct() {
		// Use default msg to avoid empty message error.
		$this->default_msg = __( 'reCAPTCHA verification failed. Please try again.', 'wpdef' );
		$this->model       = $this->get_model();
		$this->register_routes();
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );

		if (
			$this->model->is_active()
			&& $this->model->enable_locations()
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
		if ( in_array( 'login', $locations, true )
			|| in_array( 'register', $locations, true )
			|| in_array( 'lost_password', $locations, true )
		) {
			// Login form.
			if ( in_array( 'login', $locations, true ) ) {
				add_action( 'login_form', array( $this, 'display_login_recaptcha' ) );
				add_action( 'wp_authenticate_user', array( $this, 'validate_captcha_field_on_login' ), 10 );
			}
			// Register form.
			if ( in_array( 'register', $locations, true ) ) {
				if ( ! is_multisite() ) {
					add_action( 'register_form', array( $this, 'display_login_recaptcha' ) );
					add_action( 'registration_errors', array( $this, 'validate_captcha_field' ), 10 );
				} else {
					add_action( 'signup_extra_fields', array( $this, 'display_signup_recaptcha' ) );
					add_action( 'signup_blogform', array( $this, 'display_signup_recaptcha' ) );
					add_filter( 'wpmu_validate_user_signup', array( $this, 'validate_captcha_field_wpmu_registration' ), 10 );
				}
			}
			// Lost password form.
			if ( in_array( 'lost_password', $locations, true ) ) {
				add_action( 'lostpassword_form', array( $this, 'display_login_recaptcha' ) );
				add_action( 'allow_password_reset', array( $this, 'validate_captcha_field_on_lostpassword' ) );
			}
		}
		// WP comments.
		if ( ! $is_user_logged_in && in_array( 'comments', $locations, true ) ) {
			add_action( 'comment_form_after_fields', array( $this, 'display_comment_recaptcha' ) );
			add_action( 'pre_comment_on_post', array( $this, 'validate_captcha_field_on_comment' ) );
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
		} elseif ( 'v2_invisible' === $this->recaptcha_type ) { ?>
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
				in_array( 'login', $locations, true ) ||
				in_array( 'lost_password', $locations, true ) ||
				in_array( 'register', $locations, true )
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
		if ( ! $this->is_woocommerce_active() ) {
			return false;
		}

		$traces = debug_backtrace();
		foreach( $traces as $trace ) {
			if ( isset( $trace['file'] ) && false !== strpos( $trace['file'], 'woocommerce' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check the Woo plugin is active.
	 *
	 * @retun bool
	*/
	protected function is_woocommerce_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		return is_multisite()
				? is_plugin_active_for_network( 'woocommerce/woocommerce.php' )
				: is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Verify the captcha code on the Login page.
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

		if ( ! $this->recaptcha_response( 'login_form' ) ) {
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
	 * Verify the captcha code on the Registration page.
	 *
	 * @param WP_Error $allow
	 *
	 * @return WP_Error|mixed
	 */
	public function validate_captcha_field( $allow ) {
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $allow;
		}

		if ( ! $this->recaptcha_response( 'register' ) ) {
			if ( is_wp_error( $allow ) ) {
				$allow->add( 'invalid_captcha', $this->error_message() );
				return $allow;
			} else {
				return new WP_Error( 'invalid_captcha', $this->error_message() );
			}
		}
		$_POST['g-recaptcha-response-check'] = true;

		return $allow;
	}

	/**
	 * Verify the captcha code on Lost password page.
	 *
	 * @param WP_Error $allow
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_lostpassword( $allow ) {
		if ( $this->is_woocommerce_page() ) {
			return $allow;
		}

		if ( isset( $_POST['g-recaptcha-response-check'] ) && true === $_POST['g-recaptcha-response-check'] ) {
			return $allow;
		}

		if ( ! $this->recaptcha_response( 'lost_password' ) ) {
			if ( is_wp_error( $allow ) ) {
				$allow->add( 'invalid_captcha', $this->error_message() );
				return $allow;
			} else {
				return new WP_Error( 'invalid_captcha', $this->error_message() );
			}
		}

		return $allow;
	}

	/**
	 * Add google captcha to the multisite signup form.
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
	 * Verify the captcha code.
	 *
	 * @param array $result
	 *
	 * @return array|WP_user
	 */
	public function validate_captcha_field_wpmu_registration( $result ) {
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
	 * Add google recaptcha to the comment form.
	 */
	public function display_comment_recaptcha() {
		echo '<style>#commentform .recaptcha_wrap {margin: 0 0 10px;}</style>';
		echo $this->display_recaptcha();
		return true;
	}

	/**
	 * Check JS enabled for comment form.
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
	 * Display the reCAPTCHA field.
	 */
	public function recaptcha_field() {
		$output = '';

		if ( 'v3_recaptcha' !== $this->recaptcha_type ) {
			// Do not need to display the reCAPTCHA field for v3_recaptcha field.
			$data_args = 'v2_invisible' === $this->recaptcha_type
				? 'data-badge="inline" data-callback="setResponse"'
				: '';
			$output   .= sprintf(
				'<div id="recaptcha" class="g-recaptcha" data-theme="%s" data-size="%s" data-sitekey="%s" %s></div>',
				$this->recaptcha_theme,
				$this->recaptcha_size,
				$this->public_key,
				$data_args
			);
		}

		if ( 'v2_checkbox' !== $this->recaptcha_type ) {
			// Only add the hidden field for v2_invisible and v3_recaptcha type.
			$output .= '<input type="hidden" id="recaptcha-response" name="g-recaptcha-response">';
		}

		echo $output;
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
			array(
				'message' => $this->model->get_formatted_errors(),
			)
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
		if ( $is_active && $model->enable_locations() ) {
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
			$notice_text = sprintf(
			/* translators: */
				__( 'Google reCAPTCHA is currently active. %s type has been set successfully.', 'wpdef' ),
				$type
			);
			$notice_type = 'success';
		} elseif ( $is_active && ! $model->enable_locations() ) {
			$notice_type = 'warning';
			$notice_text = __( 'Google reCAPTCHA is currently deactivated in all your forms. You can enable it in the reCAPTCHA Locations below.', 'wpdef' );
		} else {
			//Inactive case
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
				'model'           => $model->export(),
				'is_active'       => $is_active,
				'default_message' => $this->default_msg,
				'all_locations'   => array(
					'login'         => __( 'Login', 'wpdef' ),
					'register'      => __( 'Register', 'wpdef' ),
					'lost_password' => __( 'Lost Password', 'wpdef' ),
					'comments'      => __( 'Comments', 'wpdef' ),
				),
				'notice_type'     => $notice_type,
				'notice_text'     => $notice_text,
				'ticket_text'     => $ticket_text,
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
