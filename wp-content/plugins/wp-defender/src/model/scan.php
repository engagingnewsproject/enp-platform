<?php

namespace WP_Defender\Model;

use WP_Defender\Behavior\Scan_Item\Core_Integrity;
use WP_Defender\Behavior\Scan_Item\Plugin_Integrity;
use WP_Defender\Behavior\Scan_Item\Theme_Integrity;
use WP_Defender\Behavior\Scan_Item\Malware_Result;
use WP_Defender\Behavior\Scan_Item\Vuln_Result;
use WP_Defender\Component\Error_Code;
use WP_Defender\DB;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;

class Scan extends DB {
	use IO, Formats;

	const STATUS_INIT    = 'init', STATUS_ERROR = 'error', STATUS_FINISH = 'finish';
	const IGNORE_INDEXER = 'defender_scan_ignore_index';
	protected $table     = 'defender_scan';

	/**
	 * Any valid relative Date and Time formats.
	 *
	 * @link https://www.php.net/manual/en/datetime.formats.relative.php
	 *
	 * @since 2.6.1
	 *
	 * @var string
	 */
	const THRESHOLD_PERIOD = '3 hours ago';

	/**
	 * Constant to notate the scan is idle or crossed the threshold limit.
	 *
	 * @since 2.6.1
	 *
	 * @var string
	 */
	const STATUS_IDLE = 'idle';

	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Scan status, the native status is init, error, and finish, we can have other status base on the
	 * task the scan is running, like gather_fact, core_integrity etc.
	 *
	 * @var string
	 * @defender_property
	 */
	public $status;
	/**
	 * Mysql time.
	 * @var string
	 * @defender_property
	 */
	public $date_start;

	/**
	 * Store the current percent.
	 * @var int
	 * @defender_property
	 */
	public $percent = 0;

	/**
	 * Store how many tasks we process.
	 * @var int
	 * @defender_property
	 */
	public $total_tasks = 0;

	/**
	 * We will use this so internal task can store the current checkpoint.
	 *
	 * @var string
	 * @defender_property
	 */
	public $task_checkpoint = '';

	/**
	 * Mysql time.
	 * @var string
	 * @defender_property
	 */
	public $date_end;

	/**
	 * This only true when a scan trigger by report schedule.
	 * @var bool
	 * @defender_property
	 */
	public $is_automation = false;

	/**
	 * Return an array with various params, mostly this will be use.
	 *
	 * @return array
	 */
	public function prepare_issues( $per_page = null, $paged = null, $type = null ) {
		$ignored_models        = $this->get_issues( $type, Scan_Item::STATUS_IGNORE, $per_page, $paged );
		$active_models         = $this->get_issues( $type, Scan_Item::STATUS_ACTIVE, $per_page, $paged );
		$summary_models        = $this->get_issues();
		$issues                = array();
		$ignored               = array();
		$count_ignored         = 0;
		$count_total           = $count_ignored + count( $active_models );
		$count_issues          = 0;
		$count_issues_filtered = 0;
		$count_core            = 0;
		$count_plugin          = 0;
		$count_malware         = 0;
		$count_vuln            = 0;
		// Info for Summary box.
		foreach ( $summary_models as $summary_model ) {
			if ( Scan_Item::STATUS_ACTIVE === $summary_model->status ) {
				switch ($summary_model->type) {
					case Scan_Item::TYPE_INTEGRITY:
						$count_core++;
						break;
					case Scan_Item::TYPE_PLUGIN_CHECK:
						$count_plugin++;
						break;
					case Scan_Item::TYPE_SUSPICIOUS:
						$count_malware++;
						break;
					case Scan_Item::TYPE_VULNERABILITY:
					default:
						$count_vuln++;
						break;
				}
				// Count all issues.
				$count_issues++;
			} elseif ( Scan_Item::STATUS_IGNORE === $summary_model->status ) {
				$count_ignored ++;
			}
		}

		foreach ( $ignored_models as $model ) {
			$ignored[] = $model->to_array();
		}
		foreach ( $active_models as $active_model ) {
			$issues[] = $active_model->to_array();

			if ( Scan_Item::STATUS_ACTIVE === $active_model->status ) {
				// We will now count all issues again by type filter for pagination usage.
				if ( null !== $type && 'all' !== $type ) {
					if ( $type === $active_model->type ) {
						$count_issues_filtered++;
					}
				} else {
					$count_issues_filtered++;
				}
			}
		}

		return array(
			'ignored'               => $ignored,
			'issues'                => $issues,
			'count_total'           => $count_total,
			'count_issues'          => $count_issues,
			'count_issues_filtered' => $count_issues_filtered,
			'count_ignored'         => $count_ignored,
			'count_core'            => $count_core,
			'count_plugin'          => $count_plugin,
			'count_malware'         => $count_malware,
			'count_vuln'            => $count_vuln,
		);
	}

