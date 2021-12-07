<?php
/**
 * Abstract request class.
 *
 * @package Hummingbird\Core\Api\Request
 */

namespace Hummingbird\Core\Api\Request;

use Hummingbird\Core\Api\Exception;
use Hummingbird\Core\Api\Service\Service;
use Hummingbird\Core\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Request
 */
abstract class Request {

	/**
	 * API Key
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Module action path
	 *
	 * @var string
	 */
	private $path = '';

	/**
	 * Service.
	 *
	 * @var null|Service
	 */
	private $service = null;

	/**
	 * Request Method
	 *
	 * @var string
	 */
	private $method = 'POST';

	/**
	 * Request max timeout
	 *
	 * @var int
	 */
	private $timeout = 15;

	/**
	 * Header arguments
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * POST arguments
	 *
	 * @var array
	 */
	private $post_args = array();

	/**
	 * GET arguments
	 *
	 * @var array
	 */
	private $get_args = array();

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Request constructor.
	 *
	 * @param Service $service  API service instance.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct( $service ) {
		$this->logger = Logger::get_instance();
		$this->logger->register_module( 'api' );

		if ( ! $service instanceof Service ) {
			throw new Exception( __( 'Wrong Service. $service must be an instance of Hummingbird\\Core\\Api\\Service\\Service', 'wphb' ), 404 );
		}

		$this->service = $service;
	}

	/**
	 * Get the service.
	 *
	 * @return Service|null
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Set the Request API Key
	 *
	 * @param string $api_key  API key.
	 */
	public function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Set the Request API Timeout
	 *
	 * @param int|float $timeout  Timeout.
	 */
	public function set_timeout( $timeout ) {
		$this->timeout = $timeout;
	}

	/**
	 * Add a new request argument for POST requests
	 *
	 * @param string $name   Argument name.
	 * @param string $value  Argument value.
	 */
	public function add_post_argument( $name, $value ) {
		$this->post_args[ $name ] = $value;
	}

	/**
	 * Add a new request argument for GET requests
	 *
	 * @param string $name   Argument name.
	 * @param string $value  Argument value.
	 */
	public function add_get_argument( $name, $value ) {
		$this->get_args[ $name ] = $value;
	}

	/**
	 * Add a new request argument for GET requests
	 *
	 * @param string $name   Argument name.
	 * @param string $value  Argument value.
	 */
	public function add_header_argument( $name, $value ) {
		$this->headers[ $name ] = $value;
	}

	/**
	 * Get the Request URL
	 *
	 * @param string $path  Endpoint route.
	 *
	 * @return mixed
	 */
	abstract public function get_api_url( $path = '' );

	/**
	 * Make a GET API Call
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function get( $path, $data = array() ) {
		try {
			return $this->request( $path, $data, 'get' );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Make a GET API Call
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function post( $path, $data = array() ) {
		try {
			return $this->request( $path, $data );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Make a PATCH API Call
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function patch( $path, $data = array() ) {
		try {
			return $this->request( $path, $data, 'patch' );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Make a GET API Call
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function head( $path, $data = array() ) {
		try {
			return $this->request( $path, $data, 'head' );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Make a GET API Call
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function delete( $path, $data = array() ) {
		try {
			return $this->request( $path, $data, 'delete' );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Make a PURGE API Call
	 *
	 * @since 2.1.0
	 *
	 * @param string $path  Endpoint route.
	 * @param array  $data  Data.
	 *
	 * @return mixed
	 */
	public function purge( $path, $data = array() ) {
		try {
			return $this->request( $path, $data, 'purge' );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Make an API Request
	 *
	 * @since 1.8.1 Timeout for non-blocking changed from 0.1 to 2 seconds.
	 *
	 * @param string $path    Path.
	 * @param array  $data    Arguments array.
	 * @param string $method  Method.
	 *
	 * @return array|mixed|object
	 */
	public function request( $path, $data = array(), $method = 'post' ) {
		$url = $this->get_api_url( $path );

		$this->sign_request();

		$url = add_query_arg( $this->get_args, $url );
		if ( 'post' !== $method && 'patch' !== $method && 'delete' !== $method ) {
			$url = add_query_arg( $data, $url );
		}

		$args = array(
			'headers'   => $this->headers,
			'sslverify' => false,
			'method'    => strtoupper( $method ),
			'timeout'   => $this->timeout,
		);

		if ( ! $args['timeout'] || 2 === $args['timeout'] ) {
			$args['blocking'] = false;
		}

		$this->logger->log( "WPHB API: Sending request to {$url}", 'api' );
		$this->logger->log( 'WPHB API: Arguments:', 'api' );
		$this->logger->log( $args, 'api' );

		switch ( strtolower( $method ) ) {
			case 'patch':
			case 'delete':
			case 'post':
				if ( is_array( $data ) ) {
					$args['body'] = array_merge( $data, $this->post_args );
				} else {
					$args['body'] = $data;
				}

				$response = wp_remote_post( $url, $args );
				break;
			case 'head':
				$response = wp_remote_head( $url, $args );
				break;
			case 'get':
				$response = wp_remote_get( $url, $args );
				break;
			case 'purge':
			default:
				$response = wp_remote_request( $url, $args );
				break;
		}

		$this->logger->log( 'WPHB API: Response:', 'api' );
		$this->logger->log( $response, 'api' );

		return $response;
	}

	/**
	 * Sign request.
	 */
	protected function sign_request() {}

}
