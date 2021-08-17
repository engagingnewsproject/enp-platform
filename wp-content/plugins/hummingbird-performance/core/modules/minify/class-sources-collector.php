<?php
/**
 * Source collector class.
 *
 * Manages the collection of all sources that WP Hummingbird is going to compress
 *
 * @package Hummingbird\Core\Modules\Minify
 */

namespace Hummingbird\Core\Modules\Minify;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sources_Collector
 */
class Sources_Collector {

	/**
	 * Where to store styles.
	 *
	 * @var string $styles_option
	 */
	private static $styles_option = 'wphb_styles_collection';

	/**
	 * Where to store scripts.
	 *
	 * @var string $scripts_option
	 */
	private static $scripts_option = 'wphb_scripts_collection';

	/**
	 * Has the collection of assets been updated.
	 *
	 * @var bool $collection_updated
	 */
	private $collection_updated = false;

	/**
	 * Collected assets so far.
	 *
	 * @var array $collected
	 */
	private $collected = array(
		'styles'  => array(),
		'scripts' => array(),
	);

	/**
	 * Sources_Collector constructor.
	 */
	public function __construct() {
		$this->collected = self::get_collection();
	}

	/**
	 * Save collection to the database.
	 */
	public function save_collection() {
		if ( $this->collection_updated ) {
			update_option( self::$styles_option, $this->collected['styles'] );
			update_option( self::$scripts_option, $this->collected['scripts'] );
		}
	}

	/**
	 * Add asset to collection.
	 *
	 * @param array|string $registered  Array of registered assets.
	 * @param string       $type        Type of asset.
	 */
	public function add_to_collection( $registered, $type ) {
		$registered = (array) $registered;

		if ( isset( $this->collected[ $type ][ $registered['handle'] ] ) && $registered === $this->collected[ $type ][ $registered['handle'] ] ) {
			return;
		}

		$this->collection_updated                          = true;
		$this->collected[ $type ][ $registered['handle'] ] = $registered;
	}

	/**
	 * Get collection.
	 *
	 * @return array
	 */
	public static function get_collection() {
		return array(
			'styles'  => get_option( self::$styles_option, array() ),
			'scripts' => get_option( self::$scripts_option, array() ),
		);
	}

	/**
	 * Clear collection.
	 */
	public static function clear_collection() {
		delete_option( self::$styles_option );
		delete_option( self::$scripts_option );
	}

	/**
	 * Get handles from both styles and scripts.
	 *
	 * @return array
	 */
	public static function get_handles() {
		$styles  = get_option( self::$styles_option, array() );
		$scripts = get_option( self::$scripts_option, array() );
		$handles = array();
		foreach ( $styles as $style ) {
			$handles[] = $style['handle'];
		}
		foreach ( $scripts as $script ) {
			$handles[] = $script['handle'];
		}

		return $handles;
	}

	/**
	 * Remove asset from collection.
	 *
	 * @param string $handle  Asset handle.
	 * @param string $type    Asset type.
	 */
	public static function clear_handle_from_collection( $handle, $type ) {
		$collection = self::get_collection();
		if ( ! isset( $collection[ $type ][ $handle ] ) ) {
			return;
		}

		unset( $collection[ $type ][ $handle ] );

		update_option( self::$styles_option, $collection['styles'] );
		update_option( self::$scripts_option, $collection['scripts'] );
	}

}
