<?php
/**
 * Author: Hoang Ngo
 */

namespace Calotes\Base;

/**
 * Every class should extend this class.
 * This contains generic function for checking internal info.
 *
 * Class Base
 * @package Calotes\Base
 */
class Base {
	/**
	 * Store internal logging, mostly for debug.
	 *
	 * @var array
	 */
	protected $internal_logging = array();

	/**
	 * Store event handlers.
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
	 * Add a log for internal use, mostly for debug.
	 *
	 * @param string $message
	 * @param string $category
	 */
	protected function log( $message, $category = '' ) {
		if ( ! is_string( $message ) || is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		$this->internal_logging[] = date( 'Y-m-d H:i:s' ) . ' ' . $message;
		//uncomment it for detailed logging on wp cli
//		 if ( 'cli' === PHP_SAPI ) {
//		    echo $message . PHP_EOL;
//		 }

		$message = '[' . date( 'c' ) . '] ' . $message . PHP_EOL;

		if ( $this->has_method( 'get_log_path' ) ) {
			if ( ! empty( $category ) && 0 === preg_match( '/\.log$/', $category ) ) {
				$category .= '.log';
			}

			$file_path = $this->get_log_path( $category );
			$dir_name  = pathinfo( $file_path, PATHINFO_DIRNAME );

			if ( ! is_dir( $dir_name ) ) {
				wp_mkdir_p( $dir_name );
			}

			if ( is_writable( $dir_name ) ) {
				file_put_contents( $file_path, $message, FILE_APPEND );
			}
		}
	}
}
