<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

class Sh_Feature_Policy extends Security_Header {
	static $rule_slug = 'sh_feature_policy';

	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_feature_policy ) {
			return false;
		}
		if ( isset( $model->sh_feature_policy_mode ) && ! empty( $model->sh_feature_policy_mode ) ) {
			return true;
		}
		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ) );

			return false;
		}
		if ( isset( $headers['feature-policy'] ) ) {
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
			'intro_text' => esc_html__( 'The Permissions-Policy response header provides control over what browser features can be used when web pages are embedded in iframes.', 'wpdef' ),
			'mode'       => isset( $model->sh_feature_policy_mode ) ? $model->sh_feature_policy_mode : 'self',
			'values'     => isset( $model->sh_feature_policy_urls ) ? $model->sh_feature_policy_urls : '',
		);
	}

	public function add_hooks() {
		add_action( 'send_headers', array( $this, 'append_header' ) );
		add_filter( 'defender_filtering_data_settings', array( $this, 'filtering_headers' ) );
	}

	public function filtering_headers( $data ) {
		if ( ! isset( $data['sh_feature_policy'] ) ) {
			return $data;
		}
		if ( 'origins' !== $data['sh_feature_policy_mode'] || empty( $data['sh_feature_policy_urls'] ) ) {
			return $data;
		}
		$urls = sanitize_textarea_field( $data['sh_feature_policy_urls'] );
		$urls = explode( PHP_EOL, $urls );
		$urls = array_map( 'trim', $urls );
		foreach ( $urls as $key => $url ) {
			if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
				unset( $urls[ $key ] );
			}
		}
		$data['sh_feature_policy_urls'] = implode( PHP_EOL, $urls );

		return $data;
	}

	public function append_header() {
		if ( headers_sent() ) {
			return;
		}
		$model = $this->get_model();
		if ( ! $this->maybe_submit_header( 'Permissions-Policy', false ) ) {

			return;
		}

		if ( true === $model->sh_feature_policy
			&& isset( $model->sh_feature_policy_mode )
			&& in_array( $model->sh_feature_policy_mode, array( 'self', 'allow', 'origins', 'none' ), true )
		) {
			$headers  = '';
			// @since 2.6.1
			$features = apply_filters( 'wd_permissions_policy_directives',
				array(
					'accelerometer',
					'autoplay',
					'camera',
					'encrypted-media',
					'fullscreen',
					'geolocation',
					'gyroscope',
					'magnetometer',
					'microphone',
					'midi',
					'payment',
					'usb',
				)
			);

			switch ( $model->sh_feature_policy_mode ) {
				case 'self':
					array_walk(
						$features,
						function ( &$value ) {
							$value .= "=(self)";
						}
					);
					$headers = 'Permissions-Policy: ' . implode( ', ', $features );
					break;
				case 'allow':
					array_walk(
						$features,
						function ( &$value ) {
							$value .= '=*';
						}
					);
					$headers = 'Permissions-Policy: ' . implode( ', ', $features );
					break;
				case 'origins':
					if ( isset( $model->sh_feature_policy_urls ) && ! empty( $model->sh_feature_policy_urls ) ) {
						$urls = explode( PHP_EOL, $model->sh_feature_policy_urls );
						$urls = array_map( 'trim', $urls );
						// Wrap strings in quotes.
						array_walk( $urls, function( &$x ) { $x = '"' . $x . '"'; } );
						$urls = implode( ' ', $urls );
						array_walk(
							$features,
							function ( &$value ) use ( $urls ) {
								$value .= '=(' . $urls . ')';
							}
						);
						$headers = 'Permissions-Policy: ' . implode( ', ', $features );
					}
					break;
				case 'none':
					array_walk(
						$features,
						function ( &$value ) {
							$value .= "=()";
						}
					);
					$headers = 'Permissions-Policy: ' . implode( ', ', $features );
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
	 * @return string
	 */
	public function get_title() {
		return __( 'Permissions-Policy', 'wpdef' );
	}
}