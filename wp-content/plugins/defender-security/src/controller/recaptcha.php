<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;

/**
 * Class Recaptcha
 *
 * @package WP_Defender\Controller
 * @since 2.5.4
 */
class Recaptcha extends \WP_Defender\Controller2 {

	/**
	 * Accepted values: v2_checkbox, v2_invisible, v3_recaptcha
	 */
	private $recaptcha_type;

	/**
	 * Accepted values: light and dark
	 */
	private $recaptcha_theme;

	/**
	 * Accepted values: normal and compact
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
	 * Use for cache
	 *
	 * @var \WP_Defender\Model\Setting\Recaptcha
	 */
	public $model;

	/**
	 *
	 * Recaptcha constructor
	 */
	public function __construct() {
		//Use default msg to avoid empty message error
		$this->default_msg = __( 'reCAPTCHA verification failed. Please try again.', 'wpdef' );
		$this->model       = $this->get_model();
		$this->register_routes();
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );

		if ( $this->model->is_active() && $this->model->enable_locations() ) {
			$this->recaptcha_type  = $this->model->active_type;
			$this->recaptcha_theme = 'light';
			$this->recaptcha_size  = 'invisible';
			$this->language        = ! empty( $this->model->language ) && 'automatic' !== $this->model->language
				? $this->model->language
				: get_locale();
			// Add the reCAPTCHA keys depending on the reCAPTCHA type
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

			//common hooks for default forms
			add_action( 'login_enqueue_scripts', array( $this, 'load_scripts' ), 1 );
			// for multisite on wp-signup.php page
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 1 );
			add_action( 'script_loader_tag', array( $this, 'script_loader_tag' ), 10, 3 );

			$locations = $this->model->locations;
			if ( in_array( 'login', $locations, true ) ) {
				add_action( 'login_form', array( $this, 'recaptcha_field' ) );
				add_action( 'login_header', array( $this, 'login_scripts' ), 10, 3 );
				add_action( 'wp_authenticate_user', array( $this, 'validate_captcha_field_on_login' ), 10, 2 );
			}
			if ( in_array( 'register', $locations, true ) ) {
				add_action( 'register_form', array( $this, 'recaptcha_field' ) );
				add_action( 'login_header', array( $this, 'login_scripts' ), 10, 3 );
				add_action( 'registration_errors', array( $this, 'validate_captcha_field' ), 10, 3 );
			}
			if ( in_array( 'lost_password', $locations, true ) ) {
				add_action( 'lostpassword_form', array( $this, 'recaptcha_field' ) );
				add_action( 'login_header', array( $this, 'login_scripts' ), 10, 3 );
				add_action( 'lostpassword_errors', array( $this, 'validate_captcha_field' ), 10 );
			}

			if ( is_multisite() ) {
				add_action( 'wpmu_validate_user_signup', array( $this, 'validate_captcha_field_wpmu_registration' ), 10, 1 );
				add_action( 'signup_extra_fields', array( $this, 'signup_extra_fields' ), 10, 1 );
				add_action( 'signup_hidden_fields', array( $this, 'signup_hidden_fields' ), 10, 1 );
			}
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
	 * Enqueue the reCAPTCHA script on the login and multisite on wp-signup.php page
	 */
	public function load_scripts() {
		$args = array( 'hl' => $this->language );

		if ( 'v2_checkbox' !== $this->recaptcha_type ) {
			$args['onload'] = 'reCaptchaLoader';
		}

		if ( 'v3_recaptcha' === $this->recaptcha_type ) {
			$args['render'] = $this->public_key;
		}

		$url = add_query_arg( $args, 'https://www.google.com/recaptcha/api.js' );

		if ( $this->recaptcha_should_enqueue() ) {
			wp_enqueue_script( 'google-recaptcha-api', $url, false );
		}
	}

	/**
	 * Display the reCAPTCHA field
	 */
	public function recaptcha_field() {
		$output = '';

		if ( 'v3_recaptcha' !== $this->recaptcha_type ) {
			// Do not need to display the reCAPTCHA field for v3_recaptcha field
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
			// Only add the hidden field for v2_invisible and v3_recaptcha type
			$output .= '<input type="hidden" id="recaptcha-response" name="g-recaptcha-response">';
		}

		echo $output;
	}

