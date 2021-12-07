<?php

namespace WP_Defender\Behavior\Scan;

use Calotes\Base\File;
use Calotes\Component\Behavior;
use WP_Defender\Model\Setting\Scan;

/**
 * We will gather core files & content files, for using in core integrity.
 *
 * Class Gather_Fact
 * @package WP_Defender\Behavior\Scan
 */
class Gather_Fact extends Behavior {
	use \WP_Defender\Traits\IO;

	const CACHE_CORE = 'wdfcore', CACHE_CONTENT = 'wdfcontent';

	/**
	 * Gather core files & content files.
	 */
	public function gather_fact() {
		$timer       = time();
		$model       = $this->owner->scan;
		$need_to_run = empty( $model->task_checkpoint ) ? 'get_core_files' : 'get_content_files';
		if ( 'get_core_files' === $need_to_run ) {
			$settings = new Scan();
			if ( $settings->integrity_check && $settings->check_core ) {
				$this->get_core_files();
			}
			$model->calculate_percent( 50, 1 );
		} else {
			$this->get_content_files();
			$model->calculate_percent( 100, 1 );
		}
		$this->log( sprintf( '%s in %s', $need_to_run, time() - $timer ), 'scan.log' );
		$model->task_checkpoint = $need_to_run;
		$model->save();

		return 'get_content_files' === $need_to_run;
	}

	/**
	 * @return array|mixed
	 */
	private function get_core_files() {
		$cache = get_site_option( self::CACHE_CORE, false );
		if ( is_array( $cache ) ) {

			return $cache;
		}
		$abs_path = ABSPATH;
		if ( defender_is_windows() ) {
			// This mean we are on Windows.
			$abs_path = str_replace( '/', DIRECTORY_SEPARATOR, $abs_path );
		}
		$core = new \Calotes\Base\File(
			ABSPATH,
			true,
			false,
			array(
				'dir' => array(
					$abs_path . 'wp-admin',
					$abs_path . WPINC,
				),
			),
			array(),
			true,
			true
		);

		$outside = new File(
			$abs_path,
			true,
			true,
			array(),
			array(
				'dir'      => array(
					$abs_path . 'wp-content',
					$abs_path . 'wp-admin',
					$abs_path . 'wp-includes',
				),
				'filename' => array(
					'wp-config.php',
				),
			),
			false,
			true
		);

		$files = array_merge( $core->get_dir_tree(), $outside->get_dir_tree() );
		$files = array_filter( $files );
		$this->log( sprintf( 'Core: %s', count( $files ) ), 'scan.log' );
		update_site_option( self::CACHE_CORE, $files );

		return $files;
	}

	/**
	 * Return every php files inside wp-content.
	 *
	 * @return mixed
	 */
	private function get_content_files() {
		$cache = get_site_option( self::CACHE_CONTENT, false );
		if ( is_array( $cache ) ) {

			return $cache;
		}
		$content = new File(
			WP_CONTENT_DIR,
			true,
			false,
			array(
				'ext' => array(
					'php',
				),
			),
			array(),
			true,
			true,
			$this->owner->settings->filesize
		);

		$files   = $content->get_dir_tree();
		$files   = array_filter( $files );
		$files[] = defender_wp_config_path();
		$this->log( sprintf( 'Content: %s', count( $files ) ), 'scan.log' );
		update_site_option( self::CACHE_CONTENT, $files );
	}
}
