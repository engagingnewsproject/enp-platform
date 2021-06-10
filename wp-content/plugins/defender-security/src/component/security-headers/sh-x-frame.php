<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

class Sh_X_Frame extends Security_Header {
	static $rule_slug = 'sh_xframe';

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
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ) );

			return false;
		}

		if ( isset( $headers['x-frame-options'] ) ) {
			$header_xframe = is_array( $headers['x-frame-options'] ) ? $headers['x-frame-options'][0] : $headers['x-frame-options'];

			$content = strtolower( trim( $header_xframe ) );
			//If deprecated header directive is ALLOW-FROM
			if ( stristr( $content, 'allow-from' ) ) {
				//then redirect to Sameorigin tab
				$model->sh_xframe_mode = 'sameorigin';
			} elseif ( in_array( strtolower( $content ), array( 'sameorigin', 'deny' ), true ) ) {
				$model->sh_xframe_mode = strtolower( $content );
			}
			$model->save();

			return true;
		}

		return false;
	}

	public function get_misc_data() {
		$model = $this->get_model();

		return array(
			'intro_text' => __( "The X-Frame-Options HTTP response header controls whether or not a browser can render a webpage inside a <frame>, <iframe> or <object> tag. Websites can avoid clickjacking attacks by ensuring that their content isn't embedded into other websites.", 'wpdef' ),
			/**
			 * Directive ALLOW-FROM is deprecated.
			 * @since 2.5.0
			*/
			'mode'       => ( isset( $model->sh_xframe_mode ) && 'allow-from' !== $model->sh_xframe_mode )
				? $model->sh_xframe_mode
				: 'sameorigin',
		);
	}

	public function add_hooks() {
		add_action( 'send_headers', array( $this, 'append_header' ) );
		add_filter( 'defender_filtering_data_settings', array( $this, 'filtering_headers' ) );
	}

	public function filtering_headers( $data ) {
		if ( ! isset( $data['sh_xframe'] ) ) {
			return $data;
		}
		//Directive ALLOW-FROM is deleted
		if ( isset( $data['sh_xframe_mode'] ) && 'allow-from' === $data['sh_xframe_mode'] ) {
			$data['sh_xframe_mode'] = 'sameorigin';
			return $data;
		}

		return $data;
	}

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
	 * @return string
	 */
	public function get_title() {
		return __( 'X-Frame-Options', 'wpdef' );
	}
}
