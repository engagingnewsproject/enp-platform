<?php

namespace WP_Defender\Component;

use WP_Defender\Behavior\Scan\Core_Integrity;
use WP_Defender\Behavior\Scan\Gather_Fact;
use WP_Defender\Behavior\Scan\Known_Vulnerability;
use WP_Defender\Behavior\Scan\Malware_Scan;
use WP_Defender\Behavior\Scan\Plugin_Integrity;
use WP_Defender\Behavior\Scan\Theme_Integrity;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
use WP_Defender\Model\Scan_Item;

class Scan extends Component {

	/**
	 * Cache the current scan
	 *
	 * @var \WP_Defender\Model\Scan
	 */
	public $scan;

	/**
	 * @var \WP_Defender\Model\Setting\Scan
	 */
	public $settings;

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->attach_behavior( Gather_Fact::class, Gather_Fact::class );
		$this->attach_behavior( Core_Integrity::class, Core_Integrity::class );
		$this->attach_behavior( Theme_Integrity::class, Theme_Integrity::class );
		$this->attach_behavior( Plugin_Integrity::class, Plugin_Integrity::class );
		if ( class_exists( Known_Vulnerability::class ) ) {
			$this->attach_behavior( Known_Vulnerability::class, new Known_Vulnerability() );
		}
		if ( class_exists( Malware_Scan::class ) ) {
			$this->attach_behavior( Malware_Scan::class, new Malware_Scan() );
		}
	}

	/**
	 * Process current scan
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function process() {
		$scan = \WP_Defender\Model\Scan::get_active();
		if ( ! is_object( $scan ) ) {
			//this case can be a scan get cancel

			return - 1;
		}
		$this->scan     = $scan;
		$this->settings = new \WP_Defender\Model\Setting\Scan();
		$tasks          = $this->get_tasks();
		$runner         = new \ArrayIterator( $tasks );
		$task           = $this->scan->status;
		if ( \WP_Defender\Model\Scan::STATUS_INIT === $scan->status ) {
			//get the first
			$this->log( 'Prepare facts for a scan', 'scan' );
			$task                    = 'gather_fact';
			$this->scan->percent     = 0;
			$this->scan->total_tasks = $runner->count();
			$this->scan->save();
		}
		if ( $this->scan->status === \WP_Defender\Model\Scan::STATUS_ERROR ) {
			//stop and return true so it break the process
			return true;
		}
		//find the current task
		$offset = array_search( $task, array_values( $tasks ) );
		if ( false === $offset ) {
			//TODO weird thing
			$this->log( sprintf( 'offset is not found, search %s', $task ), 'scan' );

			return false;
		}
		//reset the tasks to current
		$runner->seek( $offset );
		$this->log( sprintf( 'Current task %s', $runner->current() ), 'scan' );
		if ( $this->has_method( $task ) ) {
			$this->log( sprintf( 'processing %s', $runner->key() ), 'scan' );
			$result = $this->$task();
			if ( true === $result ) {
				$this->log( sprintf( 'task %s processed', $runner->key() ), 'scan' );
				//task is done, move to next
				$runner->next();
				if ( $runner->valid() ) {
					$this->log( sprintf( 'queue %s for next', $runner->key() ), 'scan' );
					$this->scan->status          = $runner->key();
					$this->scan->task_checkpoint = '';
					$this->scan->save();

					//queue for next run
					return false;
				}
				$this->log( 'All done!', 'scan' );
				//no more task in the queue, we done
				$this->scan->status = \WP_Defender\Model\Scan::STATUS_FINISH;
				$this->scan->save();
				$this->reindex_ignored_issues( $this->scan );
				$this->clean_up();
				do_action( 'defender_notify', 'malware-notification', $this->scan );

				return true;
			}
			$this->scan->status = $task;
			$this->scan->save();
		}

		return false;
	}

	/**
	 * @param \WP_Defender\Model\Scan $model
	 */
	private function reindex_ignored_issues( $model ) {
		$issues       = $model->get_issues( null, Scan_Item::STATUS_IGNORE );
		$ignore_lists = [];
		foreach ( $issues as $issue ) {
			$data = $issue->raw_data;
			if ( isset( $data['file'] ) ) {
				$ignore_lists[] = $data['file'];
			} elseif ( isset( $data['slug'] ) ) {
				$ignore_lists[] = $data['slug'];
			}
		}
		$ignore_lists = array_unique( $ignore_lists );
		$ignore_lists = array_filter( $ignore_lists );
		update_site_option( \WP_Defender\Model\Scan::IGNORE_INDEXER, $ignore_lists );
	}

	/**
	 * Get a list of tasks will run in a scan
	 *
	 * @return array
	 */
	public function get_tasks() {
		$tasks = [
			'gather_fact' => 'gather_fact',
		];
		if ( $this->settings->integrity_check ) {
			/**
			 * Changes since 2.4.7
			*/
			if ( $this->settings->check_core ) {
				$tasks['core_integrity_check'] = 'core_integrity_check';
			}
			if ( $this->settings->check_plugins ) {
				$tasks['plugin_integrity_check'] = 'plugin_integrity_check';
			}
			if ( $this->settings->check_themes ) {
				$tasks['theme_integrity_check'] = 'theme_integrity_check';
			}
		}
		if ( $this->is_pro() ) {
			if ( $this->settings->check_known_vuln ) {
				if ( $this->has_method( 'vuln_check' ) ) {
					$tasks['vuln_check'] = 'vuln_check';
				}
			}
			if ( $this->settings->scan_malware ) {
				if ( $this->has_method( 'suspicious_check' ) ) {
					$tasks['suspicious_check'] = 'suspicious_check';
				}
			}
		}

		return $tasks;
	}

	public function cancel_a_scan() {
		$scan = \WP_Defender\Model\Scan::get_active();
		if ( is_object( $scan ) ) {
			$scan->delete();
		}
		$this->clean_up();
		$this->remove_lock();
	}

	/**
	 * Clean up data generate by current scan
	 */
	public function clean_up() {
		delete_site_option( Gather_Fact::CACHE_CORE );
		delete_site_option( Gather_Fact::CACHE_CONTENT );
		delete_site_option( Malware_Scan::YARA_RULES );
		delete_site_option( Core_Integrity::CACHE_CHECKSUMS );
		delete_site_option( Theme_Integrity::THEME_SLUGS );
		delete_site_option( Plugin_Integrity::PLUGIN_SLUGS );
		$models = \WP_Defender\Model\Scan::get_last_all();
		if ( ! empty( $models ) ) {
			//remove the latest. Don't remove code to find the first value
			$current = array_shift( $models );
			foreach ( $models as $model ) {
				$model->delete();
			}
		}
	}

	/**
	 * Create a file lock, so we can check if a process already running
	 */
	public function create_lock() {
		file_put_contents( $this->get_lock_path(), time(), LOCK_EX );
	}

	/**
	 * Delete file lock
	 */
	public function remove_lock() {
		@unlink( $this->get_lock_path() );
	}

	/**
	 * Check if a lock is valid
	 *
	 * @return bool
	 */
	public function has_lock() {
		if ( ! file_exists( $this->get_lock_path() ) ) {
			return false;
		}
		$time = file_get_contents( $this->get_lock_path() );
		if ( strtotime( '+90 seconds', $time ) < time() ) {
			//usually a timeout window is 30 seconds, so we should allow lock at 1.30min for safe
			return false;
		}

		return true;
	}

	/**
	 * Get the total scanning active issues
	 * 
	 * @return integer $count
	 */
	public function indicator_issue_count() {
		$count = 0;
		$scan  = \WP_Defender\Model\Scan::get_last();
		if ( is_object( $scan ) && ! is_wp_error( $scan ) ) {
			//Only Scan issues
			$count = count( $scan->get_issues( null, \WP_Defender\Model\Scan_Item::STATUS_ACTIVE ) );
		}

		return $count;
	}
}
