<?php
/**
 * Handles the implementation of the Permissions-Policy header for security purposes.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the Permissions-Policy header which controls browser features.
 */
class Sh_Feature_Policy extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_feature_policy';

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
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
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
		}
		if ( isset( $headers['feature-policy'] ) ) {
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
				'The Permissions-Policy response header provides control over what browser features can be used when web pages are embedded in iframes.',
				'wpdef'
			),
			'mode'       => $model->sh_feature_policy_mode ?? 'self',
			'values'     => $model->sh_feature_policy_urls ?? '',
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
		if ( ! $this->maybe_submit_header( 'Permissions-Policy', false ) ) {

			return;
		}

		if ( true === $model->sh_feature_policy
			&& isset( $model->sh_feature_policy_mode )
			&& in_array( $model->sh_feature_policy_mode, array( 'self', 'allow', 'origins', 'none' ), true )
		) {
			$headers = '';
			/**
			 * Filter the list of features for the Permissions-Policy header.
			 *
			 * @since 2.6.1
			 *
			 * @param array $features An array of features.
			 */
			$features = apply_filters(
				'wd_permissions_policy_directives',
				// Default list of features.
				array(
					'accelerometer', // Enable access to the Accelerometer API.
					'autoplay', // Allow media to be automatically played.
					'camera', // Enable access to the Camera API.
					'encrypted-media', // Require encrypted media to be played.
					'fullscreen', // Allow the fullscreen mode.
					'geolocation', // Enable access to the Geolocation API.
					'gyroscope', // Enable access to the Gyroscope API.
					'magnetometer', // Enable access to the Magnetometer API.
					'microphone', // Enable access to the Microphone API.
					'midi', // Allow access to MIDI devices.
					'payment', // Allow websites to make payments.
					'usb', // Allow websites to access USB devices.
				)
			);

			switch ( $model->sh_feature_policy_mode ) {
				case 'self':
					array_walk(
						$features,
						function ( &$value ) {
							$value .= '=(self)';
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
						array_walk(
							$urls,
							function ( &$x ) {
								$x = '"' . $x . '"';
							}
						);
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
							$value .= '=()';
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
	 * Retrieves the title of the Permissions-Policy.
	 *
	 * @return string The title of the Permissions-Policy.
	 */
	public function get_title() {
		return esc_html__( 'Permissions-Policy', 'wpdef' );
	}
}