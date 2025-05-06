<?php
/**
 * Handles reCAPTCHA related actions.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_User;
use WP_Error;
use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Crypt;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Traits\Hummingbird;
use WP_Defender\Integrations\Buddypress;
use WP_Defender\Integrations\Woocommerce;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Recaptcha as Recaptcha_Component;
use WP_Defender\Model\Setting\Recaptcha as Recaptcha_Model;

/**
 * Handles reCAPTCHA related actions.
 *
 * @since 2.5.4
 */
class Recaptcha extends Event {

	use Hummingbird;

	/**
	 * Accepted values: v2_checkbox, v2_invisible, v3_recaptcha.
	 *
	 * @var string
	 */
	private $recaptcha_type;

	/**
	 * Accepted values: light and dark.
	 *
	 * @var string
	 */
	private $recaptcha_theme;

	/**
	 * Accepted values: normal and compact.
	 *
	 * @var string
	 */
	private $recaptcha_size;

	/**
	 * Recaptcha API public key.
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Recaptcha API private key.
	 *
	 * @var string
	 */
	private $private_key;

	/**
	 * Language for the reCAPTCHA.
	 *
	 * @var string
	 */
	private $language;

	/**
	 * Default message for the reCAPTCHA.
	 *
	 * @var string
	 */
	private $default_msg;

	/**
	 * The model for handling the data.
	 *
	 * @var Recaptcha_Model
	 */
	public $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Recaptcha_Component
	 */
	protected $service;

	/**
	 * Is Woo activated.
	 *
	 * @var bool
	 */
	private $is_woo_activated;

	/**
	 * Is BuddyPress activated.
	 *
	 * @var bool
	 */
	private $is_buddypress_activated;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->model   = wd_di()->get( Recaptcha_Model::class );
		$this->service = new Recaptcha_Component( $this->model );
		// Use default msg to avoid empty message error.
		$default_values    = $this->model->get_default_values();
		$this->default_msg = $default_values['message'];
		$this->register_routes();
		$this->is_woo_activated        = wd_di()->get( Woocommerce::class )->is_activated();
		$this->is_buddypress_activated = wd_di()->get( Buddypress::class )->is_activated();
		add_filter( 'wp_defender_advanced_tools_data', array( $this, 'script_data' ) );

