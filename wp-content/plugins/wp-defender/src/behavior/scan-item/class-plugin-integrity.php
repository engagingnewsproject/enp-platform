<?php
/**
 * Handles plugin integrity scan item.
 *
 * @package WP_Defender\Behavior\Scan_Item
 */

namespace WP_Defender\Behavior\Scan_Item;

use WP_Error;
use Calotes\Base\File;
use WP_Defender\Traits\IO;
use WP_Defender\Model\Scan;
use WP_Defender\Traits\Plugin;
use WP_Defender\Traits\Formats;
use Calotes\Component\Behavior;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Traits\File_Operations;
use WP_Defender\Component\Quarantine as Quarantine_Component;
use WP_Filesystem_Base;

/**
 * Class Plugin_Integrity
 * This class represents a plugin integrity behavior.
 */
class Plugin_Integrity extends Behavior {

	use Formats;
	use IO;
	use Plugin;
	use File_Operations;

	/**
	 * Return general data so we can output on frontend.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$data = $this->owner->raw_data;
		$file = $data['file'];
		list ( $file_created_at, $file_size, $deleted ) = $this->get_file_meta( $file );

		$is_quarantinable = $this->is_quarantinable( $this->owner->raw_data['file'] );

		$quarantine_data = class_exists( 'WP_Defender\Component\Quarantine' ) ?
			wd_di()->get( Quarantine_Component::class )->scan_item_data( $this->owner ) :
			array( 'is_quarantinable' => $is_quarantinable );

		return array_merge(
			array(
				'id'         => $this->owner->id,
				'type'       => Scan_Item::TYPE_PLUGIN_CHECK,
				'file_name'  => pathinfo( $file, PATHINFO_BASENAME ),
				'full_path'  => $file,
				'date_added' => $file_created_at,
				'size'       => $file_size,
				'deleted'    => $deleted,
				'scenario'   => $data['type'],
				'short_desc' => $this->get_short_description(),
			),
			$quarantine_data
		);
	}

	/**
	 * We will get the origin code by looking into svn repo.
	 *
	 * @return false|string|WP_Error
	 */
	private function get_origin_code() {
		$file = $this->owner->raw_data['file'];

		$directory_name = $this->get_plugin_directory_name( $file );
		$plugin_header  = $this->get_plugin_headers( $file );
		$plugin_header  = reset( $plugin_header );
		$file_path      = $this->get_plugin_relative_path( $file );

		$source_file_url = $this->get_file_url(
			$directory_name,
			$plugin_header['Version'],
			$file_path
		);

		return $this->get_url_content( $source_file_url );
	}

	/**
	 * Restore the file with its origin content.
	 *
	 * @return void|array|WP_Error
	 */
	public function resolve() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
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
		$ret  = $wp_filesystem->put_contents( $path, $origin );
		if ( $ret ) {
			$scan = Scan::get_last();
			$scan->remove_issue( $this->owner->id );
			$this->log( sprintf( '%s is deleted', $path ), \WP_Defender\Controller\Scan::SCAN_LOG );

			do_action( 'wpdef_fixed_scan_issue', 'plugin_integrity', 'resolve' );

			return array( 'message' => esc_html__( 'This item has been resolved.', 'wpdef' ) );
		} else {
			return new WP_Error(
				'defender_permissions_denied',
				esc_html__(
					'Permissions Denied. Defender does not have the needed permissions to edit the file. Please change file permissions to 640 or contact your hosting provider so they could change them for you.',
					'wpdef'
				)
			);
		}
	}

	/**
	 * Ignore the suspicious file for the current scan.
	 *
	 * @return array An array with a message indicating successful ignore.
	 */
	public function ignore(): array {
		$scan = Scan::get_last();
		$scan->ignore_issue( $this->owner->id );

		return array( 'message' => esc_html__( 'The suspicious file has been successfully ignored.', 'wpdef' ) );
	}

	/**
	 * Allow the suspicious file for the current scan.
	 *
	 * @return array An array with a message indicating successful restoration.
	 */
	public function unignore(): array {
		$scan = Scan::get_last();
		$scan->unignore_issue( $this->owner->id );

		return array( 'message' => esc_html__( 'The suspicious file has been successfully restored.', 'wpdef' ) );
	}

	/**
	 * Delete the file or whole folder.
	 *
	 * @return array|WP_Error
	 */
	public function delete() {
		$data = $this->owner->raw_data;
		$scan = Scan::get_last();
		$file = $data['file'];
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			$scan->remove_issue( $this->owner->id );
			$this->log(
				sprintf( '%s is not readable and will be removed from the scan result', $file ),
				\WP_Defender\Controller\Scan::SCAN_LOG
			);

			return array( 'message' => esc_html__( 'This item has been deleted.', 'wpdef' ) );
		} elseif ( 'unversion' === $data['type'] && $this->delete_infected_file( $file ) ) {
			return $this->after_delete( $file, $scan, 'plugin_integrity' );
		} elseif ( 'dir' === $data['type'] && $this->delete_dir( $file ) ) {
			return $this->after_delete( $file, $scan, 'plugin_integrity' );
		}

		return $this->get_permission_error( $file );
	}

	/**
	 *  Return the source code depending on the type of the issue.
	 *
	 * @return array
	 */
	public function pull_src(): array {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$data = $this->owner->raw_data;
		if ( ! file_exists( $data['file'] ) && ! is_dir( $data['file'] ) ) {
			return array(
				'code'   => '',
				'origin' => '',
			);
		}
		switch ( $data['type'] ) {
			case 'unversion':
				return array( 'code' => $wp_filesystem->get_contents( $data['file'] ) );
			case 'dir':
				$dir_tree = new File( $data['file'], true, true, array(), array(), false );

				return array( 'code' => implode( PHP_EOL, $dir_tree->get_dir_tree() ) );
			case 'modified':
			default:
				return array(
					'code'   => $wp_filesystem->get_contents( $data['file'] ),
					'origin' => $this->get_origin_code(),
				);
		}
	}

	/**
	 * Return a short description of the WordPress plugin file based on its type.
	 *
	 * @return string The short description of the file.
	 */
	private function get_short_description(): string {
		$data = $this->owner->raw_data;
		if ( 'unversion' === $data['type'] ) {
			return esc_html__( 'Unknown file in the WordPress plugin', 'wpdef' );
		} elseif ( 'dir' === $data['type'] ) {
			return esc_html__( 'This directory does not belong to the WordPress plugin', 'wpdef' );
		} elseif ( 'modified' === $data['type'] ) {
			return esc_html__( 'This plugin file appears modified', 'wpdef' );
		}

		return '';
	}
}