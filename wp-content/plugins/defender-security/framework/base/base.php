<?php
/**
 * Author: Hoang Ngo
 */

namespace Calotes\Base;

/**
 * Every class should extend this class.
 *
 * This contains generic function for checking internal info
 *
 * Class Object
 *
 * @package Calotes\Base
 */
class Base {
	/**
	 * Use to store internal logging, mostly for debug
	 *
	 * @var array
	 */
	protected $internal_logging = array();

	/**
	 * Store event handlers
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * @param $property
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function has_property( $property ) {
		$ref = new \ReflectionClass( $this );

		return $ref->hasProperty( $property );
	}

	/**
	 * @param $method
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function has_method( $method ) {
		$ref = new \ReflectionClass( $this );

		return $ref->hasMethod( $method );
	}

	/**
	 * Add a log for internal use, mostly for debug
	 *
	 * @param string $message
	 * @param string $category
	 */
	protected function log( $message, $category = '' ) {
		$this->internal_logging[] = date( 'Y-m-d H:i:s' ) . ' ' . $message;
		// if ( 'cli' === php_sapi_name() ) {
		// echo $message . PHP_EOL;
		// }
		if ( $this->has_method( 'get_log_path' ) ) {
			file_put_contents( $this->get_log_path( $category ), $message . PHP_EOL, FILE_APPEND );
		}
	}
}