		if (
			$this->model->is_active()
			// No need the check by Woo and Buddypress are activated because we use this below.
			&& $this->service->enable_any_location( $this->is_woo_activated, $this->is_buddypress_activated )
			&& ! $this->service->exclude_recaptcha_for_requests()
		) {
			$this->declare_variables();
			$this->add_actions();

			add_filter( 'script_loader_tag', array( $this, 'script_loader_tag' ), 10, 2 );
		}
	}

	/**
	 * Declares the necessary variables for the reCAPTCHA functionality.
	 *
	 * @return void
	 */
	protected function declare_variables(): void {
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

	/**
	 * Is it Defender's Google reCAPTCHA page?
	 *
	 * @return bool
	 */
	protected function is_recaptcha_settings(): bool {
		$view = defender_get_data_from_request( 'view', 'g' );
		return 'wdf-advanced-tools' === defender_get_current_page() && 'recaptcha' === $view;
	}

	/**
	 * Add actions for reCAPTCHA.
	 *
	 * @return void
	 */
	protected function add_actions() {
		$extra_conditions = is_admin() && ! ( defined( 'DOING_AJAX' ) && $this->is_recaptcha_settings() );
		// @since 2.5.6
		do_action( 'wd_recaptcha_before_actions', $extra_conditions );
		if ( $extra_conditions ) {
			return;
		}

		$display_for_known_users = $this->model->display_for_known_users();
		$locations               = $this->model->locations;
		// Default login form.
		if ( in_array( Recaptcha_Component::DEFAULT_LOGIN_FORM, $locations, true ) ) {
			add_filter( 'authenticate', array( $this, 'validate_login_recaptcha' ), 9999 );
			add_action( 'login_form', array( $this, 'display_login_recaptcha' ) );
			add_filter( 'wp_authenticate_user', array( $this, 'validate_captcha_field_on_login' ), 8 );
		}
		// Default register form.
		if ( in_array( Recaptcha_Component::DEFAULT_REGISTER_FORM, $locations, true ) ) {
			if ( ! is_multisite() ) {
				add_action( 'register_form', array( $this, 'display_login_recaptcha' ) );
				add_filter( 'registration_errors', array( $this, 'validate_captcha_field_on_registration' ), 10 );
			} else {
				add_action( 'signup_extra_fields', array( $this, 'display_signup_recaptcha' ) );
				add_action( 'signup_blogform', array( $this, 'display_signup_recaptcha' ) );
				add_filter(
					'wpmu_validate_user_signup',
					array(
						$this,
						'validate_captcha_field_on_wpmu_registration',
					),
					10
				);
			}
		}
		// Default lost password form.
		if ( in_array( Recaptcha_Component::DEFAULT_LOST_PASSWORD_FORM, $locations, true ) ) {
			add_action( 'lostpassword_form', array( $this, 'display_login_recaptcha' ) );
			if ( $this->maybe_validate_captcha_for_lostpassword() ) {
				add_action( 'lostpassword_post', array( $this, 'validate_captcha_field_on_lostpassword' ) );
			}
		}
		// Default comment form.
		if ( $display_for_known_users && in_array( Recaptcha_Component::DEFAULT_COMMENT_FORM, $locations, true ) ) {
			// @since v3.4.0 Change from 'comment_form_after_fields' to 'comment_form_defaults'.
			add_filter( 'comment_form_defaults', array( $this, 'comment_form_defaults' ), 10 );
			add_action( 'pre_comment_on_post', array( $this, 'validate_captcha_field_on_comment' ) );
			// When comments are loaded via Hummingbird's lazy load feature.
			if ( $this->is_lazy_load_comments_enabled() ) {
				add_action( 'wp_footer', array( $this, 'add_scripts_for_lazy_load' ) );
			}
		}
		// Todo: move code to related class.
		// For Woo forms. Mandatory check for the activated Woo before.
		if ( $this->model->check_woo_locations( $this->is_woo_activated ) ) {
			$woo_locations = $this->model->woo_checked_locations;
			// Woo login form.
			if ( in_array( Woocommerce::WOO_LOGIN_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_login_form', array( $this, 'display_login_recaptcha' ) );
				add_filter(
					'woocommerce_process_login_errors',
					array(
						$this,
						'validate_captcha_field_on_woo_login',
					),
					10
				);
			}
			// Woo register form.
			if ( in_array( Woocommerce::WOO_REGISTER_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_register_form', array( $this, 'display_login_recaptcha' ) );
				add_filter(
					'woocommerce_registration_errors',
					array(
						$this,
						'validate_captcha_field_on_woo_registration',
					),
					10
				);
			}
			// Woo lost password form.
			if ( in_array( Woocommerce::WOO_LOST_PASSWORD_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_lostpassword_form', array( $this, 'display_login_recaptcha' ) );
				// Use default WP hook because Woo doesn't have own hook, so there's the extra check for Woo form.
				$post_data = defender_get_data_from_request( null, 'p' );
				if ( isset( $post_data['wc_reset_password'], $post_data['user_login'] ) ) {
					add_action( 'lostpassword_post', array( $this, 'validate_captcha_field_on_lostpassword' ) );
				}
			}
			// Woo checkout form.
			if ( $display_for_known_users && in_array( Woocommerce::WOO_CHECKOUT_FORM, $woo_locations, true ) ) {
				add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'display_login_recaptcha' ) );
				add_action(
					'woocommerce_after_checkout_validation',
					array(
						$this,
						'validate_captcha_field_on_woo_checkout',
					),
					10,
					2
				);
			}
		}
		// Todo: move code to related class.
		// For BuddyPress forms. Mandatory check for the activated BuddyPress before.
		if ( $this->model->check_buddypress_locations( $this->is_buddypress_activated ) ) {
			$buddypress_locations = $this->model->buddypress_checked_locations;
			// Register form.
			if ( in_array( Buddypress::REGISTER_FORM, $buddypress_locations, true ) ) {
				add_action( 'bp_before_registration_submit_buttons', array( $this, 'display_buddypress_recaptcha' ) );
				add_filter(
					'bp_signup_validate',
					array(
						$this,
						'validate_captcha_field_on_buddypress_registration',
					),
					10
				);
			}
			// Group form.
			if ( $display_for_known_users && in_array( Buddypress::NEW_GROUP_FORM, $buddypress_locations, true ) ) {
				add_action( 'bp_after_group_details_creation_step', array( $this, 'display_login_recaptcha' ) );
				add_action( 'groups_group_before_save', array( $this, 'validate_captcha_field_on_buddypress_group' ) );
			}
		}
		// @since 2.5.6
		do_action( 'wd_recaptcha_after_actions', $display_for_known_users );
	}

	/**
	 * Validates the reCAPTCHA response for the login form.
	 *
	 * @param  null|WP_Error $error  WP_Error object if validation fails, else null.
	 *
	 * @return null|WP_Error WP_Error object if validation fails else null.
	 */
	public function validate_login_recaptcha( $error ) {
		// Check if the $_POST array is not empty and if 'g-recaptcha-response' key is also empty.
		$recaptcha_response = defender_get_data_from_request( 'g-recaptcha-response', 'p' );
		if ( ! empty( $recaptcha_response ) && empty( $recaptcha_response ) ) {
			$code    = 'recaptcha_error';
			$message = __( 'Please verify that you are not a robot.', 'wpdef' );

			if ( is_wp_error( $error ) ) {
				$error->add( $code, $message );
			} else {
				// Replace $user with a new WP_Error object with an error message.
				$error = new WP_Error( $code, $message );
			}
		}

		// Return the $error variable.
		return $error;
	}

	/**
	 * Modifies the script loader tag for the 'wpdef_recaptcha_api' handle.
	 *
	 * @param  string $tag  The original script loader tag.
	 * @param  string $handle  The handle being loaded.
	 *
	 * @return string The modified script loader tag.
	 */
	public function script_loader_tag( string $tag, string $handle ) {
		if ( 'wpdef_recaptcha_api' === $handle ) {
			$tag = str_replace( ' src', ' data-cfasync="false" async="async" defer="defer" src', $tag );
		}

		return $tag;
	}

	/**
	 * Returns the API URL for reCAPTCHA based on the recaptcha_type property.
	 *
	 * @return string The API URL for reCAPTCHA. Returns an empty string if the recaptcha_type is not set.
	 */
	protected function get_api_url(): string {
		if ( isset( $this->recaptcha_type ) ) {
			if ( 'v3_recaptcha' === $this->recaptcha_type ) {
				return sprintf( 'https://www.google.com/recaptcha/api.js?hl=%s&render=%s', $this->language, $this->public_key );
			} elseif ( in_array( $this->recaptcha_type, array( 'v2_checkbox', 'v2_invisible' ), true ) ) {
				return sprintf( 'https://www.google.com/recaptcha/api.js?hl=%s&render=explicit', $this->language );
			}
		}

		return '';
	}

	/**
	 * Enqueues the necessary scripts for the reCAPTCHA frontend.
	 *
	 * @return void
	 */
	public function add_scripts(): void {
		if ( isset( $this->recaptcha_type ) ) {
			$this->service->remove_dublicate_scripts();
		}

		wp_enqueue_script(
			'wpdef_recaptcha_script',
			plugins_url( 'assets/js/recaptcha_frontend.js', WP_DEFENDER_FILE ),
			array(
				'jquery',
				'wpdef_recaptcha_api',
			),
			DEFENDER_VERSION,
			true
		);
		// @since 2.5.6
		do_action( 'wd_recaptcha_extra_assets' );

		$error_text = esc_html__(
			'More than one reCAPTCHA has been found in the current form. Please remove all unnecessary reCAPTCHA fields to make it work properly.',
			'wpdef'
		);
		$options    = array(
			'hl'      => $this->language,
			'size'    => $this->recaptcha_size,
			'version' => $this->recaptcha_type,
			'sitekey' => $this->public_key,
			'error'   => sprintf( '<strong>%s</strong>:&nbsp;%s', esc_html__( 'Warning', 'wpdef' ), $error_text ),
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
	 * @return void
	 * @since 2.6.1
	 */
	public function add_scripts_for_lazy_load(): void {
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
	 *
	 * @return void
	 */
	public function display_login_recaptcha(): void {
		if ( 'v2_checkbox' === $this->recaptcha_type ) {
			$from_width = 302; ?>
			<style media="screen">
				.login-action-login #loginform,
				.login-action-lostpassword #lostpasswordform,
				.login-action-register #registerform {
					width: <?php echo (int) $from_width; ?>px !important;
				}

				#login_error,
				.message {
					width: <?php echo (int) $from_width + 20; ?>px !important;
				}

				.login-action-login #loginform .recaptcha_wrap,
				.login-action-lostpassword #lostpasswordform .recaptcha_wrap,
				.login-action-register #registerform .recaptcha_wrap {
					margin-bottom: 10px;
				}

				#group-create-body .recaptcha_wrap {
					margin-top: 15px;
				}
			</style>
			<?php
		} elseif ( 'v2_invisible' === $this->recaptcha_type ) {
			?>
			<style>
				.login-action-lostpassword #lostpasswordform .recaptcha_wrap,
				.login-action-login #loginform .recaptcha_wrap,
				.login-action-register #registerform .recaptcha_wrap {
					margin-bottom: 10px;
				}

				#signup-content .recaptcha_wrap,
				#group-create-body .recaptcha_wrap {
					margin-top: 10px;
				}
			</style>
			<?php
		}
		$allowed_html = array(
			'div'      => array(
				'class' => array(),
				'id'    => array(),
			),
			'iframe'   => array(
				'src'         => array(),
				'frameborder' => array(),
				'scrolling'   => array(),
				'style'       => array(),
			),
			'noscript' => array(),
			'textarea' => array(
				'name'  => array( 'g-recaptcha-response' ),
				'class' => array( 'g-recaptcha-response' ),
				'style' => array(),
			),
			'input'    => array(
				'type'  => array( 'hidden' ),
				'class' => array( 'g-recaptcha-response' ),
				'name'  => array( 'g-recaptcha-response' ),
			),
		);
		echo wp_kses( $this->display_recaptcha(), $allowed_html );
	}

	/**
	 * Display the output of the recaptcha.
	 *
	 * @return string
	 */
	protected function display_recaptcha(): string {
		$deps    = null;
		$content = '<div class="recaptcha_wrap wpdef_recaptcha_' . $this->recaptcha_type . '">';
		if ( ! $this->private_key || ! $this->public_key || empty( $this->recaptcha_type ) ) {
			// Display nothing.
			$content .= '</div>';

			return $content;
		}

		$api_url = $this->get_api_url();

		// Generate random id value if there's content with pagination plugin for not getting duplicate id values.
		$id = Crypt::random_int( 0, mt_getrandmax() );
		if ( in_array( $this->recaptcha_type, array( 'v2_checkbox', 'v2_invisible' ), true ) ) {
			$content .= '<div id="wpdef_recaptcha_' . $id . '" class="wpdef_recaptcha"></div>
			<noscript>
				<div style="width: 302px;">
					<div style="width: 302px; height: 422px; position: relative;">
						<div style="width: 302px; height: 422px; position: absolute;">
							<iframe src="https://www.google.com/recaptcha/api/fallback?k=' . esc_attr( $this->public_key ) . '" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;"></iframe>
						</div>
					</div>
					<div style="border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px; height: 60px; width: 300px;">
						<textarea name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px !important; height: 40px !important; border: 1px solid #c1c1c1 !important; margin: 10px 25px !important; padding: 0px !important; resize: none !important;"></textarea>
					</div>
				</div>
			</noscript>';
			$deps     = array( 'jquery' );
		} elseif ( 'v3_recaptcha' === $this->recaptcha_type ) {
			$content .= '<input type="hidden" class="g-recaptcha-response" name="g-recaptcha-response" />';
		}
		$content .= '</div>';

		// Register reCAPTCHA script.
		$locations = $this->model->locations;
		if ( ! wp_script_is( 'wpdef_recaptcha_api', 'registered' ) ) {

			if ( 'v3_recaptcha' === $this->recaptcha_type ) {
				wp_register_script( 'wpdef_recaptcha_api', $api_url, array(), DEFENDER_VERSION, false );
			} else {
				wp_register_script( 'wpdef_recaptcha_api', $api_url, $deps, DEFENDER_VERSION, true );
			}
			add_action( 'wp_footer', array( $this, 'add_scripts' ) );
			if (
				in_array( Recaptcha_Component::DEFAULT_LOGIN_FORM, $locations, true )
				|| in_array( Recaptcha_Component::DEFAULT_REGISTER_FORM, $locations, true )
				|| in_array( Recaptcha_Component::DEFAULT_LOST_PASSWORD_FORM, $locations, true )
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
	protected function is_woocommerce_page(): bool {
		if ( ! $this->is_woo_activated ) {
			return false;
		}

		$traces = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
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
	 * @param  WP_User|WP_Error $user  WP_User or WP_Error object if a previous callback failed authentication.
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
		// Is Recaptcha-request from 'Ultimate Member' plugin?
		if ( ! empty( defender_get_data_from_request( 'um_request', 'p' ) ) && function_exists( 'um_recaptcha_validate' ) ) {
			return $user;
		}

		if ( ! $this->recaptcha_response( 'default_login' ) ) {
			if ( is_wp_error( $user ) ) {
				$user->add( 'invalid_captcha', $this->service->error_message() );

				return $user;
			}
			return new WP_Error( 'invalid_captcha', $this->service->error_message() );
		}

		return $user;
	}

	/**
	 * Verify the recaptcha code on the Registration page.
	 *
	 * @param  WP_Error $errors  A WP_Error object containing any errors encountered during registration.
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_registration( WP_Error $errors ) {
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $errors;
		}

		if ( ! $this->recaptcha_response( 'default_registration' ) ) {
			$errors->add( 'invalid_captcha', $this->service->error_message() );
		}
		$_POST['g-recaptcha-response-check'] = true;

		return $errors;
	}

	/**
	 * Add google recaptcha to the multisite signup form.
	 *
	 * @param  WP_Error $errors  A WP_Error object possibly containing 'blogname' or 'blog_title' errors.
	 *
	 * @return void
	 */
	public function display_signup_recaptcha( WP_Error $errors ): void {
		$error_message = $errors->get_error_message( 'invalid_captcha' );
		if ( ! empty( $error_message ) ) {
			printf( '<p class="error">%s</p>', wp_kses_post( $error_message ) );
		}
		echo wp_kses_post( $this->display_recaptcha() );
	}

	/**
	 * Verify the recaptcha code on the multisite signup page.
	 *
	 * @param  array $result  An array of errors.
	 *
	 * @return array
	 */
	public function validate_captcha_field_on_wpmu_registration( array $result ): array {
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
			$errors->add( 'invalid_captcha', $this->service->error_message() );
			$result['errors'] = $errors;

			return $result;
		}

		return $result;
	}

	/**
	 * Verify the recaptcha code on Woo login page.
	 *
	 * @param  WP_Error $errors  A WP_Error object containing any errors encountered during login.
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_woo_login( WP_Error $errors ) {
		// Skip check if connecting to XMLRPC.
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			return $errors;
		}

		if ( ! $this->recaptcha_response( 'woo_login' ) ) {
			// Remove 'Error: ' because Woo has it by default.
			$message = str_replace(
				sprintf( '<strong>%s:</strong> ', esc_html__( 'Error', 'wpdef' ) ),
				'',
				$this->service->error_message()
			);
			$errors->add( 'invalid_captcha', $message );
		}

		return $errors;
	}

	/**
	 * Check recaptcha on Woo registration form.
	 *
	 * @param  WP_Error $errors  A WP_Error object containing any errors encountered during registration.
	 *
	 * @return WP_Error
	 */
	public function validate_captcha_field_on_woo_registration( WP_Error $errors ) {
		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $errors;
		}
		if ( ! $this->recaptcha_response( 'woo_registration' ) ) {
			// Remove 'Error: ' because Woo has it by default.
			$message = str_replace(
				sprintf( '<strong>%s:</strong> ', esc_html__( 'Error', 'wpdef' ) ),
				'',
				$this->service->error_message()
			);
			$errors->add( 'invalid_captcha', $message );
		}

		return $errors;
	}

	/**
	 * Fires before errors are returned from a password reset request.
	 * Without 2nd `$user_data` parameter because it's since WP 5.4.0.
	 *
	 * @param  WP_Error $errors  A WP_Error object containing any errors encountered during password reset.
	 *
	 * @return void
	 */
	public function validate_captcha_field_on_lostpassword( WP_Error $errors ): void {
		if ( ! $this->recaptcha_response( 'default_lost_password' ) ) {
			$errors->add( 'invalid_captcha', $this->service->error_message() );
		}
	}

	/**
	 * Validates the reCAPTCHA field on the WooCommerce checkout form.
	 *
	 * @param  array    $fields  The fields of the checkout form.
	 * @param  WP_Error $errors  The errors encountered during the checkout process.
	 *
	 * @return WP_Error The updated errors with the reCAPTCHA validation result.
	 */
	public function validate_captcha_field_on_woo_checkout( $fields, $errors ): WP_Error {
		if ( ! $this->recaptcha_response( 'woo_checkout' ) ) {
			// Remove 'Error: ' because Woo has it by default.
			$message = str_replace(
				sprintf( '<strong>%s:</strong> ', esc_html__( 'Error', 'wpdef' ) ),
				'',
				$this->service->error_message()
			);
			$errors->add( 'invalid_captcha', $message );
		}

		return $errors;
	}

	/**
	 * Display google recaptcha on comments form.
	 *
	 * @param  array $defaults  The default comment form arguments.
	 *
	 * @return array
	 */
	public function comment_form_defaults( array $defaults ) {
		$defaults['comment_notes_after'] .= '<p>' . $this->display_recaptcha() . '</p>';

		return $defaults;
	}

	/**
	 * Check JS enabled for comment form.
	 *
	 * @param  int $comment_post_id  Post ID.
	 *
	 * @return void
	 */
	public function validate_captcha_field_on_comment( $comment_post_id ) {
		if ( $this->service->exclude_recaptcha_for_requests() ) {
			return;
		}
		// Skip if it's from WooCommerce review form.
		if ( 'product' === get_post_type( $comment_post_id ) ) {
			return;
		}

		if ( ! $this->recaptcha_response( 'default_comments' ) ) {
			// @since v2.5.6
			wp_die(
				wp_kses_post(
					apply_filters(
						'wd_recaptcha_require_valid_comment',
						$this->service->error_message()
					)
				)
			);
		}
	}

	/**
	 * Validates the reCAPTCHA response for a given form.
	 *
	 * @param  string $form  The form being verified.
	 *
	 * @return bool The result of the reCAPTCHA verification.
	 * @since 2.5.6
	 */
	protected function recaptcha_response( string $form ): bool {
		$response = stripslashes( defender_get_data_from_request( 'g-recaptcha-response', 'p' ) );
		if ( empty( $this->private_key ) || empty( $response ) ) {
			return false;
		}
		// reCAPTCHA response post data.
		$remote_ip = filter_var( defender_get_data_from_request( 'REMOTE_ADDR', 's' ), FILTER_VALIDATE_IP );
		/**
		 * Filters to Get the remote IP address.
		 *
		 * @param  mixed  $remote_ip  The remote IP address.
		 *
		 * @since 2.5.6
		 */
		$remote_ip = (string) apply_filters( 'wd_recaptcha_remote_ip', $remote_ip );

		$post_body = array(
			'secret'   => $this->private_key,
			'response' => $response,
			'remoteip' => $remote_ip,
		);

		$result = $this->service->recaptcha_post_request( $post_body );

		/**
		 * Filters to check the result of a reCAPTCHA verification.
		 *
		 * @param  mixed  $result  The result of the reCAPTCHA verification.
		 * @param  mixed  $form  The form being verified.
		 *
		 * @since 2.5.6
		 */
		return apply_filters( 'wd_recaptcha_check_result', $result, $form );
	}

	/**
	 * Display the BuddyPress reCAPTCHA.
	 *
	 * @return void
	 */
	public function display_buddypress_recaptcha(): void {
		if ( ! empty( buddypress()->signup->errors['failed_recaptcha_verification'] ) ) {
			$output  = '<div class="error">';
			$output .= buddypress()->signup->errors['failed_recaptcha_verification'];
			$output .= '</div>';

			echo wp_kses_post( $output );
		}
		echo wp_kses_post( $this->display_recaptcha() );
	}

	/**
	 * Validates the reCAPTCHA field on the BuddyPress registration form.
	 *
	 * @return void
	 */
	public function validate_captcha_field_on_buddypress_registration(): void {
		if ( ! $this->recaptcha_response( 'buddypress_registration' ) ) {
			buddypress()->signup->errors['failed_recaptcha_verification'] = $this->service->error_message();
		}
	}

	/**
	 * Verify BuddyPress group form captcha.
	 *
	 * @return bool|void
	 */
	public function validate_captcha_field_on_buddypress_group() {
		if ( ! bp_is_group_creation_step( 'group-details' ) ) {
			return false;
		}

		if ( ! $this->recaptcha_response( 'buddypress_create_group' ) ) {
			bp_core_add_message( $this->service->error_message(), 'error' );
			bp_core_redirect( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/group-details/' );
		} else {
			return false;
		}
	}

	/**
	 * Provide data to the frontend via localized script.
	 *
	 * @param  array $data  Data collection is ready to passed.
	 *
	 * @return array Modified data array with added this controller data.
	 */
	public function script_data( array $data ): array {
		$data['recaptcha'] = $this->data_frontend();

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
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message'    => esc_html__( 'Google reCAPTCHA settings saved successfully.', 'wpdef' ),
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			// Merge stored data to avoid errors.
			array_merge(
				array(
					'message'    => $this->model->get_formatted_errors(),
					'error_keys' => $this->model->get_error_keys(),
				),
				$this->data_frontend()
			)
		);
	}

	/**
	 * Load the reCAPTCHA preview based on the provided request data.
	 *
	 * @param  Request $request  The request object containing the data needed to load the preview.
	 *
	 * @return Response The response object with the preview HTML and status.
	 * @defender_route
	 */
	public function load_recaptcha_preview( Request $request ): Response {
		$onload               = null;
		$js                   = null;
		$data                 = $request->get_data(
			array(
				'captcha_type' => array(
					'type' => 'string',
				),
			)
		);
		$this->recaptcha_type = $data['captcha_type'];

		$model    = $this->model;
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
			$html .= '<script src="https://www.google.com/recaptcha/api.js?hl=' . esc_attr( $language ) . '&render=explicit&onload=' . $onload . '" async defer></script>' . $js; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

			$html .= sprintf(
				'<div class="%s" data-sitekey="%s" data-theme="%s" data-size="%s" %s></div>',
				'defender-g-recaptcha-' . $this->recaptcha_type,
				esc_attr( $this->public_key ),
				esc_attr( $theme ),
				esc_attr( $captcha_size ),
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

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		$this->model->delete();
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
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$model     = $this->model;
		$is_active = $model->is_active();
		/**
		 * Different cases for entered keys and locations:
		 * success - one default, Woo or BuddyPress location is checked at least,
		 * warning - default, Woo and BuddyPress locations are unchecked,
		 * warning - default location is unchecked, also Woo and BuddyPress is deactivated,
		 * warning - non-entered keys.
		 */
		if ( $is_active ) {
			if ( $this->service->enable_any_location( $this->is_woo_activated, $this->is_buddypress_activated ) ) {
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
				/* translators: %s: Type. */
					esc_html__( 'Google reCAPTCHA is currently active. %s type has been set successfully.', 'wpdef' ),
					$type
				);
			} elseif ( ! $this->is_woo_activated && ! $this->is_buddypress_activated && ! $model->enable_default_location() ) {
				$notice_type = 'warning';
				$notice_text = sprintf(
				/* translators: %s: Type. */
					esc_html__( 'Google reCAPTCHA is currently inactive for all forms. You can deploy reCAPTCHA for specific forms in the %s below.', 'wpdef' ),
					'<b>' . esc_html__( 'reCAPTCHA Locations', 'wpdef' ) . '</b>'
				);
			} elseif (
				! $model->enable_default_location()
				&& (
					( $this->is_woo_activated && ! $model->enable_woo_location() )
					|| ( $this->is_buddypress_activated && ! $model->enable_buddypress_location() )
				)
			) {
				$notice_type = 'warning';
				$notice_text = sprintf(
				/* translators: 1. reCAPTCHA & Woo location. 2. BuddyPress location. */
					esc_html__( 'Google reCAPTCHA is currently inactive for all forms. You can deploy reCAPTCHA for specific forms in the %1$s or %2$s settings below.', 'wpdef' ),
					'<b>' . esc_html__( 'reCAPTCHA Locations', 'wpdef' ) . '</b>, <b>' . esc_html__( 'WooCommerce', 'wpdef' ) . '</b>',
					'<b>' . esc_html__( 'BuddyPress', 'wpdef' ) . '</b>'
				);
			}
		} else {
			// Inactive case.
			$notice_type = 'warning';
			$notice_text = esc_html__(
				'Google reCAPTCHA is currently inactive. Enter your Site and Secret keys and save your settings to finish setup.',
				'wpdef'
			);
		}

		/**
		 * Cases:
		 * Invalid domain for Site Key,
		 * Google ReCAPTCHA is in localhost,
		 * Cannot contact reCAPTCHA. Check your connection.
		 */
		$ticket_text = esc_html__(
			'If you see any errors in the preview, make sure the keys you’ve entered are valid, and you\'ve listed your domain name while generating the keys.',
			'wpdef'
		);

		if ( ( new WPMUDEV() )->show_support_links() ) {
			$ticket_text .= defender_support_ticket_text();
		}

		return array_merge(
			array(
				'model'                => $model->export(),
				'is_active'            => $is_active,
				'default_message'      => $this->default_msg,
				'default_locations'    => Recaptcha_Component::get_forms(),
				'notice_type'          => $notice_type,
				'notice_text'          => $notice_text,
				'ticket_text'          => $ticket_text,
				'is_woo_active'        => $this->is_woo_activated,
				'woo_locations'        => Woocommerce::get_forms(),
				'is_buddypress_active' => $this->is_buddypress_activated,
				'buddypress_locations' => Buddypress::get_forms(),
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
		$model       = $this->model;
		$notice_type = ( $model->is_active()
						&& $this->service->enable_any_location(
							$this->is_woo_activated,
							$this->is_buddypress_activated
						)
		)
			? 'success'
			: 'warning';

		return array(
			'model'       => $model->export(),
			'notice_type' => $notice_type,
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$model = $this->model;

		$model->import( $data );
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
		return array( $this->model->is_active() ? esc_html__( 'Active', 'wpdef' ) : esc_html__( 'Inactive', 'wpdef' ) );
	}

	/**
	 * Maybe validate reCaptcha for lost password.
	 *
	 * @return bool
	 * @since 3.2.0
	 */
	protected function maybe_validate_captcha_for_lostpassword(): bool {
		$post_data = defender_get_data_from_request( null, 'p' );
		$action    = $post_data['action'] ?? '';

		return ! $this->is_woocommerce_page() &&
				! isset( $post_data['wc_reset_password'], $post_data['user_login'] ) &&
				! ( is_admin() && 'send-password-reset' === $action ) &&
				'pp_ajax_passwordreset' !== $action;
	}

	/**
	 * Enable/disable module.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @return Response
	 * @defender_route
	 * @since 3.12.0
	 */
	public function toggle_module( Request $request ): Response {
		$data = $request->get_data(
			array(
				'enabled' => array(
					'type' => 'boolean',
				),
			)
		);

		$this->model->enabled = $data['enabled'];
		$this->model->save();

		Config_Hub_Helper::set_clear_active_flag();

		return new Response(
			true,
			array_merge(
				array(
					'message'    => esc_html__( 'Google reCAPTCHA settings saved successfully.', 'wpdef' ),
					'auto_close' => true,
				),
				$this->data_frontend()
			)
		);
	}
}