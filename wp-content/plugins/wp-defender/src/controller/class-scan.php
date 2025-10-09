<?php
/**
 * Handles all scan related actions.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use ActionScheduler;
use WP_Defender\Component\Breadcrumbs;
use WP_Defender\Event;
use Valitron\Validator;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Controller\Quarantine;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\Scan_Upsell;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Scan as Model_Scan;
use WP_Defender\Behavior\Scan\Core_Integrity;
use WP_Defender\Component\Scan as Scan_Component;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Helper\Analytics\Scan as Scan_Analytics;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Component\Quarantine as Quarantine_Component;
use WP_Defender\Behavior\Scan\Plugin_Integrity;

/**
 * Contains methods for handling scans.
 */
class Scan extends Event {

	use Formats;
	use Scan_Upsell;

	public const SCAN_LOG = 'scan.log';
	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-scan';

	/**
	 * The model for handling the data.
	 *
	 * @var Scan_Settings
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Scan_Component
	 */
	protected $service;

	/**
	 * Quarantine controller.
	 *
	 * @var Quarantine
	 */
	private $quarantine_controller;

	/**
	 * Indicates whether the current installation is a pro version.
	 *
	 * @var bool
	 */
	private $is_pro;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_page(
			$this->get_title(),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug
		);

		$this->model   = new Scan_Settings();
		$this->service = wd_di()->get( Scan_Component::class );
		$this->is_pro  = wd_di()->get( WPMUDEV::class )->is_pro();

		if ( class_exists( 'WP_Defender\Controller\Quarantine' ) ) {
			$this->quarantine_controller = wd_di()->get( Quarantine::class );
		}

		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_defender_process_scan', array( $this, 'process' ) );
		add_action( 'wp_ajax_nopriv_defender_process_scan', array( $this, 'process' ) );
		add_action( 'defender/async_scan', array( $this, 'process' ) );
		// Clean up data after successful core update.
		add_action( '_core_updated_successfully', array( $this, 'clean_up_data' ) );

		global $pagenow;
		// since 2.6.2.
		if (
			is_admin() &&
			'plugins.php' === $pagenow &&
			apply_filters( 'wd_display_vulnerability_warnings', true ) &&
			$this->is_pro
		) {
			$this->service->display_vulnerability_warnings();
		}

		// Schedule a time to clear completed action scheduler logs.
		if ( ! wp_next_scheduled( 'wpdef_clear_scan_logs' ) ) {
			wp_schedule_event( time(), 'weekly', 'wpdef_clear_scan_logs' );
		}
		add_action( 'wpdef_clear_scan_logs', array( $this, 'clear_scan_logs' ) );

		add_filter( 'heartbeat_nopriv_send', array( $this, 'nopriv_heartbeat' ), 10, 2 );

