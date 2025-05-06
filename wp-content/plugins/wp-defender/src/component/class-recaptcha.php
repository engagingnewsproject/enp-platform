<?php
/**
 * Handles reCAPTCHA functionality.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Calotes\Base\Component;
use WP_Defender\Model\Setting\Recaptcha as Recaptcha_Model;

/**
 * Provides methods to handle Google reCAPTCHA integration, including rendering,
 * validation, and script management.
 */
class Recaptcha extends Component {

	/**
	 * Default form identifiers for reCAPTCHA integration.
	 */
	public const DEFAULT_LOGIN_FORM = 'login',
		DEFAULT_REGISTER_FORM       = 'register',
		DEFAULT_LOST_PASSWORD_FORM  = 'lost_password',
		DEFAULT_COMMENT_FORM        = 'comments';

	/**
	 * The reCAPTCHA settings model.
	 *
	 * @var Recaptcha_Model
	 */
	protected $model;

	/**
	 * Recaptcha constructor.
	 *
	 * @param  Recaptcha_Model $model  The reCAPTCHA settings model.
	 */
	public function __construct( Recaptcha_Model $model ) {
		$this->model = $model;
	}

	/**
	 * Determines if any reCAPTCHA location is enabled.
	 *
	 * @param  bool $exist_woo  Whether WooCommerce is active.
	 * @param  bool $exist_bp  Whether BuddyPress is active.
	 *
	 * @return bool True if any location is enabled, false otherwise.
	 */
	public function enable_any_location( $exist_woo, $exist_bp ): bool {
		return $this->model->enable_default_location()
				|| $this->model->check_woo_locations( $exist_woo )
				|| $this->model->check_buddypress_locations( $exist_bp );
	}

	/**
	 * Excludes reCAPTCHA for specific requests.
	 *
	 * @return bool True if the current request is excluded, false otherwise.
	 */
	public function exclude_recaptcha_for_requests(): bool {
		$current_request   = defender_get_data_from_request( 'REQUEST_URI', 's' ) ?? '/';
		$excluded_requests = (array) apply_filters( 'wd_recaptcha_excluded_requests', array() );

		return in_array( $current_request, $excluded_requests, true );
	}

	/**
	 * Removes duplicate reCAPTCHA scripts.
	 *
	 * @return bool|void False if no scripts are registered, or void otherwise.
	 */
	public function remove_dublicate_scripts() {
		global $wp_scripts;

		if ( ! is_object( $wp_scripts ) || empty( $wp_scripts ) ) {
			return false;
		}
		/**
		 * Exclude scripts from Defender and Forminator to display reCAPTCHA.
		 *
		 * @since 5.1.0
		*/
		$excluded_handles = (array) apply_filters(
			'wd_recaptcha_excluded_handles',
			array(
				'wpdef_recaptcha_api',
				'forminator-google-recaptcha',
			)
		);
		foreach ( $wp_scripts->registered as $script_name => $args ) {
			if ( is_string( $args->src ) && preg_match( '|google\.com/recaptcha/api\.js|', $args->src )
				&& ! in_array( $script_name, $excluded_handles, true )
			) {
				wp_dequeue_script( $script_name );
			}
		}
	}

	/**
	 * Returns a custom error message for reCAPTCHA validation failure.
	 *
	 * @return string The formatted error message.
	 */
	public function error_message(): string {
		$default_values = $this->model->get_default_values();

		return sprintf(
			'<strong>%s:</strong> %s',
			esc_html__( 'Error', 'wpdef' ),
			empty( $this->model->message ) ? $default_values['message'] : $this->model->message
		);
	}

	/**
	 * Sends an HTTP POST request to the Google reCAPTCHA API and returns the validation result.
	 *
	 * @param  array $post_body  The POST request body.
	 *
	 * @return bool True if the reCAPTCHA validation is successful, false otherwise.
	 */
	public function recaptcha_post_request( array $post_body ): bool {
		$args    = array(
			'body'      => $post_body,
			'sslverify' => false,
		);
		$url     = 'https://www.google.com/recaptcha/api/siteverify';
		$request = wp_remote_post( $url, $args );

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $request );
		$response_keys = json_decode( $response_body, true );
		if ( 'v3_recaptcha' === $this->model->active_type ) {
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
	 * Retrieves the list of default forms where reCAPTCHA can be integrated.
	 *
	 * @return array An associative array of form identifiers and their display names.
	 */
	public static function get_forms(): array {
		return array(
			self::DEFAULT_LOGIN_FORM         => esc_html__( 'Login', 'wpdef' ),
			self::DEFAULT_REGISTER_FORM      => esc_html__( 'Register', 'wpdef' ),
			self::DEFAULT_LOST_PASSWORD_FORM => esc_html__( 'Lost Password', 'wpdef' ),
			self::DEFAULT_COMMENT_FORM       => esc_html__( 'Comments', 'wpdef' ),
		);
	}
}