<?php
/**
 * Request class.
 *
 * @package Calotes\Component
 */

namespace Calotes\Component;

use Calotes\Base\Model;
use Calotes\Helper\HTTP;

/**
 * Parse and validate request data.
 */
class Request {

	/**
	 * Store the data from request.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor to initialize the object with data from the POST or GET request.
	 */
	public function __construct() {
		if ( 'POST' === defender_get_data_from_request( 'REQUEST_METHOD', 's' ) ) {
			$this->data = HTTP::post( 'data', '' );
		} else {
			$this->data = HTTP::get( 'data', '' );
		}
	}

	/**
	 * Retrieve the data that will be in use, it's recommended that $filters should be provided for data validation and
	 * cast.
	 *
	 * @param  array $filters  The filters to apply.
	 *
	 * @return array
	 */
	public function get_data( $filters = array() ) {
		if ( empty( $filters ) ) {
			return $this->data;
		}
		$data = array();
		foreach ( $filters as $key => $rule ) {
			if ( ! isset( $this->data[ $key ] ) ) {
				continue; // Moving on.
			}
			// Mandatory.
			$type     = $rule['type'];
			$sanitize = $rule['sanitize'] ?? null;

			$value = $this->data[ $key ];
			// Cast.
			settype( $value, $type );
			if ( ! is_array( $sanitize ) ) {
				$sanitize = array( $sanitize );
			}
			foreach ( $sanitize as $function ) {
				if ( null !== $function && function_exists( $function ) ) {
					if ( is_array( $value ) ) {
						$value = $this->sanitize_array( $value, $function );
					} else {
						$value = $function( $value );
					}
				}
			}
			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Sanitize an array recursively.
	 *
	 * @param  array $arr  The array to sanitize.
	 * @param  mixed $sanitize  The sanitization function to apply.
	 *
	 * @return array The sanitized array.
	 */
	protected function sanitize_array( $arr, $sanitize ) {
		foreach ( $arr as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->sanitize_array( $value, $sanitize );
			} else {
				$value = $sanitize( $value );
			}
		}

		return $arr;
	}

	/**
	 * Get the data from _REQUEST.
	 *
	 * @param  Model $model  The model to get data from.
	 *
	 * @return array
	 */
	public function get_data_by_model( Model $model ) {
		return $this->get_data( $model->annotations );
	}
}