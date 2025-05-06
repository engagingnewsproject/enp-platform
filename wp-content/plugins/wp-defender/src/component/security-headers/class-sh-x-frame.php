<?php
/**
 * Handles the implementation of the X-Frame header for security purposes.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the X-Frame header which controls browser features.
 */
class Sh_X_Frame extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_xframe';

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_xframe ) {
			return false;
		}
		if ( isset( $model->sh_xframe_mode ) && ! empty( $model->sh_xframe_mode ) ) {
			return true;
		}
		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
		}

		if ( isset( $headers['x-frame-options'] ) ) {
			$header_xframe = is_array( $headers['x-frame-options'] ) ? $headers['x-frame-options'][0] : $headers['x-frame-options'];

			$content = strtolower( trim( $header_xframe ) );
			// If deprecated header directive is ALLOW-FROM then redirect to Sameorigin tab.
			if ( stristr( $content, 'allow-from' ) ) {
				$model->sh_xframe_mode = 'sameorigin';
			} elseif ( in_array( strtolower( $content ), array( 'sameorigin', 'deny' ), true ) ) {
				$model->sh_xframe_mode = strtolower( $content );
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
			'intro_text' => sprintf(
				/* translators: %s: HTML tags. */
				esc_html__( 'The X-Frame-Options HTTP response header controls whether or not a browser can render a webpage inside a %1$s or %2$s tag. Websites can avoid clickjacking attacks by ensuring that their content isn`t embedded into other websites.', 'wpdef' ),
				'<frame>, <iframe>',
				'<object>'
			),
			/**
			 * Directive ALLOW-FROM is deprecated.
			 *
			 * @since 2.5.0
			 */
			'mode'       => ( isset( $model->sh_xframe_mode ) && 'allow-from' !== $model->sh_xframe_mode )
				? $model->sh_xframe_mode
				: 'sameorigin',
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

		if ( ! $this->maybe_submit_header( 'X-Frame-Options', false ) ) {
			return;
		}

		$model = $this->get_model();
		$mode  = $model->sh_xframe_mode;
		if ( true === $model->sh_xframe && in_array( $mode, array( 'sameorigin', 'allow-from', 'deny' ), true ) ) {
			$mode = 'allow-from' === $mode ? 'sameorigin' : $mode;
			header( trim( 'X-Frame-Options: ' . $mode ) );
		}
	}

	/**
	 * Retrieves the title of the Policy.
	 *
	 * @return string The title of the Policy.
	 */
	public function get_title() {
		return esc_html__( 'X-Frame-Options', 'wpdef' );
	}
}