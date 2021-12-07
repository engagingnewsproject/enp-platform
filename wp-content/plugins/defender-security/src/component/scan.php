<?php

namespace WP_Defender\Component;

use WP_Defender\Behavior\Scan\Core_Integrity;
use WP_Defender\Behavior\Scan\Gather_Fact;
use WP_Defender\Behavior\Scan\Known_Vulnerability;
use WP_Defender\Behavior\Scan\Malware_Scan;
use WP_Defender\Behavior\Scan\Plugin_Integrity;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Scan as Model_Scan;

class Scan extends Component {

	/**
	 * Cache the current scan.
	 *
	 * @var \WP_Defender\Model\Scan
	 */
	public $scan;

	/**
	 * @var \WP_Defender\Model\Setting\Scan
	 */
	public $settings;

	/**
	 * @var array
	 */
	protected $vulnerability_details = array();

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->attach_behavior( Gather_Fact::class, Gather_Fact::class );
		$this->attach_behavior( Core_Integrity::class, Core_Integrity::class );
		$this->attach_behavior( Plugin_Integrity::class, Plugin_Integrity::class );
		if ( class_exists( Known_Vulnerability::class ) ) {
			$this->attach_behavior( Known_Vulnerability::class, new Known_Vulnerability() );
		}
		if ( class_exists( Malware_Scan::class ) ) {
			$this->attach_behavior( Malware_Scan::class, new Malware_Scan() );
		}
	}

	/**
	 * @param object $model
	 */
	public function advanced_scan_actions( $model ) {
		$this->reindex_ignored_issues( $model );
		$this->clean_up();
	}

	/**
	 * Process current scan.
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function process() {
		$scan = Model_Scan::get_active();
		if ( ! is_object( $scan ) ) {
			// This case can be a scan get cancel.
			return - 1;
		}
		$this->scan     = $scan;
		$this->settings = new \WP_Defender\Model\Setting\Scan();
		$tasks          = $this->get_tasks();
		$runner         = new \ArrayIterator( $tasks );
		$task           = $this->scan->status;
		if ( Model_Scan::STATUS_INIT === $scan->status ) {
			// Get the first.
			$this->log( 'Prepare facts for a scan', 'scan.log' );
			$task                    = 'gather_fact';
			$this->scan->percent     = 0;
			$this->scan->total_tasks = $runner->count();
			$this->scan->save();
		}
		if (
			in_array(
				$this->scan->status,
				array(
					Model_Scan::STATUS_ERROR,
					Model_Scan::STATUS_IDLE,
				),
				true
			)
		) {
			// Stop and return true to abort the process.
			return true;
		}
		// Find the current task.
		$offset = array_search( $task, array_values( $tasks ) );
		if ( false === $offset ) {
			$this->log( sprintf( 'offset is not found, search %s', $task ), 'scan.log' );

			return false;
		}
		// Reset the tasks to current.
		$runner->seek( $offset );
		$this->log( sprintf( 'Current task %s', $runner->current() ), 'scan.log' );
		if ( $this->has_method( $task ) ) {
			$this->log( sprintf( 'processing %s', $runner->key() ), 'scan.log' );
			$result = $this->$task();
			if ( true === $result ) {
				$this->log( sprintf( 'task %s processed', $runner->key() ), 'scan.log' );
				// Task is done, move to next.
				$runner->next();
				if ( $runner->valid() ) {
					$this->log( sprintf( 'queue %s for next', $runner->key() ), 'scan.log' );
					$this->scan->status          = $runner->key();
					$this->scan->task_checkpoint = '';
					$this->scan->date_end        = gmdate( 'Y-m-d H:i:s' );
					$this->scan->save();
					// Queue for next run.
					return false;
				}
				$this->log( 'All done!', 'scan.log' );
				// No more task in the queue, we are done.
				$this->scan->status = Model_Scan::STATUS_FINISH;
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
	 * @param \WP_Defender\Model\Scan $model
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
	public function get_tasks() {
		$tasks = array(
			'gather_fact' => 'gather_fact',
		);
		if ( $this->settings->integrity_check ) {
			/**
			 * @since 2.4.7
			*/
			if ( $this->settings->check_core ) {
				$tasks['core_integrity_check'] = 'core_integrity_check';
			}
			if ( $this->settings->check_plugins ) {
				$tasks['plugin_integrity_check'] = 'plugin_integrity_check';
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
		$scan = Model_Scan::get_active();
		if ( is_object( $scan ) ) {
			$scan->delete();
		}
		$this->clean_up();
		$this->remove_lock();
	}

	/**
	 * Clean up data generate by current scan.
	 */
	public function clean_up() {
		$this->delete_interim_data();

		$models = Model_Scan::get_last_all();
		if ( ! empty( $models ) ) {
			// Remove the latest. Don't remove code to find the first value.
			$current = array_shift( $models );
			foreach ( $models as $model ) {
				$model->delete();
			}
		}
	}

	/**
	 * Create a file lock, so we can check if a process already running.
	 */
	public function create_lock() {
		file_put_contents( $this->get_lock_path(), time(), LOCK_EX );
	}

	/**
	 * Delete file lock.
	 */
	public function remove_lock() {
		@unlink( $this->get_lock_path() );
	}

	/**
	 * Check if a lock is valid.
	 *
	 * @return bool
	 */
	public function has_lock() {
		if ( ! file_exists( $this->get_lock_path() ) ) {
			return false;
		}
		$time = file_get_contents( $this->get_lock_path() );
		if ( strtotime( '+90 seconds', $time ) < time() ) {
			// Usually a timeout window is 30 seconds, so we should allow lock at 1.30min for safe.
			return false;
		}

		return true;
	}

	/**
	 * Get the total scanning active issues.
	 *
	 * @return integer $count
	 */
	public function indicator_issue_count() {
		$count = 0;
		$scan  = Model_Scan::get_last();
		if ( is_object( $scan ) && ! is_wp_error( $scan ) ) {
			// Only Scan issues.
			$count = count( $scan->get_issues( null, \WP_Defender\Model\Scan_Item::STATUS_ACTIVE ) );
		}

		return $count;
	}

	/**
	 * @param array $scan_settings
	 * @param bool  $is_pro
	 *
	 * @return bool
	 */
	public function is_any_scan_active( $scan_settings, $is_pro ) {
		if ( empty( $scan_settings['integrity_check'] ) ) {
			$integrity_check = false;
		} elseif (
			! empty( $scan_settings['integrity_check'] )
			&& empty( $scan_settings['check_core'] )
			&& empty( $scan_settings['check_plugins'] )
		) {
			$integrity_check = false;
		} else {
			$integrity_check = true;
		}

		if ( ! $integrity_check && ! $is_pro ) {

			return false;
		} elseif (
			! $integrity_check
			&& empty( $scan_settings['check_known_vuln'] )
			&& empty( $scan_settings['scan_malware'] )
			&& ! $is_pro
		) {

			return false;
		}

		return true;
	}

	/**
	 * Update the idle scan status.
	 *
	 * @since 2.6.1
	 */
	public function update_idle_scan_status() {
		$idle_scan = wd_di()->get( Model_Scan::class )->get_idle();

		if ( is_object( $idle_scan ) ) {
			$ready_to_send = false;
			if ( Model_Scan::STATUS_IDLE === $idle_scan->status ) {
				$ready_to_send = true;
			}
			$this->delete_interim_data();

			as_unschedule_all_actions( 'defender/async_scan' );

			$idle_scan->status = Model_Scan::STATUS_IDLE;
			$idle_scan->save();

			$this->remove_lock();
			if ( $ready_to_send ) {
				do_action( 'defender_notify', 'malware-notification', $idle_scan );
			}
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
	 * @param string $file        Plugin basename.
	 * @param array  $plugin_data Plugin information.
	 *
	 * @return void
	 */
	public function attach_plugin_vulnerability_warning( $file, $plugin_data ) {
		/** @var WP_Plugins_List_Table $wp_list_table */
		$wp_list_table = _get_list_table(
			'WP_Plugins_List_Table',
			array(
				'screen' => get_current_screen(),
			)
		);
		$bugs          = $this->vulnerability_details[ $file ]['bugs'];
		if ( empty( $bugs ) ) {

			return;
		}
		// Check if there have been updates since the last scan.
		$exist_update = true;
		if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] ) ) {
			// The current plugin version.
			$current_version = $plugin_data['Version'];
			foreach ( $bugs as $bug_details ) {
				if( version_compare( $bug_details['fixed_in'], $current_version, '>' ) ) {
					$exist_update = false;
					break;
				}
			}
		}
		// If there were updates, do not display notice.
		if ( $exist_update ) {

			return;
		}
		// Sometimes $plugin_data['slug'] is empty.
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
				'<tr class="plugin-update-tr%s" id="vulnerability-%s" data-slug="%s" data-plugin="%s">' .
				'<td colspan="%s" class="plugin-update colspanchange plugin-vulnerability">' .
				'<div class="update-message notice inline %s notice-alt"><p>',
				$active_class,
				esc_attr( $plugin_data['slug'] ),
				esc_attr( $plugin_data['slug'] ),
				esc_attr( $file ),
				esc_attr( $wp_list_table->get_column_count() ),
				'notice-error'
			);

			$notice = sprintf(
			/* translators: %s - Plugin name. */
				__( '%s has detected a vulnerability in this plugin that may cause harm to your site.', 'wpdef' ),
				'<b>' . __( 'Defender Pro', 'wpdef' ) . '</b>'
			);
			if ( count( $bugs ) > 1 ) {
				$notice  .= '<hr/>';
				$fixed_in = '';
				$lines    = array();
				foreach ( $bugs as $bug ) {
					$lines[]  = '<span class="vulnerability-indent"></span>' . $bug['title'];
					$fixed_in = $bug['fixed_in'];
				}
				$notice .= implode( '<br/>', $lines );
				$notice .= '<hr/><span class="vulnerability-indent"></span>';
				if ( ! empty( $fixed_in ) ) {
					$notice .= sprintf(
					/* translators: %s - Version number. */
						__( 'The vulnerability has been fixed in version %s. We recommend that you update this plugin accordingly.', 'wpdef' ),
						$fixed_in
					);
				} else {
					$notice .= __( 'Important! We recommend that you deactivate this plugin until the vulnerability has been fixed.', 'wpdef' );
				}
			} else {
				$notice .= '<br/><span class="vulnerability-indent"></span>' . $bugs[0]['title'] . '<br/><span class="vulnerability-indent"></span>';
				$notice .= empty( $bugs[0]['fixed_in'] )
					? __( 'We recommend that you deactivate this plugin until the vulnerability has been fixed.', 'wpdef' )
					: sprintf(
					/* translators: 1: Version number. */
						__( 'The vulnerability has been fixed in version %s. We recommend that you update this plugin accordingly.', 'wpdef' ),
						$bugs[0]['fixed_in']
					);
			}

			printf( $notice );
		}
	}

	/**
	 * Display warnings.
	 * @since 2.6.2
	*/
	public function display_vulnerability_warnings() {
		if ( ! current_user_can( 'update_plugins' ) ) {

			return;
		}

		$last = \WP_Defender\Model\Scan::get_last();
		if ( is_object( $last ) && ! is_wp_error( $last ) ) {
			$vulnerability_issues = $last->get_issues( \WP_Defender\Model\Scan_Item::TYPE_VULNERABILITY );
			if ( empty( $vulnerability_issues ) ) {

				return;
			}

			add_action( 'admin_print_styles-plugins.php', array( $this, 'show_plugin_admin_styles' ) );
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

		$table_count   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(*)
				 FROM information_schema.tables
				 WHERE table_schema = %s AND table_name IN (%s, %s);",
				$wpdb->dbname,
				$table_actions,
				$table_logs
			)
		);

		if ( 2 !== $table_count ) {
			return array(
				'error' => __( 'Action scheduler is not setup', 'wpdef' ),
			);
		}

		$hook   = 'defender/async_scan';
		$status = 'complete';
		$limit  = 100;
		while ( $action_ids = $wpdb->get_col( $wpdb->prepare( "SELECT action_id FROM {$table_actions} as_actions WHERE as_actions.hook = %s AND as_actions.status = %s LIMIT %d", $hook, $status, $limit ) ) ) {
			if ( empty( $action_ids ) ) {
				break;
			}

			$where_in = implode( ', ', array_fill( 0, count( $action_ids ), '%s' ) );
			$r = $wpdb->query(
				$wpdb->prepare(
					"DELETE as_actions, as_logs
					 FROM {$table_actions} as_actions
					 LEFT JOIN {$table_logs} as_logs
						ON as_actions.action_id = as_logs.action_id
					 WHERE as_actions.action_id IN ( {$where_in} )",
					$action_ids
				)
			);
		}

		return array(
			'success' => __( 'Malware scan logs are cleared', 'wpdef' ),
		);
	}
}
