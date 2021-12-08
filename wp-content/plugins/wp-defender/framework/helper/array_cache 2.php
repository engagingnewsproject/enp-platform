<?php

namespace Calotes\Helper;

use Calotes\Base\Component;

/**
 * This is runtime cache, so the cache content will be flush after each refresh
 *
 * Class Cache
 *
 * @package Calotes\Helper
 */
class Array_Cache extends Component {
	protected static $cached = array();

	/**
	 * @param $name
	 * @param $value
	 * @param null  $group
	 */
	public static function set( $name, $value, $group = null ) {
		$key                  = $name . $group;
		self::$cached[ $key ] = $value;
	}

	/**
	 * @param $name
	 * @param null $group
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public static function get( $name, $group = null, $default = null ) {
		$key = $name . $group;

		return isset( self::$cached[ $key ] ) ? self::$cached[ $key ] : $default;
	}

	/**
	 * Quick way for append new element to a cached array
	 *
	 * @param $name
	 * @param $value
	 * @param null  $group
	 */
	public static function append( $name, $value, $group = null ) {
		$data = self::get( $name, $group, array() );
		if ( is_array( $data ) ) {
			$data[] = $value;
		}
		self::set( $name, $data, $group );
	}

	/**
	 * @param $name
	 * @param null $group
	 *
	 * @return bool
	 */
	public static function remove( $name, $group = null ) {
		$key = $name . $group;
		if ( isset( self::$cached[ $key ] ) ) {
			unset( self::$cached[ $key ] );

			return true;
		}

		return false;
	}
}