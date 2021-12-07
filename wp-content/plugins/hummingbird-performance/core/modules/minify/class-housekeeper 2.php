<?php
/**
 * Class Housekeeper
 *
 * Housekeeping duties to clean old minification files
 *
 * @package Hummingbird\Core\Modules\Minify
 */

namespace Hummingbird\Core\Modules\Minify;

use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Housekeeper
 */
class Housekeeper {

	/**
	 * Init method.
	 */
	public function init() {
		if ( ! wp_next_scheduled( 'wphb_minify_clear_files' ) ) {
			wp_schedule_event( time(), 'daily', 'wphb_minify_clear_files' );
		}

		add_action( 'wphb_minify_clear_files', array( $this, 'clear_expired_groups' ) );
	}

	/**
	 * Clear all minified and expired groups
	 *
	 * Sometimes minification module will not clear them by itself because they
	 * belong to a plugin or theme that is deactivated so minification won't get them anymore.
	 * This cron job will clear the expired files once a day
	 */
	public static function clear_expired_groups() {
		$maybe_clear_page_cache = false;

		$groups = Minify_Group::get_minify_groups();
		foreach ( $groups as $group ) {
			$instance = Minify_Group::get_instance_by_post_id( $group->ID );
			if ( ( $instance instanceof Minify_Group ) && $instance->is_expired() && $instance->file_id ) {
				$instance->delete_file();
				wp_delete_post( $instance->file_id, true );
				$maybe_clear_page_cache = true;
			}
		}

		if ( $maybe_clear_page_cache ) {
			self::maybe_clear_page_cache();
		}
	}

	/**
	 * When clearing expired assets, it is important that the page cache is also purged,
	 * otherwise that leads to various errors on the site.
	 *
	 * @since 2.0.0
	 */
	private static function maybe_clear_page_cache() {
		if ( Settings::get_setting( 'enabled', 'page_cache' ) ) {
			Utils::get_module( 'page_cache' )->clear_cache();
		}
	}

}
