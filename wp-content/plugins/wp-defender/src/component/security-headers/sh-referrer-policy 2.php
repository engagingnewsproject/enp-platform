<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

class Sh_Referrer_Policy extends Security_Header {
	static $rule_slug = 'sh_referrer_policy';

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
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ) );

			return false;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_misc_data() {
		$model = $this->get_model();

		return array(
			'intro_text' => esc_html__( 'The Referrer-Policy HTTP header tells browsers how referrer information should be handled when a user clicks a link that leads to another page or link. A referrer header tells website owners where inbound visitors come from (similar to Google Analytics Acquisition reports), though in some cases you may want to control or restrict the referrer information present in the header.', 'wpdef' ),
			'mode'       => isset( $model->sh_referrer_policy_mode ) ? $model->sh_referrer_policy_mode : 'origin-when-cross-origin',
		);
	}

	public function add_hooks() {
		add_action( 'send_headers', array( $this, 'append_header' ) );
	}

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
	 * @return string
	 */
	public function get_title() {
		return __( 'Referrer Policy', 'wpdef' );
	}
}