<?php
/**
 * Preload caache files.
 *
 * @since 2.1.0
 * @package Hummingbird\Core\Modules\Caching
 */

namespace Hummingbird\Core\Modules\Caching;

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Preload
 */
class Preload extends Background_Process {

	/**
	 * Database row prefix.
	 *
	 * @since 2.1.0
	 * @var string $prefix
	 */
	protected $prefix = 'wphb';

	/**
	 * Unique process ID.
	 *
	 * @since 2.1.0
	 * @var string $action
	 */
	protected $action = 'cache_preload';

	/**
	 * Task that does the preloading of each item (url).
	 *
	 * @param mixed $item  Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$args = array(
			'timeout'    => 0.01,
			'blocking'   => false,
			'user-agent' => 'Hummingbird ' . WPHB_VERSION . '/Cache Preloader',
			'sslverify'  => false,
		);

		wp_remote_get( esc_url_raw( $item ), $args );
		usleep( 500000 );

		return false;
	}

	/**
	 * Populate the queue for preloading with the provided URL, or preload all pages.
	 *
	 * @since 2.1.0
	 *
	 * @param string $url  URL of the page to preload. Leave blank to preload all.
	 */
	private function preload( $url ) {
		// Try to avoid recursive loops.
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			if ( preg_match( '/Hummingbird.+?\/Cache Preloader/', $user_agent ) ) {
				return;
			}
		}

		$this->push_to_queue( $url );
		$this->save()->dispatch();
	}

	/**
	 * Check if the desired path is already cached in the filesystem.
	 *
	 * @since 2.7.3
	 *
	 * @param string $path  Path to cacche.
	 *
	 * @return bool
	 */
	private function is_cached( $path ) {
		global $wphb_fs;

		// Init filesystem.
		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		$http_host = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_host = htmlentities( wp_unslash( $_SERVER['HTTP_HOST'] ) ); // Input var ok.
		} elseif ( function_exists( 'get_option' ) ) {
			$http_host = preg_replace( '/https?:\/\//', '', get_option( 'siteurl' ) );
		}

		return is_dir( $wphb_fs->cache_dir . $http_host . $path );
	}

	/**
	 * Callback function after clearing cache for a page/post.
	 *
	 * @since 2.1.0
	 *
	 * @param string $path  Path to page.
	 */
	public function preload_page_on_purge( $path ) {
		// Do not parse empty paths.
		if ( ! $path ) {
			return;
		}

		// Do not preload if not enabled.
		$enabled = Settings::get_setting( 'preload', 'page_cache' );
		if ( ! $enabled ) {
			return;
		}

		if ( $this->is_cached( $path ) ) {
			return;
		}

		$types = Settings::get_setting( 'preload_type', 'page_cache' );

		if ( isset( $types['on_clear'] ) && $types['on_clear'] && ! $this->is_process_running() ) {
			$url = get_option( 'home' ) . $path;
			$this->preload( $url );
		}
	}

	/**
	 * Preload home page.
	 *
	 * @since 2.3.0
	 */
	public function preload_home_page() {
		if ( $this->is_process_running() ) {
			return;
		}

		$this->preload( get_option( 'home' ) );
	}

}
