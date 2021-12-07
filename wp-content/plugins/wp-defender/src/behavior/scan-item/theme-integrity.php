<?php

namespace WP_Defender\Behavior\Scan_Item;

use Calotes\Base\File;
use Calotes\Component\Behavior;
use WP_Defender\Component\Error_Code;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;
use WP_Error;

/**
 * @since 2.5.0 Leave for migration from old versions to 2.5.0
*/
class Theme_Integrity extends Behavior {
	use Formats, IO;

	const URL_THEME_VCS = 'https://themes.svn.wordpress.org/';

	/**
	 * Return general data so we can output on frontend.
	 *
	 * @return array
	 */
	public function to_array() {
		$data            = $this->owner->raw_data;
		$file            = $data['file'];
		$file_created_at = @filemtime( $file );
		if ( $file_created_at ) {
			$file_created_at = $this->format_date_time( $file_created_at );
		} else {
			$file_created_at = 'n/a';
		}
		$file_size = @filesize( $file );
		if ( ! $file_size ) {
			$file_size = 'n/a';
		} else {
			$file_size = $this->format_bytes_into_readable( $file_size );
		}

		return array(
			'id'         => $this->owner->id,
			'type'       => Scan_Item::TYPE_THEME_CHECK,
			'file_name'  => pathinfo( $file, PATHINFO_BASENAME ),
			'full_path'  => $file,
			'date_added' => $file_created_at,
			'size'       => $file_size,
			'scenario'   => $data['type'],
			'short_desc' => $this->get_short_description(),
		);
	}

	/**
	 * We will get the origin code by looking into svn repo.
	 *
	 * @return false|string|\WP_Error
	 */
	private function get_origin_code() {
		$data          = $this->owner->raw_data;
		$file          = wp_normalize_path( $data['file'] );
		$ds            = DIRECTORY_SEPARATOR;
		$theme_dir     = WP_CONTENT_DIR . $ds . 'themes';
		$relative_path = str_replace( wp_normalize_path( $theme_dir ) . $ds, '', $file );
		$path_data     = explode( $ds, $relative_path, 2 );
		if ( ! empty( $path_data ) ) {
			$theme_slug = $path_data[0];
			$file_path  = $path_data[1];
		} else {
			return new WP_Error( 'defender_broken_file_path', __( 'Broken file path.', 'wpdef' ) );
		}
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once( ABSPATH . '/wp-includes/theme.php' );
		}
		$theme = wp_get_theme( $theme_slug );

		// Get original from wp.org e.g. https://themes.svn.wordpress.org/twentytwenty/1.6/functions.php.
		$source_file_url = self::URL_THEME_VCS . $theme_slug . $ds . $theme->get( 'Version' ) . $ds . $file_path;
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin' . $ds . 'includes' . $ds . 'file.php';
		}
		$tmp = download_url( $source_file_url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$content = file_get_contents( $tmp );
		@unlink( $tmp );

		return $content;
	}

	/**
	 * Restore the file with its origin content.
	 *
	 * @return void|array
	 */
	public function resolve() {
		$data = $this->owner->raw_data;
		if ( 'modified' !== $data['type'] ) {
			// Should not be here unless case changed.
			return;
		}

		$origin = $this->get_origin_code();
		if ( false === $origin || is_wp_error( $origin ) ) {
			return;
		}

		$path = $data['file'];
		$ret  = @file_put_contents( $path, $origin );// phpcs:ignore
		if ( $ret ) {
			$scan = Scan::get_last();
			$scan->remove_issue( $this->owner->id );
			$this->log( sprintf( '%s is deleted', $path ), 'scan.log' );

			return array(
				'message' => __( 'This item has been resolved.', 'wpdef' ),
			);
		} else {
			return new WP_Error(
				'defender_permissions_denied',
				__( 'Permissions Denied. Defender does not have the needed permissions to edit the file. Please change file permissions to 640 or contact your hosting provider so they could change them for you.', 'wpdef' )
			);
		}
	}

	/**
	 * @return array
	 */
	public function ignore() {
		$scan = Scan::get_last();
		$scan->ignore_issue( $this->owner->id );

		return array(
			'message' => __( 'The suspicious file has been successfully ignored.', 'wpdef' ),
		);
	}

	/**
	 * @return array
	 */
	public function unignore() {
		$scan = Scan::get_last();
		$scan->unignore_issue( $this->owner->id );

		return array(
			'message' => __( 'The suspicious file has been successfully restored.', 'wpdef' ),
		);
	}

	/**
	 * Delete the file or whole folder.
	 *
	 * @return array|\WP_Error
	 */
	public function delete() {
		$data = $this->owner->raw_data;
		$scan = Scan::get_last();
		if ( 'unversion' === $data['type'] && @unlink( $data['file'] ) ) {
			$scan->remove_issue( $this->owner->id );
			$this->log( sprintf( '%s is deleted', $data['file'] ), 'scan.log' );

			return array(
				'message' => __( 'This item has been permanently removed', 'wpdef' ),
			);
		} elseif ( 'dir' === $data['type'] && $this->delete_dir( $data['file'] ) ) {
			$scan->remove_issue( $this->owner->id );
			$this->log( sprintf( '%s is deleted', $data['file'] ), 'scan.log' );

			return array(
				'message' => __( 'This item has been permanently removed', 'wpdef' ),
			);
		}

		return new \WP_Error( Error_Code::NOT_WRITEABLE, __( 'Defender doesn\'t have enough permission to remove this file', 'wpdef' ) );
	}

	/**
	 *  Return the source code depending on the type of the issue.
	 *
	 * @return array
	 */
	public function pull_src() {
		$data = $this->owner->raw_data;
		if ( ! file_exists( $data['file'] ) && ! is_dir( $data['file'] ) ) {
			return array(
				'code'   => '',
				'origin' => '',
			);
		}
		switch ( $data['type'] ) {
			case 'unversion':
				return array(
					'code' => file_get_contents( $data['file'] ),
				);
			case 'dir':
				$dir_tree = new File( $data['file'], true, true, array(), array(), false );

				return array(
					'code' => implode( PHP_EOL, $dir_tree->get_dir_tree() ),
				);
			case 'modified':
			default:
				return array(
					'code'   => file_get_contents( $data['file'] ),
					'origin' => $this->get_origin_code(),
				);
		}
	}

	/**
	 * @return string
	 */
	private function get_short_description() {
		$data = $this->owner->raw_data;
		if ( 'unversion' === $data['type'] ) {
			return esc_html__( 'Unknown file in the WordPress theme', 'wpdef' );
		} elseif ( 'dir' === $data['type'] ) {
			return esc_html__( 'This directory does not belong to the WordPress theme', 'wpdef' );
		} elseif ( 'modified' === $data['type'] ) {
			return esc_html__( 'This theme file appears modified', 'wpdef' );
		}
	}
}