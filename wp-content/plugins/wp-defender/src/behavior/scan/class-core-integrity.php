<?php
/**
 * Handles core integrity scan.
 *
 * @package WP_Defender\Behavior\Scan
 */

namespace WP_Defender\Behavior\Scan;

use ArrayIterator;
use WP_Defender\Traits\IO;
use WP_Defender\Model\Scan;
use Calotes\Component\Behavior;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Component\Timer;
use WP_Defender\Helper\Analytics\Scan as Scan_Analytics;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * Handles core integrity scan.
 */
class Core_Integrity extends Behavior {

	use IO;

	public const CACHE_CHECKSUMS = 'wd_cache_checksums', ISSUE_CHECKSUMS = 'wd_issue_checksums';

	/**
	 * Check that the folder is empty.
	 *
	 * @param  string $path  The path to check.
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
	 *
	 * @return bool
	 */
	public function core_integrity_check(): bool {
		$checksums = $this->get_checksum();
		if ( false === $checksums ) {
			return false;
		}
		$core_files = get_site_option( Gather_Fact::CACHE_CORE, array() );
		$core_files = new ArrayIterator( $core_files );
		$timer      = new Timer();
		$model      = $this->owner->scan;
		$pos        = (int) $model->task_checkpoint;
		if ( $pos > 0 ) {
			$core_files->seek( $pos );
		}
		$this->log( sprintf( 'current pos %s', $pos ), Scan_Controller::SCAN_LOG );
		while ( $core_files->valid() ) {
			if ( ! $timer->check() ) {

				$reason = 'break out cause too long';

				/**
				 * Retrieves an instance of the Scan_Analytics class.
				 *
				 * @var Scan_Analytics $scan_analytics
				 */
				$scan_analytics = wd_di()->get( Scan_Analytics::class );

				$scan_analytics->track_feature(
					$scan_analytics::EVENT_SCAN_FAILED,
					array(
						$scan_analytics::EVENT_SCAN_FAILED_PROP => $scan_analytics::EVENT_SCAN_FAILED_ERROR,
						'Error_Reason' => $reason,
					)
				);

				$this->log( $reason, Scan_Controller::SCAN_LOG );
				break;
			}

			if ( $model->is_issue_whitelisted( $core_files->current() ) ) {
				// This is whitelisted, so do nothing.
				$this->log( sprintf( 'skip %s because of file is whitelisted', $core_files->current() ), Scan_Controller::SCAN_LOG );
				$core_files->next();
				continue;
			}

			if ( $model->is_issue_ignored( $core_files->current() ) ) {
				// This is ignored, so do nothing.
				$this->log( sprintf( 'skip %s because of file is ignored', $core_files->current() ), Scan_Controller::SCAN_LOG );
				$core_files->next();
				continue;
			}

			$file = $core_files->current();
			// Get relative so we can compare.
			$abs_path = defender_replace_line( ABSPATH );
			$rev_file = str_replace( $abs_path, '', $file );
			// Remove directory separator on the left.
			$rev_file = ltrim( $rev_file, DIRECTORY_SEPARATOR );

			if ( isset( $checksums[ $rev_file ] ) ) {
				if ( ! $this->compare_hashes( $file, $checksums[ $rev_file ] ) ) {
					$this->log( sprintf( 'modified %s', $file ), Scan_Controller::SCAN_LOG );
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
							Scan_Controller::SCAN_LOG
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
			// Done, reset this, so we can use it later.
			$model->task_checkpoint = '';
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
		$this->log( 'Fetch checksums, should only once', Scan_Controller::SCAN_LOG );

		global $wp_version, $wp_local_package;

		if ( ! function_exists( 'get_core_checksums' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$checksums = get_core_checksums( $wp_version, empty( $wp_local_package ) ? 'en_US' : $wp_local_package );
		if ( false === $checksums ) {
			$this->log( 'Error from fetching checksums from wp.org', Scan_Controller::SCAN_LOG );
			$scan         = $this->owner->scan;
			$scan->status = Scan::STATUS_IDLE;
			$scan->save();

			update_site_option( self::ISSUE_CHECKSUMS, time() );
			// We can't send to MP event now because the Scan process continues to ping wp.org. This can cause a event's surge.
			return false;
		}

		if ( isset( $checksums[ $wp_version ] ) ) {
			/**
			 * Sometimes the API returns the format [$wp_version]=>'...' when locale not right.
			 * Use this as fail-safe.
			 */
			$checksums = $checksums[ $wp_version ];
		}
		if ( is_array( $checksums ) ) {
			foreach ( $checksums as $key => $checksum ) {
				$formatted_key = defender_replace_line( $key );

				if ( $key !== $formatted_key ) {
					$checksums[ $formatted_key ] = $checksum;
					unset( $checksums[ $key ] );
				}
			}
		}

		update_site_option( self::CACHE_CHECKSUMS, $checksums );

		return $checksums;
	}
}