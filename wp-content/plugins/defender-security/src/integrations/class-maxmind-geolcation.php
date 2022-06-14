<?php

namespace WP_Defender\Integrations;

use WP_Defender\Traits\IO;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class MaxMind_Geolocation
 *
 * @since 2.7.1
 * @package WP_Defender\Integrations
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
	 * @return string
	 */
	public function get_db_full_name() {
		return self::DB_NAME . self::DB_EXT;
	}

	/**
	 * Todo: extend the logic to handle different results.
	 * @param string $license_key
	 *
	 * @return bool|string|\WP_Error
	 */
	public function get_downloaded_url( $license_key ) {
		$url = add_query_arg(
			array(
				'edition_id'  => self::DB_NAME,
				'license_key' => urlencode( sanitize_text_field( $license_key ) ),
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
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_db_base_path() {
		return $this->get_tmp_path() . DIRECTORY_SEPARATOR . self::DB_DIRECTORY;
	}

	/**
	 * Extract downloaded database.
	 *
	 * @since 2.8.0
	 *
	 * @return string|\WP_Error Path to the database file or an error.
	 */
	public function extract_db_archive( $temp_path ) {
		try {
			$phar = new \PharData( $temp_path );

			$base_path = $this->get_db_base_path();
			if ( ! is_dir( $base_path ) ) {
				wp_mkdir_p( $base_path );
			}

			$phar->extractTo( $base_path, null, true );
			$geodb_path = $base_path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $this->get_db_full_name();
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'wpdef_maxmind_geolocation_database_archive', $exception->getMessage() );
		} finally {
			// Archive file is not needed.
			unlink( $temp_path );
		}

		return $geodb_path;
	}

	/**
	 * Delete the database.
	 *
	 * @since 2.8.0
	 */
	public function delete_database() {
		// Easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$database_path = $this->get_db_base_path();
		if ( $wp_filesystem->exists( $database_path ) ) {
			$wp_filesystem->delete( $database_path, true );
		}
	}
}