	/**
	 * @param null $type
	 * @param null $status
	 * @param null $per_page
	 * @param null $paged
	 *
	 * @return Scan_Item[]
	 */
	public function get_issues( $type = null, $status = null, $per_page = null, $paged = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( Scan_Item::class )
					->where( 'parent_id', $this->id );

		if (
			! is_null( $type )
			&& in_array(
				$type,
				array(
					Scan_Item::TYPE_VULNERABILITY,
					Scan_Item::TYPE_INTEGRITY,
					Scan_Item::TYPE_PLUGIN_CHECK,
					// Leave for migration to 2.5.0.
					Scan_Item::TYPE_THEME_CHECK,
					Scan_Item::TYPE_SUSPICIOUS,
				),
				true
			)
		) {
			$builder->where( 'type', $type );
		}
		if (
			! is_null( $status )
			&& in_array( $status, array( Scan_Item::STATUS_IGNORE, Scan_Item::STATUS_ACTIVE ), true )
		) {
			$builder->where( 'status', $status );
		}
		if ( ! is_null( $per_page ) && ! is_null( $paged ) ) {
			$limit = ( ( $paged - 1 ) * $per_page ) . ',' . $per_page;
			$builder->limit( $limit );
		}
		$models = $builder->get();
		foreach ( $models as $key => $model ) {
			switch ( $model->type ) {
				case Scan_Item::TYPE_INTEGRITY:
					$model->attach_behavior( Core_Integrity::class, Core_Integrity::class );
					break;
				// Leave for migration to 2.5.0.
				case Scan_Item::TYPE_THEME_CHECK:
					$model->attach_behavior( Theme_Integrity::class, Theme_Integrity::class );
					break;
				case Scan_Item::TYPE_PLUGIN_CHECK:
					$model->attach_behavior( Plugin_Integrity::class, Plugin_Integrity::class );
					break;
				case Scan_Item::TYPE_SUSPICIOUS:
					$model->attach_behavior( Malware_Result::class, Malware_Result::class );
					break;
				case Scan_Item::TYPE_VULNERABILITY:
				default:
					$model->attach_behavior( Vuln_Result::class, Vuln_Result::class );
					break;
			}
			$models[ $key ] = $model;
		}

		return $models;
	}

	public function count( $type = null, $status = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( Scan_Item::class )->where( 'parent_id', $this->id );

		if (
			! is_null( $type )
			&& in_array(
				$type,
				array(
					Scan_Item::TYPE_VULNERABILITY,
					Scan_Item::TYPE_INTEGRITY,
					Scan_Item::TYPE_PLUGIN_CHECK,
					Scan_Item::TYPE_SUSPICIOUS,
				),
				true
			)
		) {
			$builder->where( 'type', $type );
		}
		if (
			! is_null( $status )
			&& in_array( $status, array( Scan_Item::STATUS_IGNORE, Scan_Item::STATUS_ACTIVE ), true )
		) {
			$builder->where( 'status', $status );
		}

		return $builder->count();
	}
	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function unignore_issue( $id ) {
		$issue = $this->get_issue( $id );
		if ( ! is_object( $issue ) ) {
			return false;
		}
		$issue->status = Scan_Item::STATUS_ACTIVE;
		$issue->save();

		$ignore_lists = get_site_option( self::IGNORE_INDEXER, array() );
		$data         = $issue->raw_data;
		if ( isset( $data['file'] ) ) {
			unset( $ignore_lists[ array_search( $data['file'], $ignore_lists ) ] );
		} elseif ( isset( $data['slug'] ) ) {
			unset( $ignore_lists[ array_search( $data['slug'], $ignore_lists ) ] );
		}
		$this->update_ignore_list( $ignore_lists );
	}

