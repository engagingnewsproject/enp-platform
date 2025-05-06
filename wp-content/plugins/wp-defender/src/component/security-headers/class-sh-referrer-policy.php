<?php
/**
 * Handles the implementation of the Referrer-Policy header for security purposes.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the Referrer-Policy header which controls browser features.
 */
class Sh_Referrer_Policy extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_referrer_policy';

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_referrer_policy ) {
			return false;
		}
		if ( isset( $model->sh_referrer_policy_mode ) && ! empty( $model->sh_referrer_policy_mode ) ) {
			return true;
		}
		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
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
				'The Referrer-Policy HTTP header tells browsers how referrer information should be handled when a user clicks a link that leads to another page or link. A referrer header tells website owners where inbound visitors come from (similar to Google Analytics Acquisition reports), though in some cases you may want to control or restrict the referrer information present in the header.',
				'wpdef'
			),
			'mode'       => $model->sh_referrer_policy_mode ?? 'origin-when-cross-origin',
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
		if ( ! $this->maybe_submit_header( 'Referrer-Policy', false ) ) {

			return;
		}

		if ( true === $model->sh_referrer_policy
			&& isset( $model->sh_referrer_policy_mode )
			&& in_array(
				$model->sh_referrer_policy_mode,
				array(
					'no-referrer',
					'no-referrer-when-downgrade',
					'origin',
					'origin-when-cross-origin',
					'same-origin',
					'strict-origin',
					'strict-origin-when-cross-origin',
					'unsafe-url',
				),
				true
			)
		) {
			$headers = 'Referrer-Policy: ' . $model->sh_referrer_policy_mode;
			header( $headers );
		}
	}

	/**
	 * Retrieves the title of the Policy.
	 *
	 * @return string The title of the Policy.
	 */
	public function get_title() {
		return esc_html__( 'Referrer Policy', 'wpdef' );
	}
}