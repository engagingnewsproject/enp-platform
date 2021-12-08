<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

class Sh_Content_Type_Options extends Security_Header {
	static $rule_slug = 'sh_content_type_options';

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
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ) );

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
	 * @return array
	 */
	public function get_misc_data() {
		$model = $this->get_model();

		return array(
			'intro_text' => esc_html__( 'The X-Content-Type-Options header is used to protect against MIME sniffing attacks. The most common example of this is when a website allows users to upload content to a website, however the user disguises a particular file type as something else.', 'wpdef' ),
			'mode'       => isset( $model->sh_content_type_options_mode ) ? $model->sh_content_type_options_mode : 'nosniff',
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
		if ( ! $this->maybe_submit_header( 'X-Content-Type-Options', false ) ) {
			//this mean Defender can't override the already output, marked to show notification

			return;
		}
		if ( true === $model->sh_content_type_options && 'nosniff' === $model->sh_content_type_options_mode ) {
			header( 'X-Content-Type-Options: nosniff' );
		}
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'X-Content-Type-Options', 'wpdef' );
	}
}