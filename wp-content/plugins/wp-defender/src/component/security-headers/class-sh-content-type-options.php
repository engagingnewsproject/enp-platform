<?php
/**
 * Handles the security header for 'X-Content-Type-Options'.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the 'X-Content-Type-Options' security header.
 */
class Sh_Content_Type_Options extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_content_type_options';

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_content_type_options ) {
			return false;
		}
		if ( isset( $model->sh_content_type_options_mode ) && 'nosniff' === $model->sh_content_type_options_mode ) {
			return true;
		}

		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
		}
		if ( isset( $headers['x-content-type-options'] ) && is_null( $model->sh_content_type_options_mode ) ) {
			$model->sh_content_type_options_mode = 'nosniff';
			$model->save();

			return true;
		}

		return false;
	}

	/**
	 * Retrieves miscellaneous data related to the Policy.
	 *
	 * @return array Contains introductory text, mode, and values for the Policy.
	 */
	public function get_misc_data() {
		$model = $this->get_model();

		return array(
			'intro_text' => esc_html__(
				'The X-Content-Type-Options header is used to protect against MIME sniffing attacks. The most common example of this is when a website allows users to upload content to a website, however the user disguises a particular file type as something else.',
				'wpdef'
			),
			'mode'       => $model->sh_content_type_options_mode ?? 'nosniff',
		);
	}

	/**
	 * Registers hooks related to sending headers.
	 */
	public function add_hooks() {
		add_action( 'send_headers', array( $this, 'append_header' ) );
	}

	/**
	 * Appends the header to the response.
	 */
	public function append_header() {
		if ( headers_sent() ) {
			return;
		}
		$model = $this->get_model();
		if ( ! $this->maybe_submit_header( 'X-Content-Type-Options', false ) ) {
			// this mean Defender can't override the already output, marked to show notification.

			return;
		}
		if ( true === $model->sh_content_type_options && 'nosniff' === $model->sh_content_type_options_mode ) {
			header( 'X-Content-Type-Options: nosniff' );
		}
	}

	/**
	 * Retrieves the title of the Policy.
	 *
	 * @return string The title of the Policy.
	 */
	public function get_title() {
		return esc_html__( 'X-Content-Type-Options', 'wpdef' );
	}
}