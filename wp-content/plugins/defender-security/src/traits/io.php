<?php

namespace WP_Defender\Traits;

trait IO {
	/**
	 * A simple function to create & return the folder that we can use to write tmp files.
	 *
	 * @return string
	 */
	protected function get_tmp_path() {
		$upload_dir = wp_upload_dir()['basedir'];
		$tmp_dir    = $upload_dir . DIRECTORY_SEPARATOR . 'wp-defender';
		if ( ! is_dir( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
		}

		if ( ! is_file( $tmp_dir . DIRECTORY_SEPARATOR . 'index.php' ) ) {
			file_put_contents( $tmp_dir . DIRECTORY_SEPARATOR . 'index.php', '' );
		}

		return $tmp_dir;
	}

	/**
	 * @param $category
	 *
	 * @return string
	 */
	public function get_log_path( $category = '' ) {
		$file = empty( $category ) ? 'defender.log' : $category;

		return $this->get_tmp_path() . DIRECTORY_SEPARATOR . $file;
	}

	/**
	 * Create a lock, this will be use in scanning.
	 *
	 * @return string
	 */
	protected function get_lock_path() {
		return $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'scan.lock';
	}

	/**
	 * Delete a folder with every content inside.
	 *
	 * @param $dir
	 */
	public function delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it    = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new \RecursiveIteratorIterator(
			$it,
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				$ret = rmdir( $file->getPathname() );
			} else {
				$ret = unlink( $file->getPathname() );
			}
			if ( false === $ret ) {
				return false;
			}
		}
		rmdir( $dir );

		return true;
	}

	/**
	 * @return string|\WP_Error
	 */
	public function get_tmp_folder() {
		$tmp_dir = $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		if ( ! is_dir( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
			if ( ! wp_mkdir_p( $tmp_dir ) ) {
				return new \WP_Error( 'defender_dir', sprintf( __( 'Unable to create the tmp directory %s', 'wpdef' ), $tmp_dir ) );
			}
		}
		return $tmp_dir;
	}

	/**
	 * Not remove double quotes inside str_replace().
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function convert_end_lines_dos_to_linux( $file_path ) {
		return str_replace( array( "\r\n", "\r" ), "\n", file_get_contents( $file_path ) );
	}

	/**
	 * Not remove double quotes inside str_replace().
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function convert_end_lines_linux_to_dos( $file_path ) {
		return str_replace( "\n", "\r\n", $this->convert_end_lines_dos_to_linux( $file_path ) );
	}

	/**
	 * Compare hashes on different OS.
	 * @param string       $file_path
	 * @param string|array $file_hash
	 *
	 * @return bool
	 */
	protected function compare_hashes_on_different_os( $file_path, $file_hash ) {
		if ( hash_equals( md5_file( $file_path ), $file_hash ) ) {
			return true;
		}
		if ( hash_equals( md5( $this->convert_end_lines_dos_to_linux( $file_path ) ), $file_hash ) ) {
			return true;
		}
		if ( hash_equals( md5( $this->convert_end_lines_linux_to_dos( $file_path ) ), $file_hash ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $file_path       Path to file.
	 * @param string|array $file_hash Hash or some hashes of file2, e.g. for readme.txt.
	 *
	 * @return bool
	 */
	public function compare_hashes( $file_path, $file_hash ) {
		if ( is_string( $file_hash ) ) {

			return $this->compare_hashes_on_different_os( $file_path, $file_hash );
		} elseif ( is_array( $file_hash ) ) {
			// Sometimes file has some hashes.
			foreach ( $file_hash as $hash_value ) {
				if ( $this->compare_hashes_on_different_os( $file_path, $hash_value ) ) {
					return true;
				}
			}

			return false;
		} else {

			return false;
		}
	}
}
