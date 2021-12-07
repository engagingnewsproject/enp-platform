<?php

namespace WP_Defender\Behavior\Scan;

use Calotes\Component\Behavior;
use WP_Defender\Component\Timer;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Traits\IO;

class Core_Integrity extends Behavior {
	use IO;

	const CACHE_CHECKSUMS = 'wd_cache_checksums';

	/**
	 * Check that the folder is empty.
	 * @param string $path
	 *
	 * @return bool
	 */
	protected function is_dir_empty( $path ) {
		$rfiles = scandir( $path );
		foreach ( $rfiles as $rfile ) {
			if ( ! in_array( $rfile, array( '.', '..' ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the core file is on touch.
	 */
	public function core_integrity_check() {
		$core_files = get_site_option( Gather_Fact::CACHE_CORE, array() );
		$core_files = new \ArrayIterator( $core_files );
		$checksums  = $this->get_checksum();
		$timer      = new Timer();
		$model      = $this->owner->scan;
		$pos        = (int) $model->task_checkpoint;
		$core_files->seek( $pos );
		$this->log( sprintf( 'current pos %s', $pos ), 'scan.log' );
		while ( $core_files->valid() ) {
			if ( ! $timer->check() ) {
				$this->log( 'break out cause too long', 'scan.log' );
				break;
			}

			if ( $model->is_issue_whitelisted( $core_files->current() ) ) {
				// This is whitelisted, so do nothing.
				$this->log( sprintf( 'skip %s because of file is whitelisted', $core_files->current() ), 'scan.log' );
				$core_files->next();
				continue;
			}

			if ( $model->is_issue_ignored( $core_files->current() ) ) {
				// This is ignored, so do nothing.
				$this->log( sprintf( 'skip %s because of file is ignored', $core_files->current() ), 'scan.log' );
				$core_files->next();
				continue;
			}

			// The file will be '\' instead of '/' on Windows OS, so we need to convert everything to '/'.
			$file = $core_files->current();
			// Get relative so we can compare.
			$abs_path = ABSPATH;
			if ( defender_is_windows() ) {
				// This mean we are on Windows.
				$abs_path = str_replace( '/', DIRECTORY_SEPARATOR, $abs_path );
			}
			$rev_file = str_replace( $abs_path, '', $file );
			// Remove the first '\' on Windows.
			$rev_file = str_replace( DIRECTORY_SEPARATOR, '/', $rev_file );
			if ( isset( $checksums[ $rev_file ] ) ) {
				if ( ! $this->compare_hashes( $file, $checksums[ $rev_file ] ) ) {
					$this->log( sprintf( 'modified %s', $file ), 'scan.log' );
					$model->add_item(
						Scan_Item::TYPE_INTEGRITY,
						array(
							'file' => $file,
							'type' => 'modified',
						)
					);
				}
			} else {
				if ( is_dir( $file ) ) {
					if ( $this->is_dir_empty( $core_files->current() ) ) {
						$this->log(
							sprintf( 'skip %s because of non-WP directory is empty', $core_files->current() ),
							'scan.log'
						);
						$core_files->next();
						continue;
					}
					$item_type = 'dir';
				} else {
					$item_type = 'unversion';
				}

				$model->add_item(
					Scan_Item::TYPE_INTEGRITY,
					array(
						'file' => $file,
						'type' => $item_type,
					)
				);
			}
			$model->calculate_percent( $core_files->key() * 100 / $core_files->count(), 2 );
			if ( 0 === $core_files->key() % 100 ) {
				// We should update the model percent each 100 files, so we have some progress ont he screen$pos * 100 / $core_files->count().
				$model->save();
			}
			$core_files->next();
		}
		if ( $core_files->valid() ) {
			// Save the current progress and quit.
			$model->task_checkpoint = $core_files->key();
		} else {
			// We will check if we have any ignore issue from last scan, so we can bring it here.
			$last = Scan::get_last();
			if ( is_object( $last ) ) {
				$ignored_issues = $last->get_issues( Scan_Item::TYPE_INTEGRITY, Scan_Item::STATUS_IGNORE );
				foreach ( $ignored_issues as $issue ) {
					$model->add_item( Scan_Item::TYPE_INTEGRITY, $issue->raw_data, Scan_Item::STATUS_IGNORE );
				}
			}
			//Done, reset this, so we can use it later.
			$model->task_checkpoint = null;
		}
		$model->save();

		return ! $core_files->valid();
	}

	/**
	 * Fetch the checksums.
	 *
	 * @return bool|array
	 */
	protected function get_checksum() {
		$cache = get_site_option( self::CACHE_CHECKSUMS, false );
		if ( is_array( $cache ) ) {

			return $cache;
		}
		$this->log( 'Fetch checksums, should only once', 'scan.log' );

		global $wp_version, $wp_local_package;

		if ( ! function_exists( 'get_core_checksums' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$checksums = get_core_checksums( $wp_version, empty( $wp_local_package ) ? 'en_US' : $wp_local_package );
		if ( false === $checksums ) {
			$this->log( 'Error from fetching checksums from wp.org', 'scan.log' );
			$scan         = $this->owner->scan;
			$scan->status = Scan::STATUS_ERROR;

			return false;
		}
		if ( isset( $checksums[ $wp_version ] ) ) {
			/**
			 * Sometimes the API returns the format [$wp_version]=>'...' when locale not right.
			 * Use this as fail-safe.
			 */
			$checksums = $wp_version[ $checksums ];
		}
		update_site_option( self::CACHE_CHECKSUMS, $checksums );

		return $checksums;
	}
}