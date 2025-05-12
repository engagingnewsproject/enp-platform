<?php
/**
 * Function for caching between runs.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Util;

class Cache {

	/**
	 * The filesystem location of the cache file.
	 *
	 * @var void
	 */
	private static $path = '';

	/**
	 * The cached data.
	 *
	 * @var array<string, mixed>
	 */
	private static $cache = [];

	/**
	 * Saves the current cache to the filesystem.
	 *
	 * @return void
	 */
	public static function save() {
		file_put_contents( self::$path, json_encode( self::$cache ) );

	}//end save()


	/**
	 * Retrieves a single entry from the cache.
	 *
	 * @param string $key The key of the data to get. If NULL,
	 *                    everything in the cache is returned.
	 *
	 * @return mixed
	 */
	public static function get( $key = null ) {
		if ( $key === null ) {
			return self::$cache;
		}

		if ( isset( self::$cache[ $key ] ) === true ) {
			return self::$cache[ $key ];
		}

		return false;

	}//end get()


	/**
	 * Retrieves a single entry from the cache.
	 *
	 * @param string $key The key of the data to set. If NULL,
	 *                      sets the entire cache.
	 * @param mixed $value The value to set.
	 *
	 * @return void
	 */
	public static function set( $key, $value ) {
		if ( $key === null ) {
			self::$cache = $value;
		} else {
			self::$cache[ $key ] = $value;
		}

	}//end set()


	/**
	 * Retrieves the number of cache entries.
	 *
	 * @return int
	 */
	public static function getSize() {
		return ( count( self::$cache ) - 1 );

	}//end getSize()


}//end class