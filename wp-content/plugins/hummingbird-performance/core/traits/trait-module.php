<?php
/**
 * Module trait.
 *
 * Required methods for abstract module class.
 *
 * @since 2.6.2
 * @package Hummingbird\Core\Traits
 */

namespace Hummingbird\Core\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Module
 */
trait Module {

	/**
	 * Initializes the module. Always executed even if the module is deactivated.
	 *
	 * @since 2.6.2
	 */
	public function init() {}

	/**
	 * Execute the module actions. Executed when module is active.
	 *
	 * @since 2.6.2
	 */
	public function run() {}

	/**
	 * Clear the module cache.
	 *
	 * @since 2.6.2
	 */
	public function clear_cache() {}

}