	/**
	 * Add the async and defer tag
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public function script_loader_tag( $tag, $handle, $src ) {
		if ( 'google-recaptcha-api' !== $handle ) {
			return $tag;
		}

		return str_replace( '<script', '<script async defer', $tag );
	}

	/**
	 * The reCaptcha login styles
	 */
	public function login_scripts() {
		if ( ! $this->recaptcha_should_enqueue() ) {
			return;
		}

		$style_start = '<style>';
		$style_end   = '</style>';

		if ( 'v2_checkbox' === $this->recaptcha_type ) {
			$output  = $style_start . PHP_EOL;
			$output .= '.g-recaptcha {-webkit-transform: scale(0.897);
				-moz-transform: scale(0.897);
				-ms-transform: scale(0.897);
				-o-transform: scale(0.897);
					transform: scale(0.897);
			-webkit-transform-origin: 0 0;
				-moz-transform-origin: 0 0;
				-ms-transform-origin: 0 0;
				-o-transform-origin: 0 0;
					transform-origin: 0 0; }';
			$output .= PHP_EOL . $style_end;
			echo $output;
		}

		if ( 'v2_invisible' === $this->recaptcha_type ) { ?>
			<style>#loginform .g-recaptcha, #lostpasswordform .g-recaptcha {margin-bottom: 10px;}</style>
			<script>
				var reCaptchaLoader = function() {
					grecaptcha.execute();
				};
				function setResponse(response) { 
					var recaptchaResponse = document.getElementById('recaptcha-response');
					if ( recaptchaResponse ) {
						document.getElementById('recaptcha-response').value = response; 
					}
				}
			</script>
			<?php
		}

