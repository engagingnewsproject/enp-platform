<?php
/**
 * The interface for log rotation class.
 *
 *  @package WP_Defender\Component\Logger
 */

namespace WP_Defender\Component\Logger;

interface Rotation_Logger_Interface {

	/**
	 * Generate a new filename with date suffix.
	 *
	 * @param string $filename Filename which requires date suffix.
	 *
	 * @return string Formatted date suffixed file name.
	 */
	public function generate_file_name( $filename );

	/**
	 * Purge logs whose modified time exceeded the threshold limit.
	 *
	 * @param string $directory_path Pathname of the directory.
	 * @param int    $count          A period as integer value.
	 * @param string $unit           A standard datetime relative format unit, like days or months.
	 */
	public function purge_old_log( $directory_path, $count, $unit );
}
