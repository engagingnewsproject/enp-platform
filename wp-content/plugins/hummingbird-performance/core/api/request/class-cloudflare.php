<?php
/**
 * Class for requests to Cloudflare API.
 *
 * @package Hummingbird\Core\Api\Request
 */

namespace Hummingbird\Core\Api\Request;

use Hummingbird\Core\Api\Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cloudflare
 */
class Cloudflare extends Request {

	/**
	 * Auth email
	 *
	 * @var string
	 */
	private $auth_email = '';

	/**
	 * Auth key
	 *
	 * @var string
	 */
	private $auth_key = '';

	/**
	 * Auth token
	 *
	 * @var string
	 */
	private $auth_token = '';

	/**
	 * Cloudflare zone
	 *
	 * @var string
	 */
	private $zone = '';

	/**
	 * Get API URL.
	 *
	 * @param string $path  API path.
	 *
	 * @return mixed|string|string[]
	 */
	public function get_api_url( $path = '' ) {
		$url = 'https://api.cloudflare.com/client/v4/' . $path;
		return str_replace( '%ZONE%', $this->zone, $url );
	}

	/**
	 * Sign request.
	 */
	protected function sign_request() {
		if ( ! empty( $this->auth_key ) && ! empty( $this->auth_email ) ) {
			$this->add_header_argument( 'X-Auth-Key', $this->auth_key );
			$this->add_header_argument( 'X-Auth-Email', $this->auth_email );
		}

		if ( ! empty( $this->auth_token ) ) {
			$this->add_header_argument( 'Authorization', 'Bearer ' . $this->auth_token );
		}
	}

	/**
	 * Set zone.
	 *
	 * @param string $zone  Zone.
	 */
	public function set_zone( $zone ) {
		$this->zone = $zone;
	}

	/**
	 * Set auth email.
	 *
	 * @param string $email  Email.
	 */
	public function set_auth_email( $email ) {
		$this->auth_email = $email;
	}

	/**
	 * Set auth key.
	 *
	 * @param string $key  API key.
	 */
	public function set_auth_key( $key ) {
		$this->auth_key = $key;
	}

	/**
	 * Set auth token.
	 *
	 * @since 3.1.0
	 *
	 * @param string $token  API token.
	 */
	public function set_auth_token( $token ) {
		$this->auth_token = $token;
	}

	/**
	 * Make an API Request
	 *
	 * @param string $path    Path.
	 * @param array  $data    Arguments array.
	 * @param string $method  Method.
	 *
	 * @throws Exception  Exception.
	 *
	 * @return array|mixed|object
	 */
	public function request( $path, $data = array(), $method = 'post' ) {
		$this->add_header_argument( 'Content-Type', 'application/json' );

		$response = parent::request( $path, $data, $method );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message(), $response->get_error_code() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );
		if ( $body && 200 !== (int) $code ) {
			if ( isset( $body->errors ) ) {
				/* translators: %s: cloudflare error */
				throw new Exception( sprintf( __( 'Cloudflare error: %s', 'wphb' ), $body->errors[0]->message ), $code );
			}

			throw new Exception(
				printf( /* translators: %1$s - error code, %2$s - error description */
					esc_html__( 'Cloudflare error code %1$s, error code: %2$s', 'wphb' ),
					absint( $body->code ),
					esc_attr( $body->error )
				)
			);
		} elseif ( false === $body ) {
			throw new Exception( __( 'Cloudflare unknown error', 'wphb' ), $code );
		}

		return $body;

	}

}