	/**
	 * Check if a slug is ignored, we use a global indexer, so we can check while
	 * the active scan is running.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function is_issue_ignored( $slug ) {
		$ignore_lists = get_site_option( self::IGNORE_INDEXER, array() );

		return in_array( $slug, $ignore_lists, true );
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function ignore_issue( $id ) {
		$issue = $this->get_issue( $id );
		if ( ! is_object( $issue ) ) {
			return false;
		}

		$issue->status = Scan_Item::STATUS_IGNORE;
		$issue->save();

		// Add this into global ingnore index.
		$ignore_lists = get_site_option( self::IGNORE_INDEXER, array() );
		$data         = $issue->raw_data;
		if ( isset( $data['file'] ) ) {
			$ignore_lists[] = $data['file'];
		} elseif ( isset( $data['slug'] ) ) {
			$ignore_lists[] = $data['slug'];
		}
		$this->update_ignore_list( $ignore_lists );
	}

	/**
	 * @param int $id
	 *
	 * @return Scan_Item|null
	 */
	public function get_issue( $id ) {
		$orm   = self::get_orm();
		$model = $orm->get_repository( Scan_Item::class )
			->where( 'id', $id )
			->first();
		if ( is_object( $model ) ) {
			switch ( $model->type ) {
				case Scan_Item::TYPE_INTEGRITY:
					$model->attach_behavior( Core_Integrity::class, Core_Integrity::class );
					break;
				case Scan_Item::TYPE_PLUGIN_CHECK:
					$model->attach_behavior( Plugin_Integrity::class, Plugin_Integrity::class );
					break;
				case Scan_Item::TYPE_SUSPICIOUS:
					$model->attach_behavior( Malware_Result::class, Malware_Result::class );
					break;
				case Scan_Item::TYPE_VULNERABILITY:
				default:
					$model->attach_behavior( Vuln_Result::class, Vuln_Result::class );
					break;
			}
		}

		return $model;
	}

	/**
	 * Remove an issue, this will happen when that issue is resolve, or the file link
	 * to this issue get deleted.
	 *
	 * @param int $id
	 */
	public function remove_issue( $id ) {
		$orm = self::get_orm();
		$orm->get_repository( Scan_Item::class )->delete( array( 'id' => $id ) );
	}

	/**
	 * This will build the data we use to output to frontend, base on the current scenario.
	 * @param null|int $per_page
	 * @param null|int $paged
	 * @param null|string $type
	 *
	 * @return array
	 */
	public function to_array( $per_page = null, $paged = null, $type = null ) {
		if ( ! in_array( $this->status, array( self::STATUS_ERROR, self::STATUS_FINISH, self::STATUS_IDLE ), true ) ) {

			return array(
				'status'      => $this->status,
				'status_text' => $this->get_status_text(),
				'percent'     => $this->percent,
				// This only for hub, when a scan running.
				'count'       => array(
					'total' => 0,
				),
			);
		} elseif ( in_array( $this->status, array( self::STATUS_FINISH, self::STATUS_IDLE ), true ) ) {
			$total_data            = $this->prepare_issues( null, null, $type );
			$total_filtered        = $this->count( $type );
			$count_issues_filtered = $total_data['count_issues_filtered'];
			$ignored_count         = $total_data['count_ignored'];
			$total_count           = $total_data['count_total'];

			$total_issue_pages   = 1;
			$total_ignored_pages = 1;
			if( ! is_null( $per_page ) && ( $total_count > $per_page ) ) {
				$data = $this->prepare_issues( $per_page, $paged, $type );
				if (  ! is_null( $paged ) ) {
					$total_issue_pages   = ceil( $count_issues_filtered / $per_page );
					$total_ignored_pages = ceil( $ignored_count / $per_page );
				}
			} else {
				$data = $total_data;
			}

			return array(
				'status'        => $this->status,
				'issues_items'  => $data['issues'],
				'ignored_items' => $data['ignored'],
				'last_scan'     => $this->format_date_time( $this->date_start ),
				'count'         => array(
					'total'                 => count( $data['issues'] ),
					'total_filtered'        => $total_filtered,
					'issues_total'          => $total_data['count_issues'],
					'issues_total_filtered' => $count_issues_filtered,
					'ignored_total'         => $ignored_count,
					'core'                  => $total_data['count_core'] + $total_data['count_plugin'],
					'content'               => $total_data['count_malware'],
					'vuln'                  => $total_data['count_vuln'],
				),
				'paging'       => array(
					'issue'    => array(
						'paged'       => $paged,
						'total_pages' => $total_issue_pages,
					),
					'ignored'  => array(
						'paged'       => $paged,
						'total_pages' => $total_ignored_pages,
					),
					'per_page' => $per_page,
				),
			);
		}
	}