		if ( 'v3_recaptcha' === $this->recaptcha_type ) {
			?>
			<script>
				var reCaptchaLoader = function() {
					grecaptcha.ready(function () {
						grecaptcha.execute('<?php echo $this->public_key; ?>', {}).then(function (token) {
							var recaptchaResponse = document.getElementById('recaptcha-response');
							if ( recaptchaResponse ) {
								recaptchaResponse.value = token;
							}
						});
					});
				}
			</script>
			<?php
		}
	}

	/**
	 * Verify the captcha code on the login field
	 *
	 * @param WP_User $user
	 * @param string $password
	 *
	 * @return WP_Error|WP_user
	 */
	public function validate_captcha_field_on_login( $user, $password ) {
		if ( empty( $_POST['g-recaptcha-response'] ) || false === $this->recaptcha_response() ) {
			return new \WP_Error( 'invalid_captcha', $this->error_message() );
		}

		return $user;
	}

	/**
	 * Verify the captcha code on the Registration, Lost password page
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error|WP_user
	 */
	public function validate_captcha_field( $errors ) {
		if ( empty( $errors ) ) {
			$errors = new \WP_Error();
		}

		if ( empty( $_POST['g-recaptcha-response'] ) || false === $this->recaptcha_response() ) {
			$errors->add( 'invalid_captcha', $this->error_message() );
			return $errors;
		}

		return $errors;
	}

	/**
	 * Verify the captcha code
	 *
	 * @param array $result
	 *
	 * @return array|WP_user
	 */
	public function validate_captcha_field_wpmu_registration( $result ) {
		if ( isset( $result['errors'] ) && ! empty( $result['errors'] ) ) {
			$errors = $result['errors'];
		} else {
			$errors = new \WP_Error();
		}

		if ( empty( $_POST['g-recaptcha-response'] ) || false === $this->recaptcha_response() ) {
			$errors->add( 'invalid_captcha', $this->error_message() );
			$result['errors'] = $errors;
			return $result;
		}

		$result['errors'] = $errors;
		return $result;
	}

	/**
	 * Add the reCAPTCHA field and some error fields on multisite wp-signup.php page
	 *
	 * @param WP_Error $errors
	 */
	public function signup_extra_fields( $errors ) {
		$output = '';
		if ( 'v3_recaptcha' !== $this->recaptcha_type ) {
			$data_args = 'v2_invisible' === $this->recaptcha_type
				? 'data-badge="inline" data-callback="setResponse"'
				: '';
			$output   .= sprintf(
				'<div class="g-recaptcha" data-theme="%s" data-size="%s" data-sitekey="%s" %s></div>',
				$this->recaptcha_theme,
				$this->recaptcha_size,
				$this->public_key,
				$data_args
			);
		}

		$invalid_captcha = $errors->get_error_message( 'invalid_captcha' );
		if ( $invalid_captcha ) {
			$output .= '<p class="error">' . $invalid_captcha . '</p>';
		}
		echo $output;
	}

	/**
	 * Add the hidden reCAPTCHA field for multisite on wp-signup.php page
	 */
	public function signup_hidden_fields() {
		if ( 'v2_checkbox' !== $this->recaptcha_type ) {
			// Only show the hidden field for v2_invisible and v3_recaptcha type
			echo '<input type="hidden" id="recaptcha-response" name="g-recaptcha-response" >';
		}
	}

	/**
	 * Get the reCAPTCHA API response.
	 *
	 * @return bool
	 */
	public function recaptcha_response() {
		// reCAPTCHA response post data
		$response = isset( $_POST['g-recaptcha-response'] ) ? esc_attr( $_POST['g-recaptcha-response'] ) : '';

		$post_body = array(
			'secret'   => $this->private_key,
			'response' => $response,
		);

		return $this->recaptcha_post_request( $post_body );
	}

	/**
	 * Display custom error message
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
	public function recaptcha_post_request( $post_body ) {
		$args    = array( 'body' => $post_body );
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
				$is_success = $response_keys['score'] >= (float)$this->model->data_v3_recaptcha['threshold'];
			} else {
				$is_success = false;
			}
		} else {
			$is_success = (bool) $response_keys['success'];
		}

		return $is_success;
	}

	/**
	 * Determine to enqueue the reCAPTCHA
	 *
	 * @return bool
	 */
	public function recaptcha_should_enqueue() {
		global $pagenow;
		$mask_login       = wd_di()->get( \WP_Defender\Component\Mask_Login::class );
		$mask_login_model = wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class );

		if ( $mask_login->is_land_on_masked_url( $mask_login_model->mask_url ) ) {
			// Detect the masked login page and enqueue the google recaptcha
			return true;
		}

		if ( 'wp-signup.php' === $pagenow || 'wp-login.php' === $pagenow ) {
			return true;
		}

		if ( isset( $_GET['checkemail'] ) && ! empty( $_GET['checkemail'] ) ) {
			// We will not enqueue after forget password is requested on losspassword page
			return false;
		}

		return false;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function script_data( $data ) {
		$data['recaptcha'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Save settings
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
			//Todo: clear active config
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
			//Onload method for Recaptcha works only for js 'var'
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

	public function remove_settings() {
		// TODO: Implement remove_settings() method.
	}

	public function remove_data() {
		$this->get_model()->delete();
	}

	public function to_array() {
		// TODO: Implement to_array() method.
	}

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
		 * Google ReCAPTCHA in localhost
		 * Cannot contact reCAPTCHA. Check your connection.
		 */
		$ticket_text = sprintf(
		/* translators: ... */
			__( 'If you see any errors in the preview, make sure the keys you’ve entered are valid, and you\'ve listed your domain name while generating the keys. Still having trouble? <a target="_blank" href="%s">Open a support ticket</a>.', 'wpdef' ),
			'https://wpmudev.com/forums/forum/support#question'
		);

		return array_merge(
			array(
				'model'           => $model->export(),
				'is_active'       => $is_active,
				'default_message' => $this->default_msg,
				'all_locations'   => array(
					'login'         => __( 'Login', 'wpdef' ),
					'register'      => __( 'Register', 'wpdef' ),
					'lost_password' => __( 'Lost Password', 'wpdef' ),
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
