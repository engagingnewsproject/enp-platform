<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Blacklist_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Table_Lockout;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Setting\Notfound_Lockout;

class Firewall extends \WP_Defender\Controller2 {
	use \WP_Defender\Traits\IP;
	use \WP_Defender\Traits\Formats;

	protected $slug = 'wdf-ip-lockout';

	/**
	 * @var \WP_Defender\Model\Setting\Firewall
	 */
	protected $model;


	/**
	 * @var \WP_Defender\Component\Firewall
	 */
	public $service;

	public function __construct() {
		$this->register_page(
			esc_html__( 'Firewall', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		$this->model   = wd_di()->get( \WP_Defender\Model\Setting\Firewall::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Firewall::class );
		$this->register_routes();
		$this->maybe_show_demo_lockout();
		$this->maybe_lockout();
		//init the controller
		wd_di()->get( \WP_Defender\Controller\Login_Lockout::class );
		wd_di()->get( Nf_Lockout::class );
		wd_di()->get( Blacklist::class );
		wd_di()->get( Firewall_Logs::class );

		/**
		 * We will schedule the time to clean up old firewall logs
		 */
		if ( ! wp_next_scheduled( 'firewall_clean_up_logs' ) ) {
			wp_schedule_event( time() + 10, 'hourly', 'firewall_clean_up_logs' );
		}
		add_action( 'firewall_clean_up_logs', array( &$this, 'clean_up_firewall_logs' ) );
		//additional hooks
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ), 11 );
	}

	/**
	 * Clean up all the old logs from the local storage, this will happen per hourly basis
	 */
	public function clean_up_firewall_logs() {
		$this->service->firewall_clean_up_logs();
	}

	/**
	 * This is for handling request from dashboard
	 * @defender_route
	 */
	public function dashboard_activation() {
		$il          = wd_di()->get( \WP_Defender\Model\Setting\Login_Lockout::class );
		$nf          = wd_di()->get( Notfound_Lockout::class );
		$il->enabled = true;
		$il->save();
		$nf->enabled = true;
		$nf->save();

		return new Response( true, $this->to_array() );
	}

	/**
	 * Render the view page
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Save the main settings
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data(
			array(
				'storage_days' => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array(
					'message' => __( 'Your settings have been updated.', 'wpdef' ),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
		);
	}

	/**
	 * @return array
	 */
	public function to_array() {
		$il = wd_di()->get( \WP_Defender\Model\Setting\Login_Lockout::class );
		$nf = wd_di()->get( Notfound_Lockout::class );

		return array_merge(
			array(
				'summary'      => array(
					'ip'          => array(
						'week' => Lockout_Log::count_login_lockout_last_7_days(),
					),
					'nf'          => array(
						'week' => Lockout_Log::count_404_lockout_last_7_days(),
					),
					'lastLockout' => Lockout_Log::get_last_lockout_date(),
				),
				'notification' => true,
				'enabled'      => $nf->enabled || $il->enabled,
				'enable_login' => $il->enabled,
				'enable_404'   => $nf->enabled,
			),
			$this->dump_routes_and_nonces()
		);
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		wp_enqueue_media();

		wp_localize_script( 'def-iplockout', 'iplockout', $this->data_frontend() );
		wp_enqueue_script( 'def-iplockout' );
		$this->enqueue_main_assets();

		do_action( 'defender_ip_lockout_action_assets' );
	}

