<?php

namespace WP_Defender\Controller;

use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;

/**
 * Class HUB
 * @package WP_Defender\Controller
 */
class HUB extends Controller2 {
	use IO, Formats;

	private $view_onboard = false;

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		add_action( 'wdp_register_hub_action', array( &$this, 'add_hub_endpoint' ) );
		add_action( 'defender_hub_sync', array( &$this, 'hub_sync' ) );
	}

	public function add_hub_endpoint( $actions ) {
		$actions['defender_new_scan']          = array( &$this, 'new_scan' );
		$actions['defender_schedule_scan']     = array( &$this, 'schedule_scan' );
		$actions['defender_manage_audit_log']  = array( &$this, 'manage_audit_log' );
		$actions['defender_manage_lockout']    = array( &$this, 'manage_lockout' );
		$actions['defender_whitelist_ip']      = array( &$this, 'whitelist_ip' );
		$actions['defender_blacklist_ip']      = array( &$this, 'blacklist_ip' );
		$actions['defender_get_scan_progress'] = array( &$this, 'get_scan_progress' );

		// Backup/restore settings.
		$actions['defender_export_settings'] = array( &$this, 'export_settings' );
		$actions['defender_import_settings'] = array( &$this, 'import_settings' );
		// Get stats, version#1.
		$actions['defender_get_stats']    = array( &$this, 'get_stats' );
		// Version#2.
		$actions['defender_get_stats_v2'] = array( &$this, 'defender_get_stats_v2' );

		return $actions;
	}

	/**
	 * Create new scan, triggered from HUB.
	 */
	public function new_scan() {
		$scan = \WP_Defender\Model\Scan::create();
		if ( is_wp_error( $scan ) ) {
			wp_send_json_error(
				array(
					'message' => $scan->get_error_message(),
				)
			);
		}
		//Todo: need to save Malware_Report last_sent & est_timestamp?
		wd_di()->get( Scan::class )->do_async_scan( 'hub' );
		$this->maybe_change_onboarding_status();
		wp_send_json_success();
	}

	/**
	 * Schedule a scan, from HUB.
	 *
	 * @param array $params
	 */
	public function schedule_scan( $params ) {
		$frequency    = $params['frequency'];
		$day          = $params['day'];
		$time         = $params['time'];
		$allowed_freq = array( 1, 7, 30 );
		if (
			! in_array( $frequency, $allowed_freq, true )
			|| ! in_array( $day, $this->get_days_of_week(), true )
			|| ! in_array( $time, $this->get_times(), true )
		) {
			wp_send_json_error();
		}
		$malware_report            = new Malware_Report();
		$malware_report->frequency = $frequency;
		$malware_report->day       = $day;
		$malware_report->time      = $time;
		$malware_report->save();

		$this->maybe_change_onboarding_status();
		wp_send_json_success();
	}

	public function manage_audit_log() {
		$response = null;
		if ( class_exists( \WP_Defender\Model\Setting\Audit_Logging::class ) ) {
			$settings = new \WP_Defender\Model\Setting\Audit_Logging();
			$response = array();
			if ( true === $settings->enabled ) {
				$settings->enabled   = false;
				$response['enabled'] = false;
			} else {
				$settings->enabled   = true;
				$response['enabled'] = true;
			}
			$settings->save();
		}
		$this->maybe_change_onboarding_status();
		wp_send_json_success( $response );
	}

	/**
	 * @param array  $params
	 * @param string $action
	 */
	public function manage_lockout( $params, $action ) {
		$type     = $params['type'];
		$response = array();
		if ( 'login' === $type ) {
			$settings = new Login_Lockout();
			if ( $settings->enabled ) {
				$settings->enabled = false;
				$response[ $type ] = 'disabled';
			} else {
				$settings->enabled = true;
				$response[ $type ] = 'enabled';
			}
			$settings->save();
		} elseif ( '404' === $type ) {
			$settings = new Notfound_Lockout();
			if ( $settings->enabled ) {
				$settings->enabled = false;
				$response[ $type ] = 'disabled';
			} else {
				$settings->enabled = true;
				$response[ $type ] = 'enabled';
			}
			$settings->save();
		} elseif ( 'ua-lockout' === $type ) {
			$settings = new User_Agent_Lockout();
			if ( $settings->enabled ) {
				$settings->enabled = false;
				$response[ $type ] = 'disabled';
			} else {
				$settings->enabled = true;
				$response[ $type ] = 'enabled';
			}
			$settings->save();
		} else {
			$response[ $type ] = 'invalid';
		}
		$this->maybe_change_onboarding_status();
		wp_send_json_success( $response );
	}

	/**
	 * @param array  $params
	 * @param string $action
	 */
	public function whitelist_ip( $params, $action ) {
		$settings = new Blacklist_Lockout();
		$ip       = $params['ip'];
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$settings->remove_from_list( $ip, 'blocklist' );
			$settings->add_to_list( $ip, 'allowlist' );
		} else {
			wp_send_json_error();
		}
		$this->maybe_change_onboarding_status();
		wp_send_json_success();
	}

	/**
	 * @param array  $params
	 * @param string $action
	 */
	public function blacklist_ip( $params, $action ) {
		$settings = new Blacklist_Lockout();
		$ip       = $params['ip'];
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$settings->remove_from_list( $ip, 'allowlist' );
			$settings->add_to_list( $ip, 'blocklist' );
		} else {
			wp_send_json_error();
		}
		$this->maybe_change_onboarding_status();
		wp_send_json_success();
	}

	/**
	 * Push data into HUB.
	 *
	 * @param array  $params
	 * @param string $action
	 */
	public function get_stats( $params, $action ) {
		$data = $this->build_stats_to_hub();
		wp_send_json_success(
			array(
				'stats' => $data,
			)
		);
	}

	/**
	 * Push scan data into HUB.
	 */
	public function get_scan_progress() {
		$model = \WP_Defender\Model\Scan::get_active();
		if ( ! is_object( $model ) ) {
			wp_send_json_success(
				array(
					'progress' => - 1,
				)
			);
		}
		$percent = $model->percent;
		if ( $percent > 100 ) {
			$percent = 100;
		}
		$this->maybe_change_onboarding_status();
		wp_send_json_success(
			array(
				'progress' => $percent,
			)
		);
	}

	/**
	 * Export settings to HUB.
	 * Analog to export_strings but return not array. So separated method.
	 */
	public function export_settings() {
		$config_component = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
		$data             = $config_component->parse_data_for_hub();
		// Replace all the new line in configs.
		$configs = $data['configs'];
		foreach ( $configs as $module => $mdata ) {
			foreach ( $mdata as $key => $value ) {
				if ( is_string( $value ) ) {
					$value         = str_replace( array( "\r", "\n" ), '{nl}', $value );
					$mdata[ $key ] = $value;
				}
			}
			$configs[ $module ] = $mdata;
		}
		$data['configs'] = $configs;
		$this->maybe_change_onboarding_status();
		wp_send_json_success( $data );
	}

	/**
	 * Import settings from HUB.
	 * Analog to import_data but with object $params. So separated method.
	 */
	public function import_settings( $params ) {
		// Dirty but quick.
		if ( empty( $params->configs ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$configs = json_decode( json_encode( $params->configs ), true );
		if ( empty( $configs ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Empty data', 'wpdef' ),
				)
			);
		}

		$config_component = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
		$lockout_service  = wd_di()->get( \WP_Defender\Component\Blacklist_Lockout::class );
		foreach ( $configs as $module => $mdata ) {
			foreach ( $mdata as $key => $value ) {
				if ( in_array( $key, array( 'geoIP_db', 'geodb_path' ), true ) ) {
					if ( ! empty( $value ) ) {
						// Download it.
						$lockout_service->download_geo_ip();
					} else {
						// Reset it.
						$mdata[ $key ] = '';
					}
				} elseif ( is_string( $value ) ) {
					$value         = str_replace( '{nl}', PHP_EOL, $value );
					$mdata[ $key ] = $value;
				}
			}
			$configs[ $module ] = $mdata;
		}

		// If it's old config structure then we upgrade configs to new format.
		if ( ! empty( $configs ) && ! $config_component->check_for_new_structure( $configs ) ) {
			$adapter = wd_di()->get( \WP_Defender\Component\Config\Config_Adapter::class );
			$configs = $adapter->upgrade( $configs );
		}
		$restore_result = $config_component->restore_data( $configs );
		if ( is_string( $restore_result ) ) {
			wp_send_json_error(
				array(
					'message' => $restore_result,
				)
			);
		}
		$this->maybe_change_onboarding_status();

		// Active config.
		Config_Hub_Helper::active_config_from_hub_id( $params->hub_config_id );

		wp_send_json_success();
	}

	/**
	 * Build the json data for HUB 2.0.
	 */
	public function defender_get_stats_v2() {
		global $wp_version;

		$audit_log = wd_di()->get( Audit_Logging::class );
		$audit     = $audit_log->summary_data( true );

		$scan  = \WP_Defender\Model\Scan::get_last();
		$total = 0;
		if ( is_object( $scan ) ) {
			$total += count( $scan->get_issues() );
		}
		// Total number of Scan issues and Ignored items.
		$scan_total_issues = $total;

		$tweaks = wd_di()->get( Security_Tweaks::class )->data_frontend();
		$total += $tweaks['summary']['issues_count'];
		// Get statuses of login/404-request if Firewall Notification is enabled.
		$firewall_notification = wd_di()->get( Firewall_Notification::class );
		if ( 'enabled' === $firewall_notification->status ) {
			$login_lockout = $firewall_notification->configs['login_lockout'];
			$nf_lockout    = $firewall_notification->configs['nf_lockout'];
		} else {
			$login_lockout = $nf_lockout = false;// phpcs:ignore
		}
		$status_active     = \WP_Defender\Model\Notification::STATUS_ACTIVE;
		$model_sec_headers = wd_di()->get( \WP_Defender\Model\Setting\Security_Headers::class );
		$scan_report       = wd_di()->get( Malware_Report::class );
		$ret               = array(
			'summary'         => array(
				'count'     => $total,
				'next_scan' => $scan_report->get_next_run_for_hub(),
			),
			'report'          => array(
				'malware_scan'  => $scan_report->get_next_run_as_string( true ),
				'firewall'      => wd_di()->get( Firewall_Report::class )->get_next_run_as_string( true ),
				'audit_logging' => wd_di()->get( Audit_Report::class )->get_next_run_as_string( true ),
			),
			'security_tweaks' => array(
				'issues'       => $tweaks['summary']['issues_count'],
				'fixed'        => $tweaks['summary']['fixed_count'],
				'notification' => wd_di()->get( Tweak_Reminder::class )->status === $status_active,
				'wp_version'   => $wp_version,
				'php_version'  => PHP_VERSION,
			),
			'malware_scan'    => array(
				'count'        => $scan_total_issues,
				'notification' => wd_di()->get( Malware_Notification::class )->status === $status_active,
			),
			'firewall'        => array(
				// Todo: add data of UA-lockouts.
				'last_lockout'               => Lockout_Log::get_last_lockout_date( true ),
				'24_hours'                   => array(
					'login_lockout'      => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array(
							Lockout_Log::AUTH_LOCK,
						)
					),
					'404_lockout'        => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array(
							Lockout_Log::LOCKOUT_404,
						)
					),
					'user_agent_lockout' => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array(
							Lockout_Log::LOCKOUT_UA,
						)
					),
				),
				'7_days'                     => array(
					'login_lockout'      => Lockout_Log::count_login_lockout_last_7_days(),
					'404_lockout'        => Lockout_Log::count_404_lockout_last_7_days(),
					'user_agent_lockout' => Lockout_Log::count_ua_lockout_last_7_days(),
				),
				'30_days'                    => array(
					'login_lockout'      => Lockout_Log::count(
						strtotime( '-30 days' ),
						time(),
						array(
							Lockout_Log::AUTH_LOCK,
						)
					),
					'404_lockout'        => Lockout_Log::count(
						strtotime( '-30 days' ),
						time(),
						array(
							Lockout_Log::LOCKOUT_404,
						)
					),
					'user_agent_lockout' => Lockout_Log::count(
						strtotime( '-30 days' ),
						time(),
						array(
							Lockout_Log::LOCKOUT_UA,
						)
					),
				),
				'notification_status'        => array(
					'login_lockout' => $login_lockout,
					'404_lockout'   => $nf_lockout,
				),
				'login_lockout_enabled'      => wd_di()->get( Login_Lockout::class )->enabled,
				'lockout_404_enabled'        => wd_di()->get( Notfound_Lockout::class )->enabled,
				'user_agent_lockout_enabled' => wd_di()->get( User_Agent_Lockout::class )->enabled,
			),
			'audit'           => array(
				'last_event' => $audit['lastEvent'],
				'24_hours'   => $audit['dayCount'],
				'7_days'     => $audit['weekCount'],
				'30_days'    => $audit['monthCount'],
				'enabled'    => $audit_log->model->is_active(),
			),
			'advanced_tools'  => array(
				'security_headers' => array(
					'sh_xframe'               => $model_sec_headers->sh_xframe,
					'sh_xss_protection'       => $model_sec_headers->sh_xss_protection,
					'sh_content_type_options' => $model_sec_headers->sh_content_type_options,
					'sh_strict_transport'     => $model_sec_headers->sh_strict_transport,
					'sh_referrer_policy'      => $model_sec_headers->sh_referrer_policy,
					'sh_feature_policy'       => $model_sec_headers->sh_feature_policy,
				),
				'mask_login'       => wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->is_active(),
			),
			'two_fa'          => array(
				'status'     => wd_di()->get( Two_Fa::class )->enabled,
				'lost_phone' => wd_di()->get( Two_Fa::class )->lost_phone,
			),
		);

		wp_send_json_success(
			array(
				'stats' => $ret,
			)
		);
	}

	public function hub_sync() {
		$data = $this->build_stats_to_hub();
		$this->make_wpmu_request(
			WPMUDEV::API_HUB_SYNC,
			$data,
			array(
				'method' => 'POST',
			)
		);
	}

	public function remove_settings() {}

	public function remove_data() {}

	public function data_frontend() {}

	public function to_array() {}

	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Change status of Onboarding if there are remoted requests, e.g. from Hub.
	*/
	private function maybe_change_onboarding_status() {
		if ( $this->view_onboard ) {
			update_site_option( 'wp_defender_shown_activator', true );
			$this->view_onboard = false;
		}
	}

	/**
	 * Display Onboard if the bool value 'true' and vice versa.
	 * @param bool $is_show
	*/
	public function set_onboarding_status( $is_show ) {
		$this->view_onboard = $is_show;
	}

	/**
	 * @return bool
	 */
	public function get_onboarding_status() {
		return $this->view_onboard;
	}

	/**
	 * Only requests from Hub and for separate pages.
	*/
	public function listen_to_requests() {
		if ( $this->view_onboard && is_admin() && isset( $_GET['page'] ) && ! empty( $_GET['page'] ) ) {
			// Redirect from the Plugins page after clicking on the Settings link.
			$pages = array( 'wdf-setting' );
			if ( $this->is_wpmu_dev_admin() ) {
				// No 'wp-defender' because it's a default slug for Def Dashboard.
				array_push(
					$pages,
					'wdf-hardener',
					'wdf-scan',
					'wdf-logging',
					'wdf-ip-lockout',
					'wdf-2fa',
					'wdf-advanced-tools'
				);
			}
			if ( in_array( sanitize_text_field( $_GET['page'] ), $pages, true ) ) {
				update_site_option( 'wp_defender_shown_activator', true );
				$this->view_onboard = false;
			}
		}
	}
}