		add_action(
			'action_scheduler_completed_action',
			array( $this, 'scan_completed_analytics' )
		);
	}

	/**
	 * Return the title of the page.
	 *
	 * @return string The title of the page.
	 */
	public function get_title(): string {
		return esc_html__( 'Malware Scanning', 'wpdef' );
	}

	/**
	 * Clean up data after core updating.
	 *
	 * @return void
	 */
	public function clean_up_data(): void {
		$this->service->clean_up();
	}

	/**
	 * Start a scan.
	 *
	 * @return Response
	 * @defender_route
	 * @defender_redirect
	 */
	public function start(): Response {
		$model = Model_Scan::create();
		if ( is_object( $model ) && ! is_wp_error( $model ) ) {
			$this->log( 'Initial ping self', self::SCAN_LOG );
			$this->service->gather_actioned_plugin_details();

			$this->do_async_scan( 'scan' );

			return new Response(
				true,
				array(
					'status'      => $model->status,
					'status_text' => $model->get_status_text(),
					'percent'     => 0,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'A scan is already in progress', 'wpdef' ),
			)
		);
	}

	/**
	 * Use this for self ping, so it can both run in background and active mode with good performance.
	 *
	 * @return void
	 * @defender_route
	 * @is_public
	 */
	public function process() {
		if ( $this->service->has_lock() ) {
			$this->log( 'Fallback as already a process is running', self::SCAN_LOG );

			return;
		}

		// This creates file lock, for make sure only 1 process run as a time.
		$this->service->create_lock();
		// Check if the ping is from self or not.
		$ret = $this->service->process();
		$this->log( 'process done, queue for next', self::SCAN_LOG );
		if ( false === $ret ) {
			// Ping self.
			$this->log( 'Scan not done, pinging', self::SCAN_LOG );
			$this->service->remove_lock();
			$this->process();
		} else {
			$this->queue_to_sync_with_hub();
			$this->service->remove_lock();
		}
	}

	/**
	 * Query status.
	 *
	 * @return Response
	 * @defender_route
	 * @defender_redirect
	 */
	public function status(): Response {
		$idle_scan = wd_di()->get( Model_Scan::class )->get_idle();

		if ( is_object( $idle_scan ) ) {
			$this->service->update_idle_scan_status();

			return new Response( false, $idle_scan->to_array() );
		}

		$checksum_issue = get_site_option( Core_Integrity::ISSUE_CHECKSUMS, 'false' );
		$checksum_scan  = Model_Scan::get_core_check();
		if ( 'false' !== $checksum_issue && is_object( $checksum_scan ) ) {
			$this->service->update_idle_scan_status_by_checksum_issue( $checksum_scan );

			return new Response( false, $checksum_scan->to_array() );
		}

		$scan = Model_Scan::get_active();
		if ( is_object( $scan ) ) {

			return new Response( false, $scan->to_array() );
		}
		$scan = Model_Scan::get_last();
		if ( is_object( $scan ) && ! is_wp_error( $scan ) ) {

			return new Response( true, $scan->to_array() );
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'Error during scanning', 'wpdef' ),
			)
		);
	}

	/**
	 * Cancel current scan.
	 *
	 * @return Response
	 * @defender_route
	 * @defender_redirect
	 */
	public function cancel(): Response {
		$component = wd_di()->get( Scan_Component::class );
		$component->cancel_a_scan();
		$last = Model_Scan::get_last();
		if ( is_object( $last ) && ! is_wp_error( $last ) ) {
			$last = $last->to_array();
		}

		return new Response(
			true,
			array(
				'scan' => $last,
			)
		);
	}

	/**
	 * Track scan item action analytics.
	 *
	 * @param  Scan_Item $scan_item  Individual item of scan issues list.
	 * @param  string    $intention  What action is going to be executed.
	 */
	private function item_action_analytics( Scan_Item $scan_item, string $intention ) {
		$allowed_intentions = Scan_Component::get_intentions();

		$event_name = 'def_threat_resolved';

		if ( in_array( $intention, $allowed_intentions, true ) ) {
			$intention_desc = array(
				'resolve'    => 'Safe Repair',
				'ignore'     => 'Ignore',
				'delete'     => 'Delete',
				'unignore'   => 'Unignore',
				'quarantine' => 'Safe Repair & Quarantine',
			);

			$resolution_method = $intention_desc[ $intention ];
			$threat_type       = '';

			if ( Scan_Item::TYPE_INTEGRITY === $scan_item->type ) {
				// Track Repair-actions.
				if ( in_array( $intention, array( 'resolve', 'quarantine' ), true ) ) {
					$threat_type = 'core file modified';
				} else {
					$threat_type = 'Unknown file in WordPress core';
				}
			} elseif ( Scan_Item::TYPE_PLUGIN_CHECK === $scan_item->type ) {
				$raw_data = $scan_item->raw_data;

				if ( isset( $raw_data['type'] ) && 'modified' === $raw_data['type'] ) {
					$threat_type = 'plugin file modified';
				}
			} elseif ( Scan_Item::TYPE_VULNERABILITY === $scan_item->type ) {
				$threat_type = 'Vulnerability';

				if ( 'resolve' === $intention ) {
					$resolution_method = 'Update';
				}
			} elseif ( Scan_Item::TYPE_SUSPICIOUS === $scan_item->type ) {
				$threat_type = 'Suspicious function';
			} elseif (
				in_array(
					$scan_item->type,
					Model_Scan::get_abandoned_types(),
					true
				)
			) {
				$threat_type = 'Outdated & removed plugins';
			}

			$this->track_feature(
				$event_name,
				array(
					'Resolution Method' => $resolution_method,
					'Threat type'       => $threat_type,
				)
			);
		}
	}

	/**
	 * A central controller to pass any request from frontend to scan item.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function item_action( Request $request ): Response {
		$data      = $request->get_data(
			array(
				'id'            => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'intention'     => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'parent_action' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$id        = $data['id'] ?? false;
		$intention = $data['intention'] ?? false;
		// Get allowed intentions.
		$allowed_intentions   = Scan_Component::get_intentions();
		$allowed_intentions[] = 'pull_src';
		if ( false === $id || false === $intention || ! in_array(
			$intention,
			$allowed_intentions,
			true
		) ) {
			wp_die();
		}

		$scan = Model_Scan::get_last();
		if ( $scan instanceof Model_Scan ) {
			$item = $scan->get_issue( $id );
			if ( is_object( $item ) && $item->has_method( $intention ) ) {

				if ( 'quarantine' === $intention ) {
					$result = $item->$intention( $data['parent_action'] );
				} else {
					$result = $item->$intention();
				}
				// Maybe track.
				if ( $this->is_tracking_active() ) {
					$this->item_action_analytics( $item, $intention );
				}

				if ( is_wp_error( $result ) ) {
					return new Response(
						false,
						array(
							'message' => $result->get_error_message(),
						)
					);
				} elseif ( isset( $result['type_notice'] ) ) {
					return new Response(
						true,
						$result
					);
				} elseif ( isset( $result['url'] ) ) {
					// Without message and interval args.
					return new Response(
						true,
						array( 'redirect' => $result['url'] )
					);
				}

				$this->queue_to_sync_with_hub();

				// Refresh scan instance.
				$scan = Model_Scan::get_last();

				if ( $scan instanceof Model_Scan ) {
					$result['scan'] = $scan->to_array();

					$success = true;
					if ( isset( $result['success'] ) && false === $result['success'] ) {
						$success = false;
					}

					return new Response( $success, $result );
				}
			}
		}

		return new Response( false, array() );
	}

	/**
	 * Process for bulk action.
	 * There is no Update-intention because it is a lengthy process. There may not be enough execution time.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function bulk_action( Request $request ): Response {
		$data      = $request->get_data(
			array(
				'items' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
				'bulk'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$items     = $data['items'] ?? array();
		$intention = $data['bulk'] ?? false;

		if (
			empty( $items )
			|| ! is_array( $items )
			|| ! in_array( $intention, array( 'ignore', 'unignore', 'delete' ), true )
		) {
			return new Response( false, array() );
		}
		// Try to get Scan.
		$scan = Model_Scan::get_last();
		if ( ! is_object( $scan ) ) {
			return new Response( false, array() );
		}

		$is_delete         = false;
		$delete_items      = array();
		$none_delete_items = array();
		$sync_hub          = false;
		foreach ( $items as $id ) {
			if ( 'ignore' === $intention ) {
				$scan->ignore_issue( (int) $id );
				$sync_hub = true;
			} elseif ( 'unignore' === $intention ) {
				$scan->unignore_issue( (int) $id );
				$sync_hub = true;
			} elseif ( 'delete' === $intention ) {
				$item = $scan->get_issue( (int) $id );
				// Work with every item.
				if ( is_object( $item ) && $item->has_method( $intention ) ) {
					$item_result = $item->delete();
					if ( is_wp_error( $item_result ) ) {
						$none_delete_items[] = $item_result->get_error_message();
					} elseif ( isset( $item_result['type_notice'] ) ) {
						return new Response( true, $item_result );
					} elseif ( isset( $item_result['collect_type'] ) ) {
						$is_delete      = true;
						$delete_items[] = $item_result['message'];
					}
					// If there is any error, no need to sync data.
					$sync_hub = true;
				}
			}
		}

		if ( $sync_hub ) {
			$this->queue_to_sync_with_hub();
		}

		$result = array();
		if ( ! empty( $none_delete_items ) ) {
			$result['message'] = sprintf(
			/* translators: %s: Vulnerability item(es) */
				_n(
					'Defender doesn\'t have enough permission to remove this file: %s',
					'Defender doesn\'t have enough permission to remove these files: %s',
					count( $none_delete_items ),
					'wpdef'
				),
				'<pre>' . implode( PHP_EOL, $none_delete_items ) . '</pre>'
			);
		} elseif ( $is_delete ) {
			$result['message'] = sprintf(
			/* translators: %s: Vulnerability item(es) */
				esc_html__( '%s has (have) been deleted', 'wpdef' ),
				implode( ', ', $delete_items )
			);
		}
		// Refresh scan instance.
		$scan           = Model_Scan::get_last();
		$result['scan'] = $scan->to_array();

		return new Response( empty( $none_delete_items ), $result );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @since 2.7.0 Add Scheduled Scanning to Malware settings and hide it on Malware Scanning - Reporting.
	 * Also, the backward compatibility of settings for Scan and Malware_Report models.
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$data = $request->get_data_by_model( $this->model );
		// Case#1: enable all child options, if parent and all child options are disabled, so that there is no notice when saving.
		if (
			! $data['integrity_check']
			&& ! $data['check_core']
			&& ! $data['check_plugins']
		) {
			$data['check_core']    = true;
			$data['check_plugins'] = true;
		}

		// Case#2: Suspicious code is activated BUT File change detection is deactivated then show the notice.
		if ( $data['scan_malware'] && ! $data['integrity_check'] ) {
			$response = array(
				'type_notice' => 'info',
				'message'     => sprintf(
					/* translators: 1. Open tag. 2. Close tag. 3. Open tag. 4. Close tag. */
					esc_html__(
						'To reduce false-positive results, we recommend enabling %1$sFile change detection%2$s options for all scan types while the %3$sSuspicious code%4$s option is enabled.',
						'wpdef'
					),
					'<strong>',
					'</strong>',
					'<strong>',
					'</strong>'
				),
			);
		} else {
			// Prepare response message for usual successful case.
			$response = array(
				'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
				'auto_close' => true,
			);
		}
		// Additional cases are in the Scan model.
		$report_change = false;
		// If 'Scheduled Scanning' is checked then need to change Malware_Report.
		if ( true === $data['scheduled_scanning'] ) {
			$report            = new Malware_Report();
			$report_change     = true;
			$report->frequency = $data['frequency'];
			$report->day       = $data['day'];
			$report->day_n     = (int) $data['day_n'];
			$report->time      = $data['time'];
			// Disable 'Scheduled Scanning'.
		} elseif ( true === $this->model->scheduled_scanning && false === $data['scheduled_scanning'] ) {
			$report         = new Malware_Report();
			$report_change  = true;
			$report->status = \WP_Defender\Model\Notification::STATUS_DISABLED;
		}

		$before_import_schedule = $this->model->quarantine_expire_schedule;

		$this->model->import( $data );
		if ( $this->model->validate() ) {

			if ( class_exists( 'WP_Defender\Component\Quarantine' ) ) {
				$quarantine_component = wd_di()->get( Quarantine_Component::class );
				$quarantine_component->reschedule_file_expiry_cron(
					$before_import_schedule,
					$data['quarantine_expire_schedule']
				);
			}

			// Todo: need to disable Malware_Notification & Malware_Report if all scan settings are deactivated?
			$this->model->save();
			// Save Report's changes.
			if ( $report_change ) {
				$report->save();
			}
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge( $response, $this->data_frontend() )
			);
		} else {
			return new Response(
				false,
				array_merge(
					array(
						'message' => $this->model->get_formatted_errors(),
					),
					$this->data_frontend()
				)
			);
		}
	}

	/**
	 * Get the issues mainly for pagination request.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function get_issues( Request $request ): Response {
		$data = $request->get_data(
			array(
				'scenario' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'type'     => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'per_page' => array(
					'type'     => 'string',
					'sanitize' => 'intval',
				),
				'paged'    => array(
					'type'     => 'int',
					'sanitize' => 'intval',
				),
			)
		);

		// Validate the request.
		$v = new Validator( $data, array() );
		$v->rule( 'required', array( 'scenario', 'type', 'per_page', 'paged' ) );
		if ( ! $v->validate() ) {
			return new Response(
				false,
				array(
					'message' => '',
				)
			);
		}

		$scan   = Model_Scan::get_last();
		$issues = $scan->to_array( $data['per_page'], $data['paged'], $data['type'] );

		return new Response(
			true,
			array(
				'issue'   => $issues['issues_items'],
				'ignored' => $issues['ignored_items'],
				'paging'  => $issues['paging'],
				'count'   => $issues['count'],
			)
		);
	}

	/**
	 * Update the run background state for active scan.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response Response object.
	 * @defender_route
	 */
	public function update_background( Request $request ): Response {
		$data = $request->get_data(
			array(
				'run_background' => array(
					'type'     => 'boolean',
					'sanitize' => 'rest_sanitize_boolean',
				),
			)
		);

		$run_background = $data['run_background'] ?? false;
		$scan           = Model_Scan::get_active();

		if ( is_object( $scan ) && $run_background ) {
			set_site_transient( 'defender_run_background', $scan->id, HOUR_IN_SECONDS * 2 );

			return new Response(
				true,
				array(
					'scan' => $scan->to_array(),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'No active scan found', 'wpdef' ),
			)
		);
	}

	/**
	 * Render main page.
	 *
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-scan', 'scan', $this->data_frontend() );
		wp_enqueue_script( 'def-scan' );
		wp_enqueue_script( 'clipboard' );
		$this->enqueue_main_assets();
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		$scan = Model_Scan::get_active();
		$last = Model_Scan::get_last();
		if ( ! is_object( $scan ) && ! is_object( $last ) ) {
			$scan = null;
		} else {
			$scan = is_object( $scan ) ? $scan->to_array() : $last->to_array();
		}

		return array_merge(
			array(
				'scan'   => $scan,
				'report' => array(
					'enabled'   => true,
					'frequency' => 'weekly',
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
		( new Scan_Settings() )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		delete_site_option( Model_Scan::IGNORE_INDEXER );
		delete_site_option( Core_Integrity::ISSUE_CHECKSUMS );
		delete_site_transient( Plugin_Integrity::$org_slugs );
		delete_site_transient( Plugin_Integrity::$org_responses );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$scan     = Model_Scan::get_active();
		$last     = Model_Scan::get_last();
		$per_page = 10;
		$paged    = 1;
		if ( ! is_object( $scan ) && ! is_object( $last ) ) {
			$scan = null;
		} else {
			$scan = is_object( $scan ) ? $scan->to_array( $per_page, $paged ) : $last->to_array( $per_page, $paged );
		}
		$settings    = new Scan_Settings();
		$report      = wd_di()->get( Malware_Report::class );
		$report_text = esc_html__( 'Automatic scans are disabled', 'wpdef' );
		if ( $settings->scheduled_scanning && isset( $settings->frequency ) ) {
			$report_text = sprintf(
			/* translators: 1. Line break tag. 2. Frequency value. */
				esc_html__( 'Automatic scans are %1$srunning %2$s', 'wpdef' ),
				'<br/>',
				$settings->frequency
			);
		}

		$misc['outdated_period'] = \WP_Defender\Behavior\Scan\Abandoned_Plugin::get_outdated_period();
		$misc['labels']          = $settings->labels();
		$misc['days_of_week']    = $this->get_days_of_week();
		$misc['times_of_day']    = $this->get_times();
		$misc['timezone_text']   = sprintf(
			/* translators: 1. Timezone. 2. Time. */
			esc_html__(
				'Your timezone is set to %1$s, so your current time is %2$s.',
				'wpdef'
			),
			'<strong>' . wp_timezone_string() . '</strong>',
			'<strong>' . wp_date( 'H:i', time() ) . '</strong>'
		);

		// Todo: add logic for deactivated scan settings. Maybe display some notice.
		$data = array(
			'scan'         => $scan,
			'settings'     => $settings->export(),
			'report'       => $report_text,
			'active_tools' => array(
				'integrity_check'        => $settings->integrity_check,
				'check_known_vuln'       => $settings->check_known_vuln,
				'scan_malware'           => $settings->scan_malware,
				'scheduled_scanning'     => $settings->scheduled_scanning,
				'check_abandoned_plugin' => $settings->check_abandoned_plugin,
			),
			'notification' => $report->to_string(),
			'next_run'     => $report->get_next_run_as_string(),
			'misc'         => $misc,
			'upsell'       => array(
				'scan' => $this->get_scan_upsell( 'scan' ),
			),
		);

		if ( class_exists( 'WP_Defender\Controller\Quarantine' ) ) {
			$data['quarantine'] = $this->quarantine_controller->data_frontend();
		}

		return array_merge( $data, $this->dump_routes_and_nonces() );
	}

	/**
	 * Imports data into the model.
	 *
	 * @param array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$model = $this->model;
		if ( empty( $data ) ) {
			$model->scheduled_scanning = false;
			$model->frequency          = 'weekly';
			$model->day_n              = 1;
			$model->day                = 'sunday';
			$model->time               = '4:00';
			$model->save();
		} else {
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
		}
	}

	/**
	 * Checks if any scan is active.
	 *
	 * @param  bool $is_pro  Indicates if the product is a pro version.
	 *
	 * @return bool True if any scan is active, false otherwise.
	 */
	private function is_any_active( bool $is_pro ): bool {
		$settings          = new Scan_Settings();
		$file_change_check = $settings->is_checked_any_file_change_types();

		if ( $is_pro ) {
			// Pro version. Check all parent types.
			return $file_change_check || $settings->check_known_vuln || $settings->scan_malware;
		} else {
			// Free version:
			// Check the 'File change detection' type because only it's available with nested types.
			// Check the Abandoned plugin type.
			return $file_change_check || $settings->check_abandoned_plugin;
		}
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		$strings = array();
		if ( $this->is_any_active( $this->is_pro ) ) {
			$strings[] = esc_html__( 'Active', 'wpdef' );
		} else {
			$strings[] = esc_html__( 'Inactive', 'wpdef' );
		}

		$scan_report       = new Malware_Report();
		$scan_notification = new Malware_Notification();
		if ( 'enabled' === $scan_notification->status ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}
		if ( $this->is_pro && 'enabled' === $scan_report->status ) {
			$strings[] = sprintf(
			/* translators: %s: Frequency value. */
				esc_html__( 'Email reports sending %s', 'wpdef' ),
				$scan_report->frequency
			);
		} elseif ( ! $this->is_pro ) {
			$strings[] = sprintf(
			/* translators: %s: Html for Pro-tag. */
				esc_html__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}

	/**
	 * Generates configuration strings based on the provided configuration and
	 * whether the product is a pro version.
	 *
	 * @param  array $config  Configuration data.
	 * @param  bool  $is_pro  Indicates if the product is a pro version.
	 *
	 * @return array Returns an array of configuration strings.
	 */
	public function config_strings( array $config, bool $is_pro ): array {
		$strings   = array();
		$strings[] = $this->service->is_any_scan_active( $config )
			? esc_html__( 'Active', 'wpdef' )
			: esc_html__( 'Inactive', 'wpdef' );

		if ( 'enabled' === $config['notification'] ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}
		if ( $is_pro && 'enabled' === $config['report'] ) {
			$strings[] = sprintf(
			/* translators: %s: Frequency value. */
				esc_html__( 'Email reports sending %s', 'wpdef' ),
				$config['frequency']
			);
		} elseif ( ! $is_pro ) {
			$strings[] = sprintf(
			/* translators: %s: Html for Pro-tag. */
				esc_html__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}

	/**
	 * Triggers the asynchronous scan.
	 *
	 * @param string $type Denotes type of the scan from the following 4 possible values: scan, install, hub or report.
	 *
	 * @return void
	 */
	public function do_async_scan( string $type ): void {
		wd_di()->get( Model_Scan::class )->delete_idle();
		// Delete the slug from the previous scan.
		delete_site_option( Core_Integrity::ISSUE_CHECKSUMS );

		as_enqueue_async_action(
			'defender/async_scan',
			array(
				'type' => $type,
			),
			'defender'
		);
	}

	/**
	 * Clear completed action scheduler logs.
	 *
	 * @return void
	 * @since 2.6.5
	 */
	public function clear_scan_logs(): void {
		$scan_component = wd_di()->get( Scan_Component::class );
		$result         = $scan_component::clear_logs();

		if ( isset( $result['error'] ) ) {
			$this->log( 'WP CRON Error : ' . $result['error'], self::SCAN_LOG );
		}
	}

	/**
	 * When user session is expired and scan is running, then don't login via heartbeat modal.
	 *
	 * @param  array  $response  The no-priv Heartbeat response.
	 * @param  string $screen_id  The screen id.
	 *
	 * @return mixed
	 * @since 3.11.0
	 */
	public function nopriv_heartbeat( $response, $screen_id ) {
		if ( false !== strpos( $screen_id, $this->slug ) ) {
			$scan = Model_Scan::get_active();

			if ( is_object( $scan ) ) {
				$response['wp-auth-check'] = true;
			}
		}

		return $response;
	}

	/**
	 * Triggers and send analytics data on scan completed.
	 *
	 * @param  int $action_id  Action ID.
	 *
	 * @return void
	 */
	public function scan_completed_analytics( $action_id ) {
		if ( 'defender' === ActionScheduler::store()->fetch_action( $action_id )->get_group() ) {
			$scan_analytics = wd_di()->get( Scan_Analytics::class );

			$scan_model     = wd_di()->get( Model_Scan::class );
			$analytics_data = $scan_analytics->scan_completed( $scan_model );
			if ( empty( $analytics_data ) ) {
				return;
			}

			$this->track_feature(
				$analytics_data['event'],
				$analytics_data['data']
			);
		}
	}
}