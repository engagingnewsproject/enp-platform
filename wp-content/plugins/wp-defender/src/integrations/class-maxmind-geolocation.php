<?php
/**
 * Handles interactions with Maxmind geolocation.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

use WP_Error;
use PharData;
use Exception;
use WP_Defender\Traits\IO;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Maxmind geolocation integration module.
 *
 * @since 2.7.1
 */
class MaxMind_Geolocation {

	use IO;

	/**
	 * The name of the MaxMind database.
	 */
	public const DB_NAME = 'GeoLite2-Country';

	/**
	 * The extension of the MaxMind database.
	 */
	public const DB_EXT = '.mmdb';

	/**
	 * The name of the Maxmind directory
	 */
	public const DB_DIRECTORY = 'maxmind';

	/**
	 * Returns the full name of the database by concatenating the database name and extension.
	 *
	 * @return string The full name of the database.
	 */
	public function get_db_full_name() {
		return self::DB_NAME . self::DB_EXT;
	}

	/**
	 * Retrieves the URL for downloading the MaxMind database using the provided license key.
	 * Todo: extend the logic to handle different results.
	 *
	 * @param  string $license_key  The license key for the MaxMind database.
	 *
	 * @return string|WP_Error The URL for downloading the database, or a WP_Error object on failure.
	 */
	public function get_downloaded_url( $license_key ) {
		$url = add_query_arg(
			array(
				'edition_id'  => self::DB_NAME,
				'license_key' => rawurlencode( sanitize_text_field( $license_key ) ),
				'suffix'      => 'tar.gz',
			),
			'https://download.maxmind.com/app/geoip_download'
		);
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return download_url( $url );
	}

	/**
	 * Get database base path.
	 *
	 * @return string
	 * @since 2.8.0
	 */
	public function get_db_base_path() {
		return $this->get_tmp_path( true ) . DIRECTORY_SEPARATOR . self::DB_DIRECTORY;
	}

	/**
	 * Extract downloaded database.
	 *
	 * @param  string $temp_path  The path to the temporary file.
	 *
	 * @return string|WP_Error Path to the database file or an error.
	 * @since 2.8.0
	 */
	public function extract_db_archive( $temp_path ) {
		try {
			$phar = new PharData( $temp_path );

			$base_path = $this->get_db_base_path();
			if ( ! is_dir( $base_path ) ) {
				wp_mkdir_p( $base_path );
			}

			$phar->extractTo( $base_path, null, true );
			$geodb_path = $base_path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $this->get_db_full_name();
		} catch ( Exception $exception ) {
			return new WP_Error( 'wpdef_maxmind_geolocation_database_archive', $exception->getMessage() );
		} finally {
			// Archive file is not needed.
			wp_delete_file( $temp_path );
		}

		return $geodb_path;
	}

	/**
	 * Delete the database.
	 *
	 * @since 2.8.0
	 */
	public function delete_database() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$database_path = $this->get_db_base_path();
		if ( $wp_filesystem->exists( $database_path ) ) {
			$wp_filesystem->delete( $database_path, true );
		}
	}
}