<?php
/**
 * Handles the implementation of the X-XSS-Protection  header for security purposes.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the X-XSS-Protection header which controls browser features.
 */
class Sh_XSS_Protection extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_xss_protection';

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_xss_protection ) {
			return false;
		}
		if ( isset( $model->sh_xss_protection_mode ) && ! empty( $model->sh_xss_protection_mode ) ) {
			return true;
		}
		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
		}

		if ( isset( $headers['x-xss-protection'] ) ) {
			$header_xss_protection = is_array( $headers['x-xss-protection'] )
				? $headers['x-xss-protection'][0]
				: $headers['x-xss-protection'];
			$content               = strtolower( trim( $header_xss_protection ) );
			$content               = explode( ';', $content );
			if ( 1 === count( $content ) ) {
				$model->sh_xss_protection_mode = 'sanitize';
			} else {
				$content                       = explode( '=', $content[1] );
				$model->sh_xss_protection_mode = $content[1];
			}
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
				'The HTTP X-XSS-Protection response header that stops pages from loading when they detect reflected cross-site scripting (XSS) attacks on Chrome, IE and Safari.',
				'wpdef'
			),
			'mode'       => $model->sh_xss_protection_mode ?? 'sanitize',
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
		if ( ! $this->maybe_submit_header( 'X-XSS-Protection', false ) ) {

			return;
		}
		if ( true === $model->sh_xss_protection && in_array(
			$model->sh_xss_protection_mode,
			array(
				'sanitize',
				'block',
				'none',
			),
			true
		) ) {
			$headers = '';
			switch ( $model->sh_xss_protection_mode ) {
				case 'sanitize':
					$headers = 'X-XSS-Protection: 1';
					break;
				case 'block':
					$headers = 'X-XSS-Protection: 1; mode=block';
					break;
				default:
					break;
			}
			if ( strlen( $headers ) > 0 ) {
				header( trim( $headers ) );
			}
		}
	}

	/**
	 * Retrieves the title of the Policy.
	 *
	 * @return string The title of the Policy.
	 */
	public function get_title() {
		return esc_html__( 'X-XSS-Protection', 'wpdef' );
	}
}