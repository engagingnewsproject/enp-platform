<?php

namespace Calotes\Component;

use Calotes\Base\Model;
use Calotes\Helper\HTTP;

/**
 * This will be passed to every defender_route, This will get data from _POST or _GET and sanitize it before
 * land to the actual process method
 *
 * Class Request
 *
 * @package Calotes\Component
 */
class Request {
	/**
	 * store the data from request
	 *
	 * @var array
	 */
	protected $data = array();

	public function __construct() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$raw_data = HTTP::post( 'data', false );
		} else {
			$raw_data = HTTP::get( 'data', false );
		}
		if ( false !== $raw_data ) {
			$this->data = json_decode( $raw_data, true );
		}
	}

	/**
	 * Retrieve the data that will be in use, it is recommend that a $filters should be provide for data validation and cast
	 *
	 * @param array $filters
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
				continue; // moving on
			}
			// mandatory
			$type     = $rule['type'];
			$sanitize = isset( $rule['sanitize'] ) ? $rule['sanitize'] : null;

			$value = $this->data[ $key ];
			// cast
			settype( $value, $type );
			if ( ! is_array( $sanitize ) ) {
				$sanitize = [ $sanitize ];
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
	 * @param $arr
	 * @param $sanitize
	 *
	 * @return mixed
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
	 * Get the data from _REQUEST
	 *
	 * @param Model $model
	 *
	 * @return array
	 */
	public function get_data_by_model( Model $model ) {
		return $this->get_data( $model->annotations );
	}
}
