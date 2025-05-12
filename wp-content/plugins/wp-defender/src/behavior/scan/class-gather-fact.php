<?php
/**
 * This file contains logic for gathering facts for scan.
 *
 * @package WP_Defender\Behavior\Scan
 */

namespace WP_Defender\Behavior\Scan;

use Calotes\Base\File;
use WP_Defender\Traits\IO;
use WP_Defender\Model\Scan;
use Calotes\Base\Component;
use WP_Defender\Component\Timer;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * We will gather core files & content files, for using in core integrity.
 */
class Gather_Fact extends Component {

	use IO;

	public const CACHE_CORE = 'wdfcore', CACHE_CONTENT = 'wdfcontent';

	/**
	 * Holds the Scan model or null if not set.
	 *
	 * @var Scan|null
	 */
	private ?Scan $scan;

	/**
	 * Holds the WPMUDEV object or null if not set.
	 *
	 * @var WPMUDEV|null
	 */
	private ?WPMUDEV $wpmudev;

	/**
	 * Holds the Scan settings or null if not set.
	 *
	 * @var Scan_Settings|null
	 */
	private ?Scan_Settings $settings;

	/**
	 * Constructor for the Gather_Fact class.
	 *
	 * @param  WPMUDEV       $wpmudev  The WPMUDEV object.
	 * @param  Scan          $scan  The Scan model.
	 * @param  Scan_Settings $scan_settings  The Scan settings.
	 */
	public function __construct( WPMUDEV $wpmudev, Scan $scan, Scan_Settings $scan_settings ) {
		$this->wpmudev  = $wpmudev;
		$this->scan     = $scan;
		$this->settings = $scan_settings;
	}

	/**
	 * Gather core files & content files.
	 *
	 * @return bool
	 */
	public function gather_info(): bool {
		$timer       = new Timer();
		$model       = $this->scan;
		$need_to_run = empty( $model->task_checkpoint ) ? 'get_core_files' : 'get_content_files';
		if ( 'get_core_files' === $need_to_run ) {
			if ( $this->settings->integrity_check && $this->settings->check_core ) {
				$this->get_core_files();
			}
			$model->calculate_percent( 50, 1 );
		} else {
			$this->get_content_files();
			$model->calculate_percent( 100, 1 );
		}
		$this->log( sprintf( '%s in %d', $need_to_run, $timer->get_difference() ), Scan_Controller::SCAN_LOG );
		$model->task_checkpoint = $need_to_run;
		$model->save();

		return 'get_content_files' === $need_to_run;
	}

	/**
	 * Get core files and update the cache.
	 *
	 * @return array The array of core files.
	 */
	private function get_core_files(): array {
		$cache = get_site_option( self::CACHE_CORE, false );
		if ( is_array( $cache ) ) {
			return $cache;
		}
		$abs_path = defender_replace_line( ABSPATH );
		$core     = new File(
			$abs_path,
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
			true,
			$this->settings->filesize
		);

		$outside = new File(
			$abs_path,
			true,
			true,
			array(),
			array(
				'dir'      => array(
					$abs_path . 'wp-content' . DIRECTORY_SEPARATOR,
					$abs_path . 'wp-admin',
					$abs_path . WPINC,
				),
				'filename' => array(
					'wp-config.php',
				),
			),
			false,
			true,
			$this->settings->filesize
		);

		$files = array_merge( $core->get_dir_tree(), $outside->get_dir_tree() );
		$files = array_filter( $files );
		$this->log( sprintf( 'Core: %s', count( $files ) ), Scan_Controller::SCAN_LOG );
		update_site_option( self::CACHE_CORE, $files );

		return $files;
	}

	/**
	 * Get various content files starting from the root and update the cache.
	 *
	 * @return array|void The array of content files if cache exists, or void if no files in cache.
	 */
	private function get_content_files() {
		$cache = get_site_option( self::CACHE_CONTENT, false );
		if ( is_array( $cache ) ) {
			return $cache;
		}
		$content = new File(
			defender_replace_line( ABSPATH ),
			true,
			false,
			array(
				'ext' => array( 'php' ),
			),
			array(),
			true,
			true,
			$this->settings->filesize
		);

		$files   = $content->get_dir_tree();
		$files   = array_filter( $files );
		$files[] = defender_wp_config_path();
		$this->log( sprintf( 'Content: %s', count( $files ) ), Scan_Controller::SCAN_LOG );
		update_site_option( self::CACHE_CONTENT, $files );
	}
}