	/**
	 * @param false $from_report
	 *
	 * @return Scan|\WP_Error
	 */
	public static function create( $from_report = false ) {
		$orm    = self::get_orm();
		$active = self::get_active();
		if ( is_object( $active ) ) {
			return new \WP_Error( Error_Code::INVALID, __( 'A scan is already in progress.', 'wpdef' ) );
		}
		$model                = new Scan();
		$model->status        = self::STATUS_INIT;
		$model->date_start    = gmdate( 'Y-m-d H:i:s' );
		$model->date_end      = gmdate( 'Y-m-d H:i:s' );
		$model->is_automation = $from_report;

		$orm->save( $model );

		return $model;
	}

	/**
	 * Delete current scan.
	 *
	 * @param int $id Table primary key id.
	 */
	public function delete( $id = null ) {
		if ( ! $this->is_positive_int( $id ) ) {
			$id = $this->id;
		}

		// Delete all the related result items.
		$orm = self::get_orm();

		$orm->get_repository( Scan_Item::class )->delete(
			array(
				'parent_id' => $id,
			)
		);

		$orm->get_repository( self::class )->delete(
			array(
				'id' => $id,
			)
		);
	}

	/**
	 * Get the current active scan if any.
	 *
	 * @return self|null
	 */
	public static function get_active() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
			->where( 'status', 'NOT IN', array( self::STATUS_FINISH, self::STATUS_ERROR, self::STATUS_IDLE ) )
			->first();
	}

	/**
	 * Get last result.
	 *
	 * @return self|null
	 */
	public static function get_last() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
			->where( 'status', 'IN', array( self::STATUS_FINISH, self::STATUS_IDLE ) )
			->order_by( 'id', 'desc' )
			->first();
	}

	/**
	 * @return array
	 */
	public static function get_last_all() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
			->where( 'status', 'IN', array( self::STATUS_FINISH, self::STATUS_IDLE ) )
			->order_by( 'id', 'desc' )
			->get();
	}

	/**
	 * If the scan find any, we will use this to add the issue.
	 *
	 * @param $type
	 * @param $data
	 * @param $status
	 */
	public function add_item( $type, $data, $status = Scan_Item::STATUS_ACTIVE ) {
		$model            = new Scan_Item();
		$model->type      = $type;
		$model->parent_id = $this->id;
		$model->raw_data  = $data;
		$model->status    = $status;
		$ret              = $model->save();

		return $ret;
	}

	/**
	 * Return current status as readable string.
	 *
	 * @return string
	 */
	public function get_status_text() {
		switch ( $this->status ) {
			case self::STATUS_INIT:
				return __( 'Initializing...', 'wpdef' );
			case 'gather_fact':
				return __( 'Gathering information...', 'wpdef' );
			case 'core_integrity_check':
				return __( 'Analyzing WordPress Core...', 'wpdef' );
			case 'plugin_integrity_check':
				return __( 'Analyzing WordPress Plugins...', 'wpdef' );
			case 'vuln_check':
				return __( 'Checking for any published vulnerabilities in your plugins & themes...', 'wpdef' );
			case 'suspicious_check':
				return __( 'Analyzing WordPress Content...', 'wpdef' );
			default:
				return __( 'The scan is running', 'wpdef' );
		}
	}

	/**
	 * Calculation scan percentage base on the tasks percent.
	 *
	 * @param $task_percent
	 * @param $pos
	 *
	 * @return float
	 */
	public function calculate_percent( $task_percent, $pos = 1 ) {
		$task_max      = 100 / $this->total_tasks;
		$task_base     = $task_max * ( $pos - 1 );
		$micro         = $task_percent * $task_max / 100;
		$this->percent = round( $task_base + $micro, 2 );
		if ( $this->percent > 100 ) {
			$this->percent = 100;
		}

		return $this->percent;
	}

	/**
	 * Get list of whitelisted files.
	 *
	 * @return array
	 */
	private function whitelisted_files() {

		return array(
			// Configuration files.
			'user.ini',
			'php.ini',
			'robots.txt',
			'.htaccess',
			'nginx.conf',
			// hidden system files and directories
			'.well_known',
			'.idea',
			'.DS_Store',
			'.svn',
			'.git',
			'.quarantine',
			'.tmb',
		);
	}

	/**
	 * Check if a slug is whitelisted.
	 *
	 * @param string $slug path to file
	 *
	 * @return bool
	 */
	public function is_issue_whitelisted( $slug ) {
		$whitelisted_files = $this->whitelisted_files();
		foreach ( $whitelisted_files as $file ) {
			if ( false !== stristr( $slug, $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $ignore_lists
	 */
	public function update_ignore_list( $ignore_lists ) {
		$ignore_lists = array_unique( $ignore_lists );
		$ignore_lists = array_filter( $ignore_lists );
		update_site_option( self::IGNORE_INDEXER, $ignore_lists );
	}

	/**
	 * Get the threshold time limit as DateTime object.
	 *
	 * @return \DateTime Threshold time limit as DateTime object.
	 */
	public function threshold_date_time_object() {
		$timezone = new \DateTimeZone( 'UTC' );

		/**
		 * Filter to override scan threshold period.
		 *
		 * @since 2.6.1
		 *
		 * @link https://www.php.net/manual/en/datetime.formats.relative.php
		 *
		 * @param string  $threshold Any valid relative Date and Time formats.
		 */
		$threshold = apply_filters( 'wd_scan_threshold', self::THRESHOLD_PERIOD );

		return new \DateTime( $threshold, $timezone );
	}

	/**
	 * Threshold time limit in mysql string format.
	 *
	 * @return string Threshold time limit as mysql string format.
	 */
	public function threshold_date_time_mysql() {
		$type = 'Y-m-d H:i:s';

		$threshold_date_time_object = $this->threshold_date_time_object();

		$mysql_format = $threshold_date_time_object->format( $type );

		return $mysql_format;
	}

	/**
	 * Get the idle scan if any.
	 *
	 * @return self|null
	 */
	public function get_idle() {
		$orm = self::get_orm();

		$mysql_date = $this->threshold_date_time_mysql();

		return $orm->get_repository( self::class )
			->where( 'status', 'NOT IN', array( self::STATUS_FINISH, self::STATUS_ERROR ) )
			->where( 'date_start', '<', $mysql_date )
			->first();
	}

	/**
	 * Delete all idle scan and scan items
	 *
	 * @since 2.6.1
	 */
	public function delete_idle() {
		$idle_scans = self::get_orm()
			->get_repository( self::class )
			->where( 'status', self::STATUS_IDLE )
			->get();

		foreach ( $idle_scans as $idle_scan ) {
			$this->delete( $idle_scan->id );
		}
	}

	/**
	 * Verify positive integer or not.
	 *
	 * @since 2.6.1
	 *
	 * @param mixed $id Argument to check for a positive number.
	 *
	 * @return bool Return true on positive integer else false.
	 */
	private function is_positive_int( $id ) {
		return is_int( $id ) && $id > 0;
	}
}