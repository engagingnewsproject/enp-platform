<?php
/**
 * File related helper utilities.
 *
 * @package WP_Defender\Helper
 */

namespace WP_Defender\Helper;

use WP_Error;

/**
 * Handles file related tasks.
 */
class File {

	/**
	 * Check is two files identical.
	 *
	 * @param  string $local_file  File path of the local file for content comparison.
	 * @param  string $remote_file  Url of the remote file for content comparison.
	 *
	 * @return bool|string|WP_Error If remote fetch fails return WP_Error object or
	 * true for identical file content or false for non-identical file content.
	 */
	public function is_identical_content( string $local_file, string $remote_file ) {
		wp_raise_memory_limit();

		$local_file_content = file( $local_file, FILE_IGNORE_NEW_LINES );

		$tmp = download_url( $remote_file );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$remote_file_content = file( $tmp, FILE_IGNORE_NEW_LINES );

		wp_delete_file( $tmp );

		return $local_file_content === $remote_file_content;
	}

	/**
	 * Deny access for the provided directory.
	 *
	 * @param  string $directory  File path to the directory.
	 *
	 * @since 4.2.0
	 */
	public function maybe_dir_access_deny( string $directory ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$files = array(
			array(
				'base'    => $directory,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => $directory,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			$file_path = trailingslashit( $file['base'] ) . $file['file'];
			if ( ! is_null( $file_path ) && ! file_exists( $file_path ) ) {
				$wp_filesystem->put_contents( $file_path, $file['content'] );
			}
		}
	}
}