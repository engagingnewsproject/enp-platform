<?php
/**
 * Handles audit logging functionalities .
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use DateTime;
use Exception;
use DateInterval;
use WP_Defender\Event;
use Calotes\Helper\HTTP;
use WP_Defender\Traits\User;
use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use WP_Defender\Traits\Formats;
use WP_Defender\Component\Audit;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Setting\Audit_Logging as Model_Audit_Logging;

/**
 * Handles audit logging functionalities .
 */
class Audit_Logging extends Event {

	use User;
	use Formats;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-logging';

	/**
	 * The model for handling the data.
	 *
	 * @var Model_Audit_Logging
	 */
	public $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Audit|null
	 */
	public ?Audit $service;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_page(
			esc_html( Model_Audit_Logging::get_module_name() ),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug
		);
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( Model_Audit_Logging::class );
		$this->service = new Audit();
		$this->register_routes();
		if ( $this->model->is_active() ) {
			$this->service->enqueue_event_listener();
			add_action( 'shutdown', array( $this, 'cache_audit_logs' ) );

			/**
			 * Network Cron Manager
			 *
			 * @var Network_Cron_Manager $network_cron_manager
			 */
			$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
			$network_cron_manager->register_callback(
				'audit_clean_up_logs',
				array( $this->service, 'audit_clean_up_logs' ),
				HOUR_IN_SECONDS
			);
			$network_cron_manager->register_callback(
				'audit_sync_events',
				array( $this->service, 'flush' ),
				HOUR_IN_SECONDS,
				time() + 15
			);
		}
	}

	/**
	 * Exports audit logs as a CSV file.
	 *
	 * @return void
	 * @throws Exception If there is an error during export.
	 * @defender_route
	 */
	public function export_as_csv(): void {
		$date_from = HTTP::get(
			'date_from',
			wp_date( 'Y-m-d H:i:s', strtotime( '-7 days', time() ) )
		);
		$date_to   = HTTP::get( 'date_to', wp_date( 'Y-m-d H:i:s', time() ) );
		// Convert date using timezone.
		$timezone  = wp_timezone();
		$date_from = ( new DateTime( $date_from, $timezone ) )->setTime( 0, 0, 0 )->getTimestamp();
		$date_to   = ( new DateTime( $date_to, $timezone ) )->setTime( 23, 59, 59 )->getTimestamp();
		$username  = HTTP::get( 'term', '' );
		$user_id   = '';
		$user      = get_user_by( 'login', $username );
		$events    = HTTP::get( 'event_type', array() );
		if ( is_object( $user ) ) {
			$user_id = $user->ID;
		}

		$handler    = new Audit();
		$ip_address = HTTP::get( 'ip_address', '' );
		$result     = $handler->fetch( $date_from, $date_to, $events, $user_id, $ip_address, false );
		// WP_Filesystem class doesn’t directly provide a function for opening a stream to php://memory with the 'w' mode.
		$fp      = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$headers = array(
			esc_html__( 'Summary', 'wpdef' ),
			esc_html__( 'Date / Time', 'wpdef' ),
			esc_html__( 'Context', 'wpdef' ),
			esc_html__( 'Type', 'wpdef' ),
			esc_html__( 'IP address', 'wpdef' ),
			esc_html__( 'User', 'wpdef' ),
		);
		fputcsv( $fp, $headers, ',', '"', '\\' );
		foreach ( $result as $log ) {
			$fields = $log->export();
			$vars   = array(
				$fields['msg'],
				is_array( $fields['timestamp'] )
				? $this->format_date_time( $fields['timestamp'][0] )
				: $this->format_date_time( $fields['timestamp'] ),
				$fields['context'],
				$fields['action_type'],
				$fields['ip'],
				$this->get_user_display( $fields['user_id'] ),
			);
			fputcsv( $fp, $vars, ',', '"', '\\' );
		}
		$filename = 'wdf-audit-logs-export-' . wp_date( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// Make php send the generated csv lines to the browser.
		fpassthru( $fp );
		exit();
	}

	/**
	 * We'll pass all the event logs into the db handler, so it writes down to db.
	 * Do it in shutdown runtime, so no delay time.
	 *
	 * @return void
	 */
	public function cache_audit_logs(): void {
		$audit = new Audit();
		$audit->log_audit_events();
	}

	/**
	 * Pull the logs from db cached:
	 * - date_from: the start of the date we will run the query, as mysql time format,
	 * - date_to: similar to the above,
	 * others will refer to Audit.
	 *
	 * @param  Request $request  The request object containing filter parameters.
	 *
	 * @return Response
	 * @throws Exception If there is an error during log retrieval.
	 * @defender_route
	 */
	public function pull_logs( Request $request ): Response {
		$data = $request->get_data(
			array(
				'date_from'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'date_to'    => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'username'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'events'     => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
				'ip_address' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'paged'      => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		if ( empty( $data['date_from'] ) || empty( $data['date_to'] ) ) {
			return new Response( false, array( 'message' => esc_html__( 'Invalid data.', 'wpdef' ) ) );
		}
		// Convert date using timezone.
		$timezone  = wp_timezone();
		$date_from = ( new DateTime( $data['date_from'], $timezone ) )
			->setTime( 0, 0, 0 )
			->getTimestamp();
		$date_to   = ( new DateTime( $data['date_to'], $timezone ) )
			->setTime( 23, 59, 59 )
			->getTimestamp();

		$events     = $data['events'] ?? array();
		$ip_address = $data['ip_address'] ?? '';
		$paged      = $data['paged'] ?? 1;
		$username   = $data['username'] ?? '';
		$user_id    = '';
		if ( ! empty( $username ) ) {
			$user = get_user_by( 'login', $username );
			if ( is_object( $user ) ) {
				$user_id = $user->ID;
				// Fetch result with the specified user.
				$result = $this->service->fetch( $date_from, $date_to, $events, $user_id, $ip_address, $paged );
			} else {
				// A non-existent username.
				$result = array();
			}
		} else {
			// Fetch result with empty user field.
			$result = $this->service->fetch( $date_from, $date_to, $events, $user_id, $ip_address, $paged );
		}

		if ( is_wp_error( $result ) ) {
			return new Response( false, array( 'message' => $result->get_error_message() ) );
		}
		$logs = array();
		if ( ! empty( $result ) ) {
			foreach ( $result as $item ) {
				$logs[] = array_merge(
					$item->export(),
					array(
						'user'        => $this->get_user_display( $item->user_id ),
						'user_url'    => (int) $item->user_id > 0 ? get_edit_user_link( $item->user_id ) : '',
						'log_date'    => $this->get_date( $item->timestamp ),
						'format_date' => $this->format_date_time( $item->timestamp ),
					)
				);
			}
		}
		// @since 3.0.0 If no logs then $count = 0.
		if ( empty( $logs ) ) {
			$count = 0;
		} else {
			$count = Audit_Log::count( $date_from, $date_to, $events, $user_id, $ip_address );
		}
		$per_page = 20;

		// Get the count for the submitted data.
		return new Response(
			true,
			array(
				'logs'        => $logs,
				'total_items' => $count,
				'total_pages' => ceil( $count / $per_page ),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Generates a human-readable frequency text for audit reports.
	 *
	 * @param  Audit_Report $audit_report  The audit report object.
	 *
	 * @return string Returns the formatted frequency description.
	 */
	public function get_frequency_text( Audit_Report $audit_report ): string {
		$text = '';
		switch ( $audit_report->frequency ) {
			case 'daily':
				$text = ucfirst( $audit_report->day ) . 's at ' . $audit_report->time;
				break;
			case 'weekly':
			case 'monthly':
				$text = ucfirst( $audit_report->frequency ) . ' on ' . ucfirst( $audit_report->day ) . 's at ' . $audit_report->time;
				break;
			default:
				break;
		}

		return $text;
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script(
			'def-audit',
			'audit',
			$this->data_frontend()
		);
		wp_enqueue_script( 'def-audit' );
		$this->enqueue_main_assets();
	}

	/**
	 * Render the root element for frontend.
	 *
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * Provides a summary of audit logs.
	 *
	 * @return void
	 * @throws Exception If there is an error during summary generation.
	 * @defender_route
	 */
	public function summary(): void {
		$response = $this->model->is_active() ? $this->summary_data() : array();
		wp_send_json_success( $response );
	}

	/**
	 * Returns an array with summary data for audit logging.
	 *
	 * @param  bool $for_hub  Default 'false' because it's displayed on site summary sections.
	 *
	 * @return array
	 * @throws Exception Emits Exception in case of an error.
	 */
	public function summary_data( bool $for_hub = false ): array {
		// Monthly count.
		$date_from   = ( new DateTime( wp_date( 'Y-m-d', strtotime( '-30 days' ) ) ) )
			->setTime( 0, 0, 0 )
			->getTimestamp();
		$date_to     = ( new DateTime( wp_date( 'Y-m-d' ) ) )->setTime( 23, 59, 59 )->getTimestamp();
		$month_count = Audit_Log::count( $date_from, $date_to );
		// Weekly count.
		$date_from  = ( new DateTime( wp_date( 'Y-m-d', strtotime( '-7 days' ) ) ) )
			->setTime( 0, 0, 0 )
			->getTimestamp();
		$week_count = Audit_Log::count( $date_from, $date_to );
		// Daily count. Sync data to the Hub without timezone.
		$date_from = $for_hub ? new DateTime( 'now' ) : new DateTime( 'now', wp_timezone() );
		$date_from = $date_from->modify( '-24 hours' )->setTime( 0, 0, 0 )->getTimestamp();
		$day_count = Audit_Log::count( $date_from, $date_to );
		// Get the last item.
		$last = Audit_Log::get_last();
		if ( is_object( $last ) ) {
			$last = $for_hub
				? $this->persistent_hub_datetime_format( $last->timestamp )
				: $this->format_date_time( $last->timestamp );
		} else {
			$last = 'n/a';
		}

		return array(
			'monthCount' => $month_count,
			'weekCount'  => $week_count,
			'dayCount'   => $day_count,
			'lastEvent'  => $last,
			'report'     => wd_di()->get( Audit_Report::class )->to_string(),
		);
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$data = $request->get_data_by_model( $this->model );
		if ( false === $data['enabled'] && $data['enabled'] !== $this->model->is_active() ) {
			// Toggle off, so we need to flush everything to cloud.
			$this->service->flush();
		}

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
		}

		Config_Hub_Helper::set_clear_active_flag();

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
					'auto_close' => true,
				)
			)
		);
	}

	/**
	 * Converts the current state of the object to an array.
	 *
	 * @return array Returns an associative array of object properties.
	 */
	public function to_array(): array {
		return array_merge(
			array(
				'enabled' => $this->model->is_active(),
				'report'  => true,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
		( new Model_Audit_Logging() )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		Audit_Log::truncate();
		// Remove cached data.
		Array_Cache::remove( 'sockets', 'audit' );
		Array_Cache::remove( 'logs', 'audit' );
		Array_Cache::remove( 'menu_updated', 'audit' );
		Array_Cache::remove( 'post_updated', 'audit' );
		delete_site_option( Audit::CACHE_LAST_CHECKPOINT );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 * @throws Exception If there is an error.
	 */
	public function data_frontend(): array {
		$logs       = array();
		$count      = 0;
		$per_page   = 20;
		$total_page = 1;
		if ( $this->model->is_active() ) {
			$timezone  = wp_timezone();
			$date_from = ( new DateTime() )->setTimezone( $timezone )
											->sub( new DateInterval( 'P7D' ) )->setTime( 0, 0, 0 );
			$date_to   = ( new DateTime() )->setTimezone( $timezone )->setTime( 23, 59, 59 );
			$result    = $this->service->fetch(
				$date_from->getTimestamp(),
				$date_to->getTimestamp(),
				array(),
				'',
				'',
				1
			);
			if ( ! is_wp_error( $result ) ) {
				foreach ( $result as $item ) {
					$logs[] = array_merge(
						$item->export(),
						array(
							'user'        => $this->get_user_display( $item->user_id ),
							'user_url'    => (int) $item->user_id > 0 ? get_edit_user_link( $item->user_id ) : '',
							'log_date'    => $this->get_date( $item->timestamp ),
							'format_date' => $this->format_date_time( $item->timestamp ),
						)
					);
				}
				$count      = Audit_Log::count( $date_from->getTimestamp(), $date_to->getTimestamp() );
				$total_page = ceil( $count / $per_page );
			}
		}

		return array_merge(
			array(
				'model'       => $this->model->export(),
				'logs'        => $logs,
				'events_type' => Audit_Log::allowed_events(),
				'summary'     => array(
					'count_7_days' => $count,
					'report'       => wd_di()->get( Audit_Report::class )->to_string(),
				),
				'paging'      => array(
					'paged'       => 1,
					'total_pages' => $total_page,
					'count'       => $count,
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 *
	 * @throws Exception If table is not defined.
	 */
	public function import_data( array $data ) {
		$model = $this->model;
		if ( empty( $data ) ) {
			$model->enabled      = false;
			$model->storage_days = '6 months';
			$model->save();
		} else {
			$model->import( $data );
			if ( $model->validate() ) {
				$model->save();
			}
		}
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		if ( ! ( new WPMUDEV() )->is_pro() ) {
			return array(
				sprintf(
					/* translators: %s: Html for Pro-tag. */
					esc_html__( 'Inactive %s', 'wpdef' ),
					'<span class="sui-tag sui-tag-pro">Pro</span>'
				),
			);
		}

		if ( $this->model->is_active() ) {
			$strings      = array( esc_html__( 'Active', 'wpdef' ) );
			$audit_report = new Audit_Report();
			if ( 'enabled' === $audit_report->status ) {
				$strings[] = sprintf(
					/* translators: %s: Frequency value. */
					esc_html__( 'Email reports sending %s', 'wpdef' ),
					$audit_report->frequency
				);
			}
		} else {
			$strings = array( esc_html__( 'Inactive', 'wpdef' ) );
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
		if ( $is_pro ) {
			if ( $config['enabled'] ) {
				$strings = array( esc_html__( 'Active', 'wpdef' ) );
				if ( isset( $config['report'] ) && 'enabled' === $config['report'] ) {
					$strings[] = sprintf(
						/* translators: %s: Frequency value. */
						esc_html__( 'Email reports sending %s', 'wpdef' ),
						$config['frequency']
					);
				}
			} else {
				$strings = array( esc_html__( 'Inactive', 'wpdef' ) );
			}
		} else {
			$strings = array(
				sprintf(
					/* translators: %s: Html for Pro-tag. */
					esc_html__( 'Inactive %s', 'wpdef' ),
					'<span class="sui-tag sui-tag-pro">Pro</span>'
				),
			);
		}

		return $strings;
	}
}