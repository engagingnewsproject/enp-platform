<?php
/**
 * Array cache helper.
 *
 * @package Calotes\Helper
 */

namespace Calotes\Helper;

use Calotes\Base\Component;

/**
 * This is runtime cache, so the cache content will be flush after each refresh.
 */
class Array_Cache extends Component {

	/**
	 * The cached array.
	 *
	 * @var array
	 */
	protected static $cached = array();

	/**
	 * Sets a value in the cache.
	 *
	 * @param  mixed $name  The name of the value to set.
	 * @param  mixed $value  The value to set.
	 * @param  mixed $group  The group of the value (optional).
	 *
	 * @return void
	 */
	public static function set( $name, $value, $group = null ) {
		$key                  = $name . $group;
		self::$cached[ $key ] = $value;
	}

	/**
	 * Retrieves a value from the cache.
	 *
	 * @param  mixed $name  The name of the value to retrieve.
	 * @param  mixed $group  The group of the value (optional).
	 * @param  mixed $default_name  The default value to return if the value is not found (optional).
	 *
	 * @return mixed The retrieved value or the default value if not found.
	 */
	public static function get( $name, $group = null, $default_name = null ) {
		$key = $name . $group;

		return self::$cached[ $key ] ?? $default_name;
	}

	/**
	 * Appends a new element to a cached array.
	 *
	 * @param  mixed $name  The name of the value to append.
	 * @param  mixed $value  The value to append.
	 * @param  mixed $group  The group of the value (optional).
	 *
	 * @return void
	 */
	public static function append( $name, $value, $group = null ) {
		$data = self::get( $name, $group, array() );
		if ( is_array( $data ) ) {
			$data[] = $value;
		}
		self::set( $name, $data, $group );
	}

	/**
	 * Removes a value from the cache.
	 *
	 * @param  mixed $name  The name of the value to remove.
	 * @param  mixed $group  The group of the value (optional).
	 *
	 * @return bool True if the value was successfully removed, false otherwise.
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