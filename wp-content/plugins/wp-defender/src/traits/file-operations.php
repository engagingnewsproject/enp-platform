<?php
/**
 * Helper functions for file related tasks.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_Error;
use WP_Defender\Model\Scan;
use WP_Defender\Component\Error_Code;

trait File_Operations {

	/**
	 * Deletes a file if it exists and is writable.
	 *
	 * @param  string $file  The path to the file to be deleted.
	 *
	 * @return bool Returns true if the file was successfully deleted, false otherwise.
	 */
	public function delete_infected_file( string $file ): bool {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return file_exists( $file ) && $wp_filesystem->is_writable( $file ) && $wp_filesystem->delete( $file );
	}

	/**
	 * Handle actions after deleting a file or folder.
	 *
	 * @param  string    $deleted_file  The file or folder that was deleted.
	 * @param  Scan|null $related_scan  The scan instance related to the deletion.
	 * @param  string    $scan_type  The type of scan.
	 *
	 * @return array An array with a message confirming the deletion.
	 */
	public function after_delete( string $deleted_file, ?Scan $related_scan, string $scan_type ): array {
		$this->log( sprintf( '%s is deleted', $deleted_file ), \WP_Defender\Controller\Scan::SCAN_LOG );
		$related_scan->remove_issue( $this->owner->id );
		$related_scan->remove_related_issue_by( $deleted_file, $scan_type );
		do_action( 'wpdef_fixed_scan_issue', $scan_type, 'delete' );

		return array( 'message' => esc_html__( 'This item has been permanently removed', 'wpdef' ) );
	}

	/**
	 * Returns a WP_Error object with an error code indicating that the file is not writeable.
	 *
	 * @param  string $file  The path to the file that is not writeable.
	 *
	 * @return WP_Error The WP_Error object with the error code and the basename of the file.
	 */
	public function get_permission_error( string $file ): WP_Error {
		return new WP_Error( Error_Code::NOT_WRITEABLE, wp_basename( $file ) );
	}
}