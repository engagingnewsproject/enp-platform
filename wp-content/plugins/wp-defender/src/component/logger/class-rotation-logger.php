<?php
/**
 * The log rotation class.
 *
 * @package    WP_Defender\Component\Logger
 */

namespace WP_Defender\Component\Logger;

use GlobIterator;
use WP_Defender\Traits\IO;

/**
 * Deals rotation log file naming & purging old logs.
 */
class Rotation_Logger implements Rotation_Logger_Interface {

	use IO;

	public const ROTATION_FREQUENCY = 7;
	public const ROTATION_UNIT      = 'day';

	/**
	 * Class constructor to clear file status cache.
	 */
	public function __construct() {
		clearstatcache();
	}

	/**
	 * Generate a new filename with date suffix.
	 *
	 * @param  string $filename  Filename which requires date suffix.
	 *
	 * @return string Formatted date suffixed file name.
	 */
	public function generate_file_name( $filename ) {
		$file_parts = pathinfo( $filename );

		$file_name = $file_parts['filename'] . wp_date( '-Y-m-d' );

		$directory_path = $this->get_tmp_path();
		$iterator       = new GlobIterator( $directory_path . '/' . $file_name . '*' );

		if ( file_exists( $iterator->getPathname() ) ) {
			$file_name = $iterator->getBasename();
		} else {
			$file_name = uniqid( $file_name . '-' );

			if ( ! empty( $file_parts['extension'] ) ) {
				$file_name .= '.' . $file_parts['extension'];
			}
		}

		return $file_name;
	}

	/**
	 * Purge logs whose modified time exceeded the threshold limit.
	 *
	 * @param  string $directory_path  Pathname of the directory.
	 * @param  int    $count  A period as integer value.
	 * @param  string $unit  A standard datetime relative format unit, like days or months.
	 */
	public function purge_old_log(
		$directory_path = '',
		$count = self::ROTATION_FREQUENCY,
		$unit = self::ROTATION_UNIT
	) {
		$threshold_timestamp = strtotime( '-' . $count . $unit );
		if ( empty( $directory_path ) ) {
			$directory_path = $this->get_tmp_path();
		}
		$iterator = new GlobIterator( $directory_path . '/*.log' );

		while ( $iterator->valid() ) {

			if ( $iterator->getMTime() < $threshold_timestamp ) {
				wp_delete_file( $iterator->getPathname() );
			}

			$iterator->next();
		}
	}

	/**
	 * Invoke all init methods.
	 */
	public function init() {
		/**
		 * Delete old logs rotationally.
		 */
		if ( ! wp_next_scheduled( 'wpdef_log_rotational_delete' ) ) {
			wp_schedule_event( time(), 'daily', 'wpdef_log_rotational_delete' );
		}
		add_action( 'wpdef_log_rotational_delete', array( &$this, 'purge_old_log' ) );
	}
}