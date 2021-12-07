<?php

namespace WP_Defender;

use Calotes\Helper\HTTP;
use Calotes\Model\Setting;
use WP_Defender\Traits\Permission;

abstract class Controller2 extends \Calotes\Base\Controller {
	use \WP_Defender\Traits\IO;
	use \WP_Defender\Traits\User;
	use Permission;

	/**
	 * @var string
	 */
	protected $parent_slug = 'wp-defender';


	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget
	 *
	 * @return array
	 */
	abstract public function data_frontend();

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc
	 *
	 * @return array
	 */
	abstract public function to_array();

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset
	 *
	 * @param $data array
	 *
	 * @return boolean
	 */
	abstract public function import_data( $data );

	/**
	 * Remove all settings, configs generated in this container runtime
	 *
	 * @return mixed
	 */
	abstract public function remove_settings();

	/**
	 * Remove all data
	 *
	 * @return mixed
	 */
	abstract public function remove_data();

	/**
	 * Export strings
	 *
	 * @return array
	 */
	abstract public function export_strings();

	/**
	 * An internal cache
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Queue mandatory assets
	 */
	public function enqueue_main_assets() {
		if ( $this->is_page_active() ) {
			wp_enqueue_script( 'clipboard' );
			wp_enqueue_style( 'defender' );
			wp_enqueue_script( 'wpmudev-sui' );
		}
	}

	/**
	 * This too check if the current page is active so we can queue right assets
	 *
	 * @return bool
	 */
	public function is_page_active() {
		$current = HTTP::get( 'page' );

		return $current === $this->slug;
	}

	/**
	 * Quick way for saving settings
	 *
	 * @param Setting $model
	 * @param $category
	 *
	 * @return bool
	 */
	public function default_update_settings( Setting &$model, $category ) {
		if ( ! $this->check_permission() ) {
			return false;
		}
		if ( ! $this->verify_nonce( 'update_settings' . $category ) ) {
			return false;
		}

		$data = HTTP::post( 'data' );
		$data = apply_filters( 'defender_filtering_data_settings', $data );
		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();

			// bind for submit data to DEV
			if ( ! wp_next_scheduled( 'defender_hub_sync' ) ) {
				wp_schedule_single_event( time(), 'defender_hub_sync' );
			}

			return true;
		}

		return false;
	}

	public function queue_to_sync_with_hub() {
		if ( ! wp_next_scheduled( 'defender_hub_sync' ) ) {
			wp_schedule_single_event( time(), 'defender_hub_sync' );
		}
	}

	/**
	 * Read through this class and generate a list of intention method, register it with the central
	 * The methods that have annotation @defender_method will be registered automatically
	 */
	public function register_routes() {
		foreach ( $this->get_methods() as $method ) {
			$doc_block = $method->getDocComment();
			if ( stristr( $doc_block, '@defender_route' ) ) {
				if ( 'register_routes' === $method->getName() ) {
					continue;
				}
				$is_public = stristr( $doc_block, '@is_public' );
				wd_central()->add_route( $method->getName(), static::class, ! $is_public );
			}
		}
	}

	/**
	 * Return all methods from current class
	 *
	 * @return \ReflectionMethod[]
	 */
	private function get_methods() {
		$class = new \ReflectionClass( static::class );

		return $class->getMethods( \ReflectionMethod::IS_PUBLIC );
	}

	/**
	 * Dump the route and nonces
	 *
	 * @return array[]
	 */
	public function dump_routes_and_nonces() {
		$nonces = array();
		$routes = array();
		foreach ( $this->get_methods() as $method ) {
			$doc_block = $method->getDocComment();
			if ( stristr( $doc_block, '@defender_route' ) ) {
				if ( 'register_routes' === $method->getName() ) {
					continue;
				}
				$nonces[ $method->getName() ] = wd_central()->get_nonce( $method->getName(), static::class );
				$routes[ $method->getName() ] = wd_central()->get_route( $method->getName(), static::class );
			}
		}

		return array(
			'routes' => $routes,
			'nonces' => $nonces,
		);
	}
}