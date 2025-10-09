<?php
/**
 * Handling the scanning process.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Countable;
use ArrayIterator;
use WP_Defender\Component;
use WP_Plugins_List_Table;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Scan as Scan_Model;
use WP_Defender\Behavior\Scan\Gather_Fact;
use WP_Defender\Behavior\Scan\Malware_Scan;
use WP_Defender\Behavior\Scan\Core_Integrity;
use WP_Defender\Behavior\Scan\Plugin_Integrity;
use WP_Defender\Behavior\Scan\Malware_Deep_Scan;
use WP_Defender\Behavior\Scan\Malware_Quick_Scan;
use WP_Defender\Behavior\Scan\Known_Vulnerability;
use WP_Defender\Behavior\Scan\Abandoned_Plugin;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Helper\Analytics\Scan as Scan_Analytics;
use WP_Defender\Controller\Scan as Scan_Controller;

/**
 * The Scan class handles the scanning process, managing tasks, and coordinating different types of scans.
 */
class Scan extends Component {
	use \WP_Defender\Traits\Plugin;

	// For all Scan types where plugins are used.
	public const PLUGINS_ACTIONED = 'wp-defender-actioned-plugins';

	/**
	 * The current scan model.
	 *
	 * @var Scan_Model
	 */
	public $scan;

	/**
	 * Scan settings model.
	 *
	 * @var Scan_Settings
	 */
	public $settings;

	/**
	 * Details of vulnerabilities found during the scan.
	 *
	 * @var array
	 */
	protected $vulnerability_details = array();

	/**
	 * Instance of Known_Vulnerability to handle known vulnerability checks.
	 *
	 * @var Known_Vulnerability
	 */
	private $known_vulnerability;

	/**
	 * Instance of Malware_Scan to handle malware scanning.
	 *
	 * @var Malware_Scan
	 */
	private $malware_scan;

	/**
	 * Instance of Gather_Fact to gather necessary information before scanning.
	 *
	 * @var Gather_Fact|null
	 */
	private ?Gather_Fact $gather_fact;

	/**
	 * Instance of Abandoned_Plugin to handle abandoned plugin checks.
	 *
	 * @var Abandoned_Plugin
	 */
	private $abandoned_plugin;

	/**
	 * Lock file name for scanning.
	 *
	 * @var string
	 */
	protected string $lock_filename = 'scan.lock';

	/**
	 * Indicate whether the current installation is a pro version.
	 *
	 * @var bool
	 */
	private $is_pro;

	/**
	 * Constructs the Scan object and initializes behaviors.
	 */
	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->attach_behavior( Core_Integrity::class, Core_Integrity::class );
		$this->attach_behavior( Plugin_Integrity::class, Plugin_Integrity::class );
		$this->is_pro   = wd_di()->get( WPMUDEV::class )->is_pro();
		$this->settings = wd_di()->get( Scan_Settings::class );
	}

	/**
	 * Performs additional actions after an advanced scan.
	 *
	 * @param Scan_Model $model  The scan model.
	 */
	public function advanced_scan_actions( $model ) {
		$this->reindex_ignored_issues( $model );
		$this->clean_up();

		if ( defender_is_wp_org_version() ) {
			Rate::run_counter_of_completed_scans();
		}
	}

	/**
	 * Process current scan.
	 *
	 * @return bool|int
	 */
	public function process() {
		$scan = Scan_Model::get_active();
		if ( ! is_object( $scan ) ) {
			// This case can be a scan get cancel.
			return - 1;
		}
		$this->scan = $scan;
		$tasks      = $this->get_tasks();
		$runner     = new ArrayIterator( $tasks );
		$task       = $this->scan->status;
		if ( Scan_Model::STATUS_INIT === $scan->status ) {
			// Get the first.
			$this->log( 'Prepare facts for a scan', Scan_Controller::SCAN_LOG );
			$task                    = Scan_Model::STEP_GATHER_INFO;
			$this->scan->percent     = 0;
			$this->scan->total_tasks = $runner->count();
			$this->scan->save();
		}
		if (
			in_array(
				$this->scan->status,
				array(
					Scan_Model::STATUS_ERROR,
					Scan_Model::STATUS_IDLE,
				),
				true
			)
		) {
			// Stop and return true to abort the process.
			return true;
		}
		// Find the current task.
		$offset = array_search( $task, array_values( $tasks ), true );
		if ( false === $offset ) {
			$this->log( sprintf( 'offset is not found, search %s', $task ), Scan_Controller::SCAN_LOG );

			return false;
		}
		// Reset the tasks to current.
		$runner->seek( $offset );
		$this->log( sprintf( 'Current task %s', $runner->current() ), Scan_Controller::SCAN_LOG );
		if ( $this->has_method( $task ) ) {
			$this->log( sprintf( 'processing %s', $runner->key() ), Scan_Controller::SCAN_LOG );
			$result = $this->task_handler( $task );
			if ( true === $result ) {
				$this->log( sprintf( 'task %s processed', $runner->key() ), Scan_Controller::SCAN_LOG );
				// Task is done, move to next.
				$runner->next();
				if ( $runner->valid() ) {
					$this->log( sprintf( 'queue %s for next', $runner->key() ), Scan_Controller::SCAN_LOG );
					$this->scan->status          = $runner->key();
					$this->scan->task_checkpoint = '';
					$this->scan->date_end        = gmdate( 'Y-m-d H:i:s' );
					$this->scan->save();
					// Queue for next run.
					return false;
				}
				$this->log( 'All done!', Scan_Controller::SCAN_LOG );
				// No more task in the queue, we are done.
				$this->scan->status = Scan_Model::STATUS_FINISH;
				$this->scan->save();
				$this->advanced_scan_actions( $this->scan );
				do_action( 'defender_notify', 'malware-notification', $this->scan );

				return true;
			}
			$this->scan->status = $task;
			$this->scan->save();
		}

		return false;
	}

	/**
	 * Reindex ignored issues to update their status in the scan model.
	 *
	 * @param  Scan_Model $model  The scan model.
	 */
	private function reindex_ignored_issues( $model ) {
		$issues       = $model->get_issues( null, Scan_Item::STATUS_IGNORE );
		$ignore_lists = array();
		foreach ( $issues as $issue ) {
			$data = $issue->raw_data;
			if ( isset( $data['file'] ) ) {
				$ignore_lists[] = $data['file'];
			} elseif ( isset( $data['slug'] ) ) {
				$ignore_lists[] = $data['slug'];
			}
		}
		$model->update_ignore_list( $ignore_lists );
	}

	/**
	 * Get a list of tasks will run in a scan.
	 *
	 * @return array
	 */
	public function get_tasks(): array {
		$tasks = array( Scan_Model::STEP_GATHER_INFO => 'gather_info' );
		if ( $this->settings->integrity_check ) {
			// Nested options.
			if ( $this->settings->check_core ) {
				$tasks[ Scan_Model::STEP_CHECK_CORE ] = 'core_integrity_check';
			}
			if ( $this->settings->check_plugins ) {
				$tasks[ Scan_Model::STEP_CHECK_PLUGIN ] = 'plugin_integrity_check';
			}
		}

		if ( $this->settings->check_abandoned_plugin ) {
			$tasks[ Scan_Model::STEP_ABANDONED_PLUGIN_CHECK ] = 'abandoned_plugin_check';
		}
		if ( $this->is_pro ) {
			if ( $this->settings->check_known_vuln && $this->has_method( Scan_Model::STEP_VULN_CHECK ) ) {
				$tasks[ Scan_Model::STEP_VULN_CHECK ] = 'vuln_check';
			}
			if ( $this->settings->scan_malware && $this->has_method( Scan_Model::STEP_SUSPICIOUS_CHECK ) ) {
				$tasks[ Scan_Model::STEP_SUSPICIOUS_CHECK ] = 'suspicious_check';
			}
		}

		return $tasks;
	}

	/**
	 * Handles individual scan tasks based on the task identifier.
	 *
	 * @param  string $task  The task identifier.
	 *
	 * @return bool Returns true if the task was handled successfully.
	 */
	private function task_handler( $task ) {
		switch ( $task ) {
			case 'gather_info':
				if ( empty( $this->gather_fact ) && class_exists( Gather_Fact::class ) ) {
					$this->set_gather_fact(
						wd_di()->make( Gather_Fact::class, array( 'scan' => $this->scan ) )
					);
				}

				return $this->gather_info( $this->gather_fact );
			case 'vuln_check':
				if ( empty( $this->known_vulnerability ) && class_exists( Known_Vulnerability::class ) ) {
					$this->set_known_vulnerability(
						wd_di()->make( Known_Vulnerability::class, array( 'scan' => $this->scan ) )
					);
				}

				return $this->vuln_check( $this->known_vulnerability );
			case 'suspicious_check':
				if ( class_exists( Malware_Scan::class ) ) {
					$this->set_malware_scan(
						wd_di()->make( Malware_Scan::class, array( 'scan' => $this->scan ) )
					);
				}

				return $this->suspicious_check( $this->malware_scan );
			case 'abandoned_plugin_check':
				if ( empty( $this->abandoned_plugin ) && class_exists( Abandoned_Plugin::class ) ) {
					$this->set_abandoned_plugin(
						wd_di()->make( Abandoned_Plugin::class, array( 'scan' => $this->scan ) )
					);
				}

				return $this->abandoned_plugin_check( $this->abandoned_plugin );
			default:
				return $this->$task();
		}
	}

	/**
	 * A wrapper method for Known_Vulnerability class method vuln_check.
	 *
	 * @param  Known_Vulnerability $known_vulnerability  An instance of Known_Vulnerability.
	 *
	 * @return bool True always as in wrapped method Known_Vulnerability::vuln_check.
	 */
	private function vuln_check( Known_Vulnerability $known_vulnerability ): bool {
		if ( method_exists( $known_vulnerability, 'vuln_check' ) ) {
			return $known_vulnerability->vuln_check();
		}

		return true; // Followed Known_Vulnerability::vuln_check return pattern i.e. always true for skipped vuln check.
	}

	/**
	 * Setter injection method for Known_Vulnerability instance.
	 *
	 * @param  Known_Vulnerability $known_vulnerability  The Known_Vulnerability instance to set.
	 */
	public function set_known_vulnerability( Known_Vulnerability $known_vulnerability ) {
		if ( class_exists( Known_Vulnerability::class ) ) {
			$this->known_vulnerability = $known_vulnerability;
		}
	}

	/**
	 * A wrapper method for Malware_Scan class method vuln_check.
	 *
	 * @param  Malware_Scan $malware_scan  An instance of Malware_Scan.
	 *
	 * @return bool True if method Malware_Scan::suspicious_check not exists else bool value returned by that method.
	 */
	private function suspicious_check( Malware_Scan $malware_scan ): bool {
		if ( method_exists( $malware_scan, 'suspicious_check' ) ) {
			$quick_scan = wd_di()->get( Malware_Quick_Scan::class );
			$deep_scan  = wd_di()->get( Malware_Deep_Scan::class );

			return $malware_scan->suspicious_check( $quick_scan, $deep_scan );
		}

		return true;
	}

	/**
	 * Setter injection method for Malware_Scan instance.
	 *
	 * @param  Malware_Scan $malware_scan  The Malware_Scan instance to set.
	 */
	public function set_malware_scan( Malware_Scan $malware_scan ) {
		if ( class_exists( Malware_Scan::class ) ) {
			$this->malware_scan = $malware_scan;
		}
	}

	/**
	 * Set the Abandoned_Plugin object.
	 *
	 * @param  Abandoned_Plugin $abandoned_plugin  The Abandoned_Plugin object to set.
	 */
	public function set_abandoned_plugin( Abandoned_Plugin $abandoned_plugin ) {
		if ( class_exists( Abandoned_Plugin::class ) ) {
			$this->abandoned_plugin = $abandoned_plugin;
		}
	}

	/**
	 * A wrapper method for Abandoned_Plugin class method abandoned_plugin_check.
	 *
	 * @param  Abandoned_Plugin $abandoned_plugin  An instance of Abandoned_Plugin.
	 *
	 * @return bool
	 */
	private function abandoned_plugin_check( Abandoned_Plugin $abandoned_plugin ): bool {
		if ( method_exists( $abandoned_plugin, 'abandoned_plugin_check' ) ) {
			return $abandoned_plugin->abandoned_plugin_check();
		}

		return true;
	}

	/**
	 * Cancels an active scan and cleans up related data.
	 */
	public function cancel_a_scan() {
		$scan = Scan_Model::get_active();
		if ( is_object( $scan ) ) {
			$scan->delete();
		}
		$this->clean_up();
		$this->remove_lock();

		$scan_analytics = wd_di()->get( Scan_Analytics::class );

		$scan_analytics->track_feature(
			$scan_analytics::EVENT_SCAN_FAILED,
			array(
				$scan_analytics::EVENT_SCAN_FAILED_PROP => $scan_analytics::EVENT_SCAN_FAILED_CANCEL,
			)
		);
	}

	/**
	 * Track the event if we have a failed checksum.
	 */
	public function maybe_track_failed_checksum() {
		// If there is a Checksum issue, then check DB's value from time().
		$checksum_issue = (int) get_site_option( Core_Integrity::ISSUE_CHECKSUMS, 0 );
		if ( $checksum_issue > 0 ) {
			$scan_analytics = wd_di()->get( Scan_Analytics::class );
			$reason         = 'Failed to fetch checksums from wp.org';

			$scan_analytics->track_feature(
				$scan_analytics::EVENT_SCAN_FAILED,
				array(
					$scan_analytics::EVENT_SCAN_FAILED_PROP => $scan_analytics::EVENT_SCAN_FAILED_ERROR,
					'Error_Reason' => $reason,
				)
			);

			$this->log( $reason, Scan_Controller::SCAN_LOG );
		}
	}

	/**
	 * Clean up data generate by current scan.
	 */
	public function clean_up() {
		$this->delete_interim_data();

		$models = Scan_Model::get_last_all();
		if ( ! empty( $models ) ) {
			// Remove the latest. Don't remove code to find the first value.
			$current = array_shift( $models );
			foreach ( $models as $model ) {
				$model->delete();
			}
		}
	}

	/**
	 * Get the total scanning active issues.
	 *
	 * @return int $count
	 */
	public function indicator_issue_count(): int {
		$count = 0;
		$scan  = Scan_Model::get_last();
		if ( is_object( $scan ) && ! is_wp_error( $scan ) ) {
			// Only Scan issues.
			$count = (int) $scan->count( null, Scan_Item::STATUS_ACTIVE );
		}

		return $count;
	}

	/**
	 * Checks if any scan type is active based on the scan settings and the user's membership status.
	 *
	 * @param  array $scan_settings  The scan settings.
	 *
	 * @return bool Returns true if any scan type is active, false otherwise.
	 */
	public function is_any_scan_active( $scan_settings ): bool {
		if ( empty( $scan_settings['integrity_check'] ) ) {
			// Check the parent type.
			$file_change_check = false;
		} elseif (
			$scan_settings['integrity_check']
			&& empty( $scan_settings['check_core'] )
			&& empty( $scan_settings['check_plugins'] )
		) {
			// Check the parent and child types.
			$file_change_check = false;
		} else {
			$file_change_check = true;
		}
		// Similar to is_any_active(...) method from the controller.
		if ( $this->is_pro ) {
			// Pro version. Check all parent types.
			return $file_change_check || ! empty( $scan_settings['check_known_vuln'] )
				|| ! empty( $scan_settings['scan_malware'] );
		} else {
			// Free version.
			return $file_change_check || ! empty( $scan_settings['check_abandoned_plugin'] );
		}
	}

	/**
	 * Update the idle scan status due the time limit.
	 *
	 * @since 2.6.1
	 */
	public function update_idle_scan_status() {
		$idle_scan = wd_di()->get( Scan_Model::class )->get_idle();

		if ( is_object( $idle_scan ) ) {
			$ready_to_send = false;
			if ( Scan_Model::STATUS_IDLE === $idle_scan->status ) {
				$ready_to_send = true;
			}
			$this->delete_interim_data();

			as_unschedule_all_actions( 'defender/async_scan' );

			$idle_scan->status          = Scan_Model::STATUS_IDLE;
			$idle_scan->task_checkpoint = 'time_limit';
			$idle_scan->save();

			$this->remove_lock();
			if ( $ready_to_send ) {
				do_action( 'defender_notify', 'malware-notification', $idle_scan );
			}
		}
	}

	/**
	 * Update the idle scan status due the checksum issue.
	 *
	 * @param object $scan The current scan.
	 *
	 * @since 4.9.0
	 */
	public function update_idle_scan_status_by_checksum_issue( $scan ) {
		$ready_to_send = false;
		if ( Scan_Model::STATUS_IDLE === $scan->status ) {
			$ready_to_send = true;
		}
		$this->delete_interim_data();

		as_unschedule_all_actions( 'defender/async_scan' );

		$scan->status          = Scan_Model::STATUS_IDLE;
		$scan->task_checkpoint = 'checksum_issue';
		$scan->save();

		$this->remove_lock();
		if ( $ready_to_send ) {
			do_action( 'defender_notify', 'malware-notification', $scan );
		}
	}

	/**
	 * Clear all temporary scan data.
	 *
	 * @since 2.6.1
	 */
	private function delete_interim_data() {
		delete_site_option( Gather_Fact::CACHE_CORE );
		delete_site_option( Gather_Fact::CACHE_CONTENT );
		delete_site_option( Malware_Scan::YARA_RULES );
		delete_site_option( Core_Integrity::CACHE_CHECKSUMS );
		delete_site_option( Plugin_Integrity::PLUGIN_SLUGS );
		delete_site_option( Plugin_Integrity::PLUGIN_PREMIUM_SLUGS );
		delete_site_option( self::PLUGINS_ACTIONED );
		$this->maybe_track_failed_checksum();
	}

	/**
	 * Display styles on the Plugins page.
	 */
	public function show_plugin_admin_styles() {
		$custom_css = '.vulnerability-indent{ padding-left: 26px; }
		.plugins .plugin-update-tr .plugin-update.plugin-vulnerability{box-shadow: inset 0 0px 0 rgb(0 0 0 / 10%);
		border-bottom: rgb(0 0 0 / 10%) solid 1px;}';
		wp_add_inline_style( 'defender-menu', $custom_css );
	}

	/**
	 * Display update information for a plugin.
	 *
	 * @param  string $file  Plugin basename.
	 * @param  array  $plugin_data  Plugin information.
	 *
	 * @return void
	 */
	public function attach_plugin_vulnerability_warning( $file, $plugin_data ) {
		/**
		 * WP Plugin list table instance.
		 *
		 * @var WP_Plugins_List_Table $wp_list_table
		 */
		$wp_list_table = _get_list_table(
			'WP_Plugins_List_Table',
			array( 'screen' => get_current_screen() )
		);
		$bugs          = $this->vulnerability_details[ $file ]['bugs'];
		if ( empty( $bugs ) ) {
			return;
		}
		$last_fixed_in = '0';
		// Check if there have been updates since the last scan.
		$exist_update = true;
		if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] ) ) {
			// The current plugin version.
			$current_version = $plugin_data['Version'];
			foreach ( $bugs as $bug_details ) {
				// If the fixed version is existed then get the latest one.
				if ( isset( $bug_details['fixed_in'] ) && ! empty( $bug_details['fixed_in'] )
					&& version_compare( $bug_details['fixed_in'], $last_fixed_in, '>' )
				) {
					$last_fixed_in = $bug_details['fixed_in'];
				}
			}
			if ( version_compare( $last_fixed_in, $current_version, '>' ) ) {
				$exist_update = false;
			}
		}
		// If there were updates, do not display notice.
		if ( $exist_update ) {
			return;
		}
		if ( empty( $plugin_data['slug'] ) && isset( $this->vulnerability_details[ $file ]['base_slug'] ) ) {
			$plugin_data['slug'] = $this->vulnerability_details[ $file ]['base_slug'];
		}

		if ( is_network_admin() || ! is_multisite() ) {
			if ( is_network_admin() ) {
				$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
			} else {
				$active_class = is_plugin_active( $file ) ? ' active' : '';
			}

			printf(
				'<tr class="plugin-update-tr%s" id="vulnerability-%s" data-slug="%s" data-plugin="%s"><td colspan="%s" class="plugin-update colspanchange plugin-vulnerability"><div class="update-message notice inline %s notice-alt"><p>',
				esc_attr( $active_class ),
				esc_attr( $plugin_data['slug'] ),
				esc_attr( $plugin_data['slug'] ),
				esc_attr( $file ),
				esc_attr( $wp_list_table->get_column_count() ),
				'notice-error'
			);

			$notice = sprintf(
			/* translators: %s - Plugin name. */
				esc_html__(
					'%s has detected a vulnerability in this plugin that may cause harm to your site.',
					'wpdef'
				),
				'<b>' . esc_html__( 'Defender Pro', 'wpdef' ) . '</b>'
			);
			if ( ( is_array( $bugs ) || $bugs instanceof Countable ? count( $bugs ) : 0 ) > 1 ) {
				$notice .= '<hr/>';
				$lines   = array();
				foreach ( $bugs as $bug ) {
					$lines[] = '<span class="vulnerability-indent"></span>' . $bug['title'];
				}
				$notice .= implode( '<br/>', $lines );
				$notice .= '<hr/><span class="vulnerability-indent"></span>';
				if ( '0' !== $last_fixed_in ) {
					$notice .= sprintf(
					/* translators: %s - Version number. */
						esc_html__(
							'The vulnerability has been fixed in version %s. We recommend that you update this plugin accordingly.',
							'wpdef'
						),
						$last_fixed_in
					);
				} else {
					$notice .= esc_html__(
						'Important! We recommend that you deactivate this plugin until the vulnerability has been fixed.',
						'wpdef'
					);
				}
			} else {
				$notice .= '<br/><span class="vulnerability-indent"></span>' . $bugs[0]['title'] . '<br/><span class="vulnerability-indent"></span>';
				$notice .= empty( $last_fixed_in )
					? esc_html__(
						'We recommend that you deactivate this plugin until the vulnerability has been fixed.',
						'wpdef'
					)
					: sprintf(
					/* translators: 1: Version number. */
						esc_html__(
							'The vulnerability has been fixed in version %s. We recommend that you update this plugin accordingly.',
							'wpdef'
						),
						$last_fixed_in
					);
			}

			printf( wp_kses_post( $notice ) );
		}
	}

	/**
	 * Display warnings.
	 *
	 * @since 2.6.2
	 */
	public function display_vulnerability_warnings() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$last = Scan_Model::get_last();
		if ( is_object( $last ) && ! is_wp_error( $last ) ) {
			$vulnerability_issues = $last->get_issues( Scan_Item::TYPE_VULNERABILITY );
			if ( empty( $vulnerability_issues ) ) {
				return;
			}

			add_action( 'admin_print_styles-plugins.php', array( $this, 'show_plugin_admin_styles' ) );
			// Vulnerability list.
			foreach ( $vulnerability_issues as $vulnerability_obj ) {
				$plugin_slug = $vulnerability_obj->raw_data['slug'];
				// Get the details so that you can apply them later for each plugin.
				$this->vulnerability_details[ $plugin_slug ] = $vulnerability_obj->raw_data;
				add_action(
					"after_plugin_row_$plugin_slug",
					array(
						$this,
						'attach_plugin_vulnerability_warning',
					),
					100,
					2
				);
			}
		}
	}

	/**
	 * Clear completed action scheduler logs.
	 *
	 * @since 2.6.5
	 */
	public static function clear_logs() {
		global $wpdb;

		$table_actions = ! empty( $wpdb->actionscheduler_actions ) ?
			$wpdb->actionscheduler_actions :
			$wpdb->prefix . 'actionscheduler_actions';
		$table_logs    = ! empty( $wpdb->actionscheduler_logs ) ?
			$wpdb->actionscheduler_logs :
			$wpdb->prefix . 'actionscheduler_logs';

		$table_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT count(*)
				 FROM information_schema.tables
				 WHERE table_schema = %s AND table_name IN (%s, %s);',
				$wpdb->dbname,
				$table_actions,
				$table_logs
			)
		);

		if ( 2 !== $table_count ) {
			return array( 'error' => esc_html__( 'Action scheduler is not setup', 'wpdef' ) );
		}

		$hook       = 'defender/async_scan';
		$status     = 'complete';
		$limit      = 100;
		$action_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT action_id FROM {$table_actions} as_actions WHERE as_actions.hook = %s AND as_actions.status = %s LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$hook,
				$status,
				$limit
			)
		);
		while ( $action_ids ) {
			if ( empty( $action_ids ) ) {
				break;
			}

			$query = "DELETE as_actions, as_logs FROM {$table_actions} as_actions LEFT JOIN {$table_logs} as_logs ON as_actions.action_id = as_logs.action_id WHERE as_actions.action_id IN ( " . implode(
				', ',
				array_fill(
					0,
					is_array( $action_ids ) || $action_ids instanceof Countable ? count( $action_ids ) : 0,
					'%s'
				)
			) . ' )';
			$query = call_user_func_array(
				array( $wpdb, 'prepare' ),
				array_merge(
					array( $query ),
					$action_ids
				)
			);
			// SQL is prepared here, so we will ignore WordPress.DB.PreparedSQL.NotPrepared.
			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

			$action_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT action_id FROM {$table_actions} as_actions WHERE as_actions.hook = %s AND as_actions.status = %s LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$hook,
					$status,
					$limit
				)
			);
		}

		return array( 'success' => esc_html__( 'Malware scan logs are cleared', 'wpdef' ) );
	}

	/**
	 * Set the Gather_Fact object.
	 *
	 * @param  Gather_Fact $gather_fact  The Gather_Fact object to set.
	 */
	public function set_gather_fact( Gather_Fact $gather_fact ) {
		if ( class_exists( Gather_Fact::class ) ) {
			$this->gather_fact = $gather_fact;
		}
	}

	/**
	 * Gathers information using the Gather_Fact class.
	 *
	 * @param  Gather_Fact $gather_fact  The Gather_Fact instance.
	 *
	 * @return bool Returns true if information gathering is successful.
	 */
	public function gather_info( Gather_Fact $gather_fact ): bool {
		if ( method_exists( $gather_fact, 'gather_info' ) ) {
			return $gather_fact->gather_info();
		}

		return true;
	}

	/**
	 * Gey intentions.
	 *
	 * @since 4.11.0
	 * @return array
	 */
	public static function get_intentions(): array {
		return array(
			'resolve',
			'ignore',
			'delete',
			'unignore',
			'quarantine',
		);
	}

	/**
	 * Get the list of actioned plugins taking into account the Ignored and Excluded.
	 *
	 * @return array
	 */
	public function gather_actioned_plugin_details(): array {
		$cache = get_site_option( self::PLUGINS_ACTIONED );
		if ( self::are_actioned_plugins( $cache ) ) {
			return $cache;
		}

		$items          = array();
		$is_plugin_used = false;
		if ( $this->settings->integrity_check && $this->settings->check_plugins ) {
			$is_plugin_used = true;
		} elseif ( $this->settings->check_abandoned_plugin ) {
			$is_plugin_used = true;
		} elseif ( $this->is_pro && ( $this->settings->check_known_vuln || $this->settings->scan_malware ) ) {
			$is_plugin_used = true;
		}

		if ( $is_plugin_used ) {
			$model = Scan_Model::get_last();
			/**
			 * Exclude plugin slugs.
			 *
			 * @param  array  $slugs  Slugs of excluded plugins.
			 *
			 * @since 3.1.0
			 */
			$excluded_slugs = (array) apply_filters( 'wd_scan_excluded_plugin_slugs', array() );

			foreach ( $this->get_plugins() as $slug => $item ) {
				if ( is_object( $model ) && $model->is_issue_ignored( $slug ) ) {
					continue;
				}
				$base_slug = $this->get_plugin_slug_by( $slug );
				if ( in_array( $base_slug, $excluded_slugs, true ) ) {
					continue;
				}
				// Use keys with the first capital letter to match default plugin header keys.
				$items[ $base_slug ] = array(
					'Name'    => $item['Name'],
					'Version' => $item['Version'],
					'Slug'    => $slug,
				);
			}

			update_site_option( self::PLUGINS_ACTIONED, $items );
		}

		return $items;
	}

	/**
	 * Does the list of actioned plugins exist and no empty?
	 *
	 * @param array|false $actioned_plugins The actioned plugins.
	 *
	 * @return bool
	 */
	public static function are_actioned_plugins( $actioned_plugins ): bool {
		return is_array( $actioned_plugins ) && array() !== $actioned_plugins;
	}
}