	private function maybe_show_demo_lockout() {
		$is_test = HTTP::get( 'def-lockout-demo', 0 );
		if ( 1 === (int) $is_test ) {
			$type = HTTP::get( 'type' );

			switch ( $type ) {
				case 'login':
					$settings = wd_di()->get( \WP_Defender\Model\Setting\Login_Lockout::class );
					$message  = $settings->lockout_message;
					break;
				case '404':
					$settings = wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class );
					$message  = $settings->lockout_message;
					break;
				case 'blocklist':
					$settings = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
					$message  = $settings->ip_lockout_message;
					break;
				default:
					$message = __( 'Demo', 'wpdef' );
					break;
			}

			$this->render_partial(
				'ip-lockout/locked',
				array(
					'message' => $message,
				)
			);
			exit;
		}
	}

	/**
	 * We wil check and prevent the access if the current IP is blacklist, or get temporary banned
	 */
	public function maybe_lockout() {
		do_action( 'wd_before_lockout' );

		$the_list = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
		$service  = wd_di()->get( Blacklist_Lockout::class );
		$ip       = $this->get_user_ip();
		if ( $service->is_ip_whitelisted( $ip ) ) {
			return;
		}
		// green light if access staff is enabled
		if ( $this->is_a_staff_access() ) {
			return;
		}

		if ( $service->is_blacklist( $ip ) || $service->is_country_blacklist() ) {
			//this one is get blacklisted
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			header( 'HTTP/1.0 403 Forbidden' );
			header( 'Cache-Control: private' );

			$this->render_partial(
				'ip-lockout/locked',
				array(
					'message' => $the_list->ip_lockout_message,
				)
			);
			exit();
		}

		$notfound_lockout = wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class );
		if ( $notfound_lockout->enabled && false === $notfound_lockout->detect_logged && is_user_logged_in() ) {
			/**
			 * We don't need to check the IP if:
			 * the current user can logged in and no blacklisted,
			 * the option detect_404_logged is disabled
			 */
			return;
		}

		//check blacklist
		$model = Lockout_Ip::get( $ip );
		if ( is_object( $model ) && $model->is_locked() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			header( 'HTTP/1.0 403 Forbidden' );
			header( 'Cache-Control: private' );

			$this->render_partial(
				'ip-lockout/locked',
				array(
					'message' => $model->lockout_message,
				)
			);
			exit();
		}
	}

	/**
	 * Check if the access is from our staff access
	 * @return bool
	 */
	private function is_a_staff_access() {
		if ( defined( 'WPMUDEV_DISABLE_REMOTE_ACCESS' ) && true === constant( 'WPMUDEV_DISABLE_REMOTE_ACCESS' ) ) {
			return false;
		}

		$wpmu_dev = new WPMUDEV();
		if ( $wpmu_dev->get_apikey() && isset( $_REQUEST['wdpunkey'] ) ) {
			$access = \WPMUDEV_Dashboard::$site->get_option( 'remote_access' );
			$this->log( var_export( $access, true ) );

			return hash_equals( $_REQUEST['wdpunkey'], $access['key'] );
		}

		return false;
	}

	/**
	 * Query the data
	 */
	public function query_logs() {
		if ( ! $this->check_permission() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'query_logs' . 'ip_lockout' ) ) {
			return;
		}

		$filters = array(
			'from' => strtotime( 'midnight', strtotime( HTTP::post( 'date_from', strtotime( '-7 days midnight' ) ) ) ),
			'to'   => strtotime( 'tomorrow', strtotime( HTTP::post( 'date_to', strtotime( 'tomorrow' ) ) ) ),
			'type' => HTTP::post( 'type', '' ),
			'ip'   => HTTP::post( 'ip', '' ),
		);

		$paged     = HTTP::post( 'paged', 1 );
		$order_by  = HTTP::post( 'orderBy', 'id' );
		$order     = HTTP::post( 'order', 'desc' );
		$page_size = HTTP::post( 'per_page', 20 );

		$cache_name = $filters['from'] . '-' . $filters['to'];
		$logs       = Lockout_Log::query_logs( $cache_name, $filters, $paged, $order_by, $order, $page_size );
		$count      = Lockout_Log::count( $filters['from'], $filters['to'], $filters['type'], $filters['ip'] );

		$tl_component = new Table_Lockout();
		$ip           = $tl_component->get_user_ip();
		// If 'ban_status' is selected
		$key_status = HTTP::post( 'ban_status', '' );

		$new_logs     = array();
		$adv_log_data = array();
		foreach ( $logs as $log ) {
			if ( '' !== $key_status && ! $tl_component->ip_has_status_text( $log->ip, $key_status ) ) {
				continue;
			}
			$adv_log_data[ $log->id ] = array(
				'date'        => $this->get_date( $log->date ),
				'format_date' => $this->format_date_time( gmdate( 'Y-m-d H:i:s', $log->date ) ),
				'status_text' => $tl_component->get_ip_status_text( $log->ip ),
				'ip_status'   => $tl_component->black_or_white( $log->ip ),
				'is_mine'     => $ip === $log->ip,
			);

			$new_logs[] = $log;
		}
		if ( '' !== $key_status ) {
			$count = count( $new_logs );
		}
		$total_pages = ceil( $count / $page_size );
		wp_send_json_success(
			array(
				'logs'       => $new_logs,
				'advLogData' => $adv_log_data,
				'countAll'   => $count,
				'totalPages' => $total_pages,
				'perPage'    => $page_size,
			)
		);
	}

	/**
	 * CSV exporter for IP logs
	 */
	public function export_ip_logs() {
		if ( ! $this->check_permission() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'export_ip_logs' . 'ip_lockout' ) ) {
			return;
		}

		$fp      = fopen( 'php://memory', 'w' );
		$headers = array(
			__( 'Log', 'wpdef' ),
			__( 'Date / Time', 'wpdef' ),
			__( 'Type', 'wpdef' ),
			__( 'IP address', 'wpdef' ),
			__( 'Status', 'wpdef' ),
		);
		fputcsv( $fp, $headers );

		$filters = array(
			'from' => strtotime( 'midnight', strtotime( HTTP::get( 'date_from', strtotime( '-7 days midnight' ) ) ) ),
			'to'   => strtotime( 'tomorrow', strtotime( HTTP::get( 'date_to', strtotime( 'tomorrow' ) ) ) ),
			'type' => HTTP::get( 'term', false ),
			'ip'   => HTTP::get( 'ip', false ),
		);

		$paged     = HTTP::get( 'paged', 1 );
		$order_by  = HTTP::get( 'orderBy', 'id' );
		$order     = HTTP::get( 'order', 'desc' );
		$page_size = 20;

		$cache_name = $filters['from'] . '-' . $filters['to'];
		$logs       = Lockout_Log::query_logs( $cache_name, $filters, $paged, $order_by, $order, $page_size );

		// If 'ban_status' is selected
		$key_status = HTTP::get( 'ban_status', '' );

		$tl_component = new Table_Lockout();
		foreach ( $logs as $log ) {
			if ( '' !== $key_status && ! $tl_component->ip_has_status_text( $log->ip, $key_status ) ) {
				continue;
			}
			$item = array(
				$log->log,
				$this->get_date( $log->date ),
				$tl_component->get_type( $log->type ),
				$log->ip,
				$tl_component->get_ip_status_text( $log->ip ),
			);
			fputcsv( $fp, $item );
		}
		$filename = 'wdf-lockout-logs-export-' . gmdate( 'ymdHis' ) . '.csv';
		fseek( $fp, 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// make php send the generated csv lines to the browser
		fpassthru( $fp );
		exit();
	}

	/**
	 * Endpoint for toggle IP blocklist or allowlist, use on logs item content
	 */
	public function toggle_ip_action() {
		if ( ! $this->check_permission() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'toggle_ip_action' . 'ip_lockout' ) ) {
			return;
		}

		$ip   = HTTP::post( 'ip', false );
		$type = HTTP::post( 'type', false );

		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$bl_setting = new \WP_Defender\Model\Setting\Blacklist_Lockout();
			if ( in_array( $type, array( 'unallowlist', 'unblocklist' ), true ) ) {
				$type = substr( $type, 2 );
				$bl_setting->remove_from_list( $ip, $type );
				wp_send_json_success(
					array(
						'message' => sprintf(
						/* translators: ... */
							__(
								'IP %1$s has been removed from your %2$s. You can control your %3$s in <a href="%4$s">IP Lockouts.</a>',
								'wpdef'
							),
							$ip,
							$type,
							$type,
							network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
						),
					)
				);
			} else {
				$bl_setting->add_to_list( $ip, $type );
				wp_send_json_success(
					array(
						'message' => sprintf(
						/* translators: ... */
							__(
								'IP %1$s has been added to your %2$s. You can control your %3$s in <a href="%4$s">IP Lockouts.</a>',
								'wpdef'
							),
							$ip,
							$type,
							$type,
							network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
						),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'No record found', 'wpdef' ),
				)
			);
		}
	}

	/**
	 * Remove all IP logs
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function empty_logs( Request $request ) {
		if ( Lockout_Log::truncate() ) {

			return new Response(
				true,
				array(
					'message'  => __( 'Your logs have been successfully deleted.', 'wpdef' ),
					'interval' => 1,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => __( 'Failed remove!', 'wpdef' ),
			)
		);
	}

	/**
	 * Return summary data
	 *
	 * @return array
	 */
	public function get_summary() {
		$lockouts = Lockout_Log::get_summary();

		if ( 0 === count( $lockouts ) ) {
			return array(
				'lockout_last'            => __( 'Never', 'wpdef' ),
				'lockout_today'           => 0,
				'lockout_this_month'      => 0,
				'lockout_login_today'     => 0,
				'lockout_login_this_week' => 0,
				'lockout_404_today'       => 0,
				'lockout_404_this_week'   => 0,
			);
		}

		//init params
		$lockout_last            = 0;
		$lockout_today           = 0;
		$lockout_this_month      = count( $lockouts );
		$lockout_login_today     = 0;
		$lockout_login_this_week = 0;
		$lockout_404_this_week   = 0;
		$lockout_404_today       = 0;
		//time
		$today_midnight  = strtotime( '-24 hours', current_time( 'timestamp' ) ); // phpcs:ignore
		$first_this_week = strtotime( '-7 days', current_time( 'timestamp' ) ); // phpcs:ignore
		foreach ( $lockouts as $k => $log ) {
			//the other as DESC, so first will be last lockout
			if ( $lockout_last < $log->date ) {
				$lockout_last = $log->date;
			}

			if ( $log->date > $today_midnight ) {
				$lockout_today ++;
				if ( Lockout_Log::LOCKOUT_404 === $log->type ) {
					$lockout_404_today += 1;
				} else {
					$lockout_login_today += 1;
				}
			}

			if ( Lockout_Log::AUTH_LOCK === $log->type && $log->date > $first_this_week ) {
				$lockout_login_this_week ++;
			} elseif ( Lockout_Log::LOCKOUT_404 === $log->type && $log->date > $first_this_week ) {
				$lockout_404_this_week ++;
			}
		}

		$data = array(
			'lockout_last'            => $this->format_date_time( $lockout_last ),
			'lockout_today'           => $lockout_today,
			'lockout_this_month'      => $lockout_this_month,
			'lockout_login_today'     => $lockout_login_today,
			'lockout_login_this_week' => $lockout_login_this_week,
			'lockout_404_today'       => $lockout_404_today,
			'lockout_404_this_week'   => $lockout_404_this_week,
		);

		return $data;
	}

	/**
	 * Endpoint for bulk action
	 */
	public function bulk_action() {
		if ( ! $this->check_permission() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'bulk_action' . 'ip_lockout' ) ) {
			return;
		}

		$ids  = HTTP::post( 'ids', array() );
		$type = HTTP::post( 'type', false );

		$messages = '';
		$ips      = array();
		if ( count( $ids ) && $type ) {
			$bl_setting = new \WP_Defender\Model\Setting\Blacklist_Lockout();
			switch ( $type ) {
				case 'allowlist':
					foreach ( $ids as $id ) {
						$model = Lockout_Log::find_by_id( $id );
						$ips[] = $model->ip;
						$bl_setting->add_to_list( $model->ip, 'allowlist' );
					}
					$messages = sprintf(
					/* translators: ... */
						__(
							'IP %1$s has been added to your allowlist. You can control your allowlist in <a href="%2$s">IP Lockouts.</a>',
							'wpdef'
						),
						implode( ', ', $ips ),
						network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
					);
					break;
				case 'ban':
					foreach ( $ids as $id ) {
						$model = Lockout_Log::find_by_id( $id );
						$ips[] = $model->ip;
						$bl_setting->add_to_list( $model->ip, 'blocklist' );
					}
					$messages = sprintf(
					/* translators: ... */
						__(
							'IP %1$s has been added to your blocklist. You can control your blocklist in <a href="%2$s">IP Lockouts.</a>',
							'wpdef'
						),
						implode( ', ', $ips ),
						network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist' )
					);
					break;
				case 'delete':
					foreach ( $ids as $id ) {
						$model = Lockout_Log::find_by_id( $id );
						$ips[] = $model->ip;
						$model->delete();
					}
					$messages = sprintf(
					/* translators: ... */
						__( 'IP %s has been deleted', 'wpdef' ),
						implode( ', ', $ips )
					);
					break;
				default:
					$bl_component = new Blacklist_Lockout();
					//param not from the button on frontend, log it
					$this->log(
						sprintf(
						/* translators: ... */
							__( 'Unexpected value %1$s from IP %2$s', 'wpdef' ),
							$type,
							$bl_component->get_user_ip()
						)
					);
					break;
			}

			wp_send_json_success(
				array(
					'reload'  => 1,
					'message' => $messages,
				)
			);
		}
	}

	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Login_Lockout )->delete();
		( new \WP_Defender\Model\Setting\Blacklist_Lockout )->delete();
		( new \WP_Defender\Model\Setting\Notfound_Lockout )->delete();
		( new \WP_Defender\Model\Setting\Firewall )->delete();
	}

	public function remove_data() {
		Lockout_Log::truncate();
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget
	 * @return array
	 */
	function data_frontend() {
		$summary_data = $this->get_summary();
		$data         = array(
			'login'                => array(
				'week' => $summary_data['lockout_login_this_week'],
				'day'  => $summary_data['lockout_login_today'],
			),
			'nf'                   => array(
				'week' => $summary_data['lockout_404_this_week'],
				'day'  => $summary_data['lockout_404_today'],
			),
			'month'                => $summary_data['lockout_this_month'],
			'day'                  => $summary_data['lockout_today'],
			'last_lockout'         => $summary_data['lockout_last'],
			'settings'             => $this->model->export(),
			'login_lockout'        => wd_di()->get( \WP_Defender\Model\Setting\Login_Lockout::class )->enabled,
			'nf_lockout'           => wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class )->enabled,
			'report'               => wd_di()->get( Firewall_Report::class )->to_string(),
			'notification_lockout' => 'enabled' === wd_di()->get( Firewall_Notification::class )->status,
		);

		return array_merge( $data, $this->dump_routes_and_nonces() );
	}

	/**
	 * @param $data array
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
		$strings               = array( __( 'Active', 'wpdef' ) );
		$is_pro                = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
		$firewall_notification = new \WP_Defender\Model\Notification\Firewall_Notification();
		$firewall_report       = new \WP_Defender\Model\Notification\Firewall_Report();

		if ( 'enabled' === $firewall_notification->status ) {
			$strings[] = __( 'Email notifications active', 'wpdef' );
		}
		if ( $is_pro && 'enabled' === $firewall_report->status ) {
			$strings[] = sprintf(
			/* translators: ... */
				__( 'Email reports sending %s', 'wpdef' ),
				$firewall_report->frequency
			);
		} elseif ( ! $is_pro ) {
			$strings[] = sprintf(
			/* translators: ... */
				__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}
}
