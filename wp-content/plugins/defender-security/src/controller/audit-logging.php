<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use WP_Defender\Component\Audit;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\User;

class Audit_Logging extends Controller2 {
	use User, Formats;

	public $slug = 'wdf-logging';

	/**
	 * Use for cache.
	 *
	 * @var \WP_Defender\Model\Setting\Audit_Logging
	 */
	public $model;

	/**
	 * @var Audit
	 */
	public $service;

	public function __construct() {
		$this->register_page(
			esc_html__( 'Audit Logging', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->model   = wd_di()->get( \WP_Defender\Model\Setting\Audit_Logging::class );
		$this->service = new Audit();
		$this->register_routes();
		if ( $this->model->is_active() ) {
			$this->service->enqueue_event_listener();
			add_action( 'shutdown', array( &$this, 'cache_audit_logs' ) );
			/**
			 * We will schedule the time for flush data into cloud.
			 */
			if ( ! wp_next_scheduled( 'audit_sync_events' ) ) {
				wp_schedule_event( time() + 15, 'hourly', 'audit_sync_events' );
			}
			add_action( 'audit_sync_events', array( &$this, 'sync_events' ) );

			/**
			 * We will schedule the time to clean up old logs.
			 */
			if ( ! wp_next_scheduled( 'audit_clean_up_logs' ) ) {
				wp_schedule_event( time(), 'hourly', 'audit_clean_up_logs' );
			}
			add_action( 'audit_clean_up_logs', array( &$this, 'clean_up_audit_logs' ) );
		}
	}

	/**
	 * Sync all the events into cloud, this will happen per hourly basis.
	 */
	public function sync_events() {
		$this->service->flush();
	}

	/**
	 * Clean up all the old logs from the local storage, this will happen per hourly basis.
	 */
	public function clean_up_audit_logs() {
		$this->service->audit_clean_up_logs();
	}

	/**
	 * @throws \Exception
	 * @defender_route
	 */
	public function export_as_csv() {
		$date_from = HTTP::get(
			'date_from',
			date( 'Y-m-d H:i:s', strtotime( '-7 days', current_time( 'timestamp' ) ) )
		);
		$date_to   = HTTP::get( 'date_to', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
		$date_from = ( new \DateTime( $date_from ) )->setTime( 0, 0, 0 )->getTimestamp();
		$date_to   = ( new \DateTime( $date_to ) )->setTime( 23, 59, 59 )->getTimestamp();
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
		// Have data, now prepare to flush.
		$fp      = fopen( 'php://memory', 'w' );
		$headers = array(
			__( 'Summary', 'wpdef' ),
			__( 'Date / Time', 'wpdef' ),
			__( 'Context', 'wpdef' ),
			__( 'Type', 'wpdef' ),
			__( 'IP address', 'wpdef' ),
			__( 'User', 'wpdef' ),
		);
		fputcsv( $fp, $headers );
		foreach ( $result as $log ) {
			$fields = $log->export();
			$vars   = array(
				$fields['msg'],
				is_array( $fields['timestamp'] )
					? $this->format_date_time( date( 'Y-m-d H:i:s', $fields['timestamp'][0] ) )
					: $this->format_date_time( date( 'Y-m-d H:i:s', $fields['timestamp'] ) ),
				$fields['context'],
				$fields['action_type'],
				$fields['ip'],
				$this->get_user_display( $fields['user_id'] ),
			);
			fputcsv( $fp, $vars );
		}
		$filename = 'wdf-audit-logs-export-' . date( 'ymdHis' ) . '.csv';
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
	 */
	public function cache_audit_logs() {
		$audit = new Audit();
		$audit->log_audit_events();
	}

	/**
	 * Pull the logs from db cached:
	 *  - date_from: the start of the date we will run the query, as mysql time format,
	 *  - date_to: similar to the above,
	 *  others will refer to Audit.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function pull_logs( Request $request ) {
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
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$date_from = $data['date_from'];
		$date_from = new \DateTime( $date_from );
		$date_to   = $data['date_to'];
		$date_to   = new \DateTime( $date_to );

		if ( ! is_object( $date_from ) || ! is_object( $date_to ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid data', 'wpdef' ),
				)
			);
		}
		$username = isset( $data['username'] ) ? $data['username'] : '';
		$user_id  = '';
		$user     = get_user_by( 'login', $username );
		$events   = isset( $data['events'] ) ? $data['events'] : array();
		if ( is_object( $user ) ) {
			$user_id = $user->ID;
		}
		$ip_address = isset( $data['ip_address'] ) ? $data['ip_address'] : '';
		$paged      = isset( $data['paged'] ) ? $data['paged'] : 1;

		$result = $this->service->fetch(
			$date_from->setTime( 0, 0, 0 )->getTimestamp(),
			$date_to->setTime( 23, 59, 59 )->getTimestamp(),
			$events,
			$user_id,
			$ip_address,
			$paged
		);

		if ( is_wp_error( $result ) ) {
			return new Response(
				false,
				array(
					'message' => $result->get_error_message(),
				)
			);
		}
		$logs = array();
		foreach ( $result as $item ) {
			$logs[] = array_merge(
				$item->export(),
				array(
					'user'        => $this->get_user_display( $item->user_id ),
					'log_date'    => $this->get_date( $item->timestamp ),
					'format_date' => $this->format_date_time( gmdate( 'Y-m-d H:i:s', $item->timestamp ) ),
				)
			);
		}
		$count      = Audit_Log::count(
			$date_from->setTime( 0, 0, 0 )->getTimestamp(),
			$date_to->setTime( 23, 59, 59 )->getTimestamp(),
			$events,
			$user_id,
			$ip_address
		);
		$per_page   = 20;
		$total_page = ceil( $count / $per_page );

		// Get the week count.
		return new Response(
			true,
			array(
				'logs'        => $logs,
				'total_items' => $count,
				'total_pages' => $total_page,
				'per_page'    => $per_page,
			)
		);
	}

	public function get_frequency_text( Audit_Report $audit_report ) {
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

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		wp_enqueue_script( 'def-moment', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ) );
		wp_enqueue_script(
			'def-daterangepicker',
			defender_asset_url( '/assets/js/vendor/daterangepicker/daterangepicker.js' )
		);
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
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * @throws \Exception
	 * @defender_route
	 */
	public function summary() {
		$response = $this->model->is_active() ? $this->summary_data() : array();
		wp_send_json_success( $response );
	}

	/**
	 * @param bool $for_hub. Default 'false' because it's displayed on site summary sections.
	 *
	 * @return array
	*/
	public function summary_data( $for_hub = false) {
		$date_from   = ( new \DateTime( date( 'Y-m-d', strtotime( '-30 days' ) ) ) )->setTime(
			0,
			0,
			0
		)->getTimestamp();
		$date_to     = ( new \DateTime( date( 'Y-m-d' ) ) )->setTime( 23, 59, 59 )->getTimestamp();
		$month_count = Audit_Log::count( $date_from, $date_to );
		$date_from   = ( new \DateTime( date( 'Y-m-d', strtotime( '-7 days' ) ) ) )->setTime( 0, 0, 0 )->getTimestamp();
		$week_count  = Audit_Log::count( $date_from, $date_to );
		$last        = Audit_Log::get_last();
		$date_from   = ( new \DateTime( 'now', wp_timezone() ) )->modify( '-24 hours' )->setTime( 0, 0, 0 )->getTimestamp();
		$day_count   = Audit_Log::count( $date_from, $date_to );
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
		);
	}

	/**
	 * Save settings.
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
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
					'message' => __( 'Your settings have been updated.', 'wpdef' ),
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public function to_array() {
		return array_merge(
			array(
				'enabled' => $this->model->is_active(),
				'report'  => true,
			),
			$this->dump_routes_and_nonces()
		);
	}

	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Audit_Logging() )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
		Audit_Log::truncate();
		delete_site_option( Audit::CACHE_LAST_CHECKPOINT );
	}

	/**
	 * Setup config for audit.
	 * Todo: need?
	 */
	public function optimize_configs() {
		$settings          = new \WP_Defender\Model\Setting\Audit_Logging();
		$settings->enabled = true;
		$settings->save();

		$report = new Audit_Report();
		$report->save();
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		$logs       = array();
		$count      = 0;
		$per_page   = 20;
		$total_page = 1;
		if ( $this->model->is_active() ) {
			$date_from = ( new \DateTime() )
				->setTimezone( wp_timezone() )
				->sub( new \DateInterval( 'P7D' ) )->setTime( 0, 0, 0 );
			$date_to   = ( new \DateTime() )->setTimezone( wp_timezone() )->setTime( 23, 59, 59 );

			$result = $this->service->fetch(
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
							'log_date'    => $this->get_date( $item->timestamp ),
							'format_date' => $this->format_date_time( gmdate( 'Y-m-d H:i:s', $item->timestamp ) ),
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
	 * @param array $data
	 */
	public function import_data( $data ) {
		$model = $this->model;

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		if ( ! ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro() ) {
			return array(
				sprintf( __( 'Inactive %s', 'wpdef' ), '<span class="sui-tag sui-tag-pro">Pro</span>' ),
			);
		}

		if ( $this->model->is_active() ) {
			$strings      = array( __( 'Active', 'wpdef' ) );
			$audit_report = new \WP_Defender\Model\Notification\Audit_Report();
			if ( 'enabled' === $audit_report->status ) {
				$strings[] = sprintf(
					__( 'Email reports sending %s', 'wpdef' ),
					$audit_report->frequency
				);
			}
		} else {
			$strings = array( __( 'Inactive', 'wpdef' ) );
		}

		return $strings;
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( $config, $is_pro ) {
		if ( $is_pro ) {
			if ( $config['enabled'] ) {
				$strings = array( __( 'Active', 'wpdef' ) );
				if ( isset( $config['report'] ) && 'enabled' === $config['report'] ) {
					$strings[] = sprintf(
					/* translators: %s - option frequency */
						__( 'Email reports sending %s', 'wpdef' ),
						$config['frequency']
					);
				}
			} else {
				$strings = array( __( 'Inactive', 'wpdef' ) );
			}
		} else {
			$strings = array(
				sprintf(
				/* translators: ... */
					__( 'Inactive %s', 'wpdef' ),
					'<span class="sui-tag sui-tag-pro">Pro</span>'
				)
			);
		}

		return $strings;
	}
}
