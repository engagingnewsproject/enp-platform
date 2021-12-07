<?php
/**
 * Class Rss
 *
 * @since 1.8
 * @package Hummingbird
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Traits\Module as ModuleContract;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rss
 *
 * @package Hummingbird\Core\Modules
 */
class Rss extends Module {

	use ModuleContract;

	/**
	 * Initialize module.
	 */
	public function init() {
		add_action( 'wp_feed_options', array( $this, 'rss_caching_status' ) );
	}

	/**
	 * Return true if the module is activated.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! Settings::get_setting( 'enabled', $this->get_slug() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Activate module.
	 *
	 * @since 1.9.0
	 */
	public function enable() {
		Settings::update_setting( 'enabled', true, $this->get_slug() );
	}

	/**
	 * Deactivate module.
	 *
	 * @since 1.9.0
	 */
	public function disable() {
		Settings::update_setting( 'enabled', false, $this->get_slug() );
	}

	/**
	 * Set caching status.
	 *
	 * @param object $feed  SimplePie feed object (passed by reference).
	 */
	public function rss_caching_status( $feed ) {
		$options = $this->get_options();

		$feed->enable_cache( $options['enabled'] );
		$feed->set_cache_duration( $options['duration'] );
	}

}
