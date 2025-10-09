<?php
/**
 * Handle various actions and interactions with the WPMUDEV Hub.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Formats;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Component\Quarantine;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Component\IP\Antibot_Global_Firewall;
use WP_Defender\Component\IP\Global_IP;
use WP_Defender\Component\Backup_Settings;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Component\Config\Config_Adapter;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Setting\Audit_Logging as Model_Audit_Logging;
use WP_Defender\Model\Setting\Security_Tweaks as Model_Security_Tweaks;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;

/**
 * Handles various actions and interactions with the WPMUDEV Hub.
 */
class HUB extends Event {

	use IO;
	use Formats;

	/**
	 * Flag indicating whether to display the onboarding view or not.
	 *
	 * @var bool
	 */
	private $view_onboard = false;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		add_action( 'wdp_register_hub_action', array( $this, 'add_hub_endpoint' ) );
		add_action( 'defender_hub_sync', array( $this, 'hub_sync' ) );
	}

	/**
	 * Add various hub endpoints to the actions array.
	 *
	 * @param  array $actions  An array of actions.
	 *
	 * @return array The updated actions array with hub endpoints added.
	 */
	public function add_hub_endpoint( $actions ) {
		$actions['defender_new_scan']              = array( $this, 'new_scan' );
		$actions['defender_schedule_scan']         = array( $this, 'schedule_scan' );
		$actions['defender_manage_audit_log']      = array( $this, 'manage_audit_log' );
		$actions['defender_manage_lockout']        = array( $this, 'manage_lockout' );
		$actions['defender_whitelist_ip']          = array( $this, 'whitelist_ip' );
		$actions['defender_blacklist_ip']          = array( $this, 'blacklist_ip' );
		$actions['defender_get_scan_progress']     = array( $this, 'get_scan_progress' );
		$actions['defender_manage_recaptcha']      = array( $this, 'manage_recaptcha' );
		$actions['defender_manage_2fa']            = array( $this, 'manage_2fa' );
		$actions['defender_manage_global_ip_list'] = array( $this, 'manage_global_ip_list' );
		$actions['defender_set_global_ip_list']    = array( $this, 'set_global_ips' );
		$actions['defender_set_antibot_status']    = array( $this, 'set_antibot_status' );
		// Backup/restore settings.
		$actions['defender_export_settings'] = array( $this, 'export_settings' );
		$actions['defender_import_settings'] = array( $this, 'import_settings' );
		// Get stats, version#1.
		$actions['defender_get_stats'] = array( $this, 'get_stats' );
		// Version#2.
		$actions['defender_get_stats_v2'] = array( $this, 'defender_get_stats_v2' );

		$actions['defender_get_quarantined_files']    = array( $this, 'get_quarantined_files' );
		$actions['defender_restore_quarantined_file'] = array( $this, 'restore_quarantined_file' );

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
		// Todo: need to save Malware_Report last_sent & est_timestamp?
		$scan_controller = wd_di()->get( Scan::class );
		$scan_controller->do_async_scan( 'hub' );

		wp_send_json_success();
	}

	/**
	 * Schedule a scan, from HUB.
	 *
	 * @param  array $params  Schedule config.
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

		wp_send_json_success();
	}

	/**
	 * Track a feature activation or deactivation from the Hub.
	 *
	 * @param  bool   $is_active  Whether the feature is being activated or deactivated.
	 * @param  string $feature_title  The title of the feature being tracked.
	 */
	protected function track_feature_from_hub( bool $is_active, string $feature_title ) {
		$event = $is_active ? 'def_feature_activated' : 'def_feature_deactivated';
		$data  = array(
			'Feature'        => $feature_title,
			'Triggered From' => 'Hub',
		);

		$this->track_feature( $event, $data );
	}

	/**
	 * Manage the audit log settings by toggling the enabled status of the Audit Logging feature.
	 *
	 * @return void
	 */
	public function manage_audit_log() {
		$response = null;
		if ( class_exists( Model_Audit_Logging::class ) ) {
			$settings = new Model_Audit_Logging();
			$response = array();
			if ( true === $settings->enabled ) {
				$settings->enabled   = false;
				$response['enabled'] = false;
			} else {
				$settings->enabled   = true;
				$response['enabled'] = true;
			}
			$settings->save();
			// Track.
			if ( $this->is_tracking_active() ) {
				$this->track_feature_from_hub( ! $settings->enabled, 'Audit Logging' );
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Manages the lockout feature based on the given parameters.
	 *
	 * @param  array  $params  The parameters for managing the lockout.
	 *                       - type (string): The type of lockout to manage. Possible values: 'login', '404',
	 *                       'ua-lockout'.
	 * @param  string $action  The action to perform on the lockout.
	 *
	 * @return void
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
			$feature = 'Login Protection';
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
			$feature = '404 Detection';
		} elseif ( User_Agent_Lockout::get_module_slug() === $type ) {
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
		// Track. Only for Login & NF Lockouts. Ignoring phpcs error due to all possible types.
		if ( $this->is_tracking_active() && in_array( $type, array( 'login', '404' ), true ) ) {
			$event = $settings->enabled ? 'def_feature_deactivated' : 'def_feature_activated';
			$data  = array(
				'Feature'        => $feature,
				'Triggered From' => 'Hub',
			);

			$this->track_feature( $event, $data );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Whitelists an IP address by removing it from the block list and adding it to the allow list.
	 *
	 * @param  array  $params  An array containing the IP address to whitelist.
	 * @param  string $action  The action to perform.
	 *
	 * @return void
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
		wp_send_json_success();
	}

	/**
	 * Blacklist an IP address by removing it from the allow list and adding it to the block list.
	 *
	 * @param  array  $params  An array containing the IP address to blacklist.
	 * @param  string $action  The action to perform.
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
		wp_send_json_success();
	}

	/**
	 * Push data into HUB. It's without timezone.
	 *
	 * @param array  $params  An array containing the data to push.
	 * @param string $action  The action to perform.
	 */
	public function get_stats( $params, $action ) {
		$data = $this->build_stats_to_hub();
		wp_send_json_success(
			array( 'stats' => $data )
		);
	}

	/**
	 * Push scan data into HUB.
	 */
	public function get_scan_progress() {
		$model = \WP_Defender\Model\Scan::get_active();
		if ( ! is_object( $model ) ) {
			wp_send_json_success(
				array( 'progress' => - 1 )
			);
		}
		$percent = $model->percent;
		if ( $percent > 100 ) {
			$percent = 100;
		}
		wp_send_json_success(
			array( 'progress' => $percent )
		);
	}

	/**
	 * Export settings to HUB.
	 * Analog to export_strings but return not array. So separated method.
	 */
	public function export_settings() {
		$config_component = wd_di()->get( Backup_Settings::class );
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
		wp_send_json_success( $data );
	}

	/**
	 * Import settings from HUB.
	 * Analog to import_data but with object $params. So separated method.
	 *
	 * @param object object $params Request parameters.
	 */
	public function import_settings( $params ) {
		// Dirty but quick.
		if ( empty( $params->configs ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid config', 'wpdef' ) )
			);
		}

		$configs = json_decode( wp_json_encode( $params->configs ), true );
		if ( empty( $configs ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Empty data', 'wpdef' ) )
			);
		}

		$config_component = wd_di()->get( Backup_Settings::class );
		$lockout_service  = wd_di()->get( \WP_Defender\Component\Blacklist_Lockout::class );
		foreach ( $configs as $module => $mdata ) {
			foreach ( $mdata as $key => $value ) {
				// Todo: update logic to import/export whitelisted/blocklisted countries via maxmind_license_key.
				if ( in_array( $key, array( 'geoIP_db', 'geodb_path' ), true ) ) {
					if ( ! empty( $value ) ) {
						// Download it.
						$lockout_service->is_geodb_downloaded();
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
			$adapter = wd_di()->get( Config_Adapter::class );
			$configs = $adapter->upgrade( $configs );
		}
		$restore_result = $config_component->restore_data( $configs, 'hub' );
		if ( is_string( $restore_result ) ) {
			wp_send_json_error(
				array( 'message' => $restore_result )
			);
		}

		// Active config.
		Config_Hub_Helper::active_config_from_hub_id( (int) $params->hub_config_id );

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
		// Exclude direct call of data_frontend().
		$tweak_arr = wd_di()->get( Model_Security_Tweaks::class )->get_tweak_types();

		$total += $tweak_arr['count_issues'];
		// Get statuses of login/404/ua-request if Firewall Notification is enabled.
		$firewall_notification = wd_di()->get( Firewall_Notification::class );

		if ( 'enabled' === $firewall_notification->status ) {
			$login_lockout = $firewall_notification->configs['login_lockout'];
			$nf_lockout    = $firewall_notification->configs['nf_lockout'];
			// since 3.3.0.
			$ua_lockout = $firewall_notification->configs['ua_lockout'] ?? false;
		} else {
			$login_lockout = false;
			$nf_lockout    = false;
			$ua_lockout    = false;
		}

		$status_active     = \WP_Defender\Model\Notification::STATUS_ACTIVE;
		$model_sec_headers = wd_di()->get( \WP_Defender\Model\Setting\Security_Headers::class );
		$scan_report       = wd_di()->get( Malware_Report::class );
		$two_fa            = wd_di()->get( Two_Fa::class );

		$quarantined_files = class_exists( 'WP_Defender\Component\Quarantine' ) ?
			wd_di()->get( Quarantine::class )->hub_list() : array();
		$antibot_service   = wd_di()->get( Antibot_Global_Firewall::class );

		$ret = array(
			'summary'           => array(
				'count'     => $total,
				'next_scan' => $scan_report->get_next_run_for_hub(),
			),
			'report'            => array(
				'malware_scan'  => $scan_report->get_next_run_as_string( true ),
				'firewall'      => wd_di()->get( Firewall_Report::class )->get_next_run_as_string( true ),
				'audit_logging' => wd_di()->get( Audit_Report::class )->get_next_run_as_string( true ),
			),
			'security_tweaks'   => array(
				'issues'       => $tweak_arr['count_issues'],
				'fixed'        => $tweak_arr['count_fixed'],
				'notification' => wd_di()->get( Tweak_Reminder::class )->status === $status_active,
				'wp_version'   => $wp_version,
				'php_version'  => PHP_VERSION,
			),
			'malware_scan'      => array(
				'count'        => $scan_total_issues,
				'notification' => wd_di()->get( Malware_Notification::class )->status === $status_active,
			),
			'firewall'          => array(
				'last_lockout'               => Lockout_Log::get_last_lockout_date( true ),
				'24_hours'                   => array(
					'login_lockout'      => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array( Lockout_Log::AUTH_LOCK )
					),
					'404_lockout'        => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array( Lockout_Log::LOCKOUT_404 )
					),
					'user_agent_lockout' => Lockout_Log::count(
						strtotime( '-24 hours' ),
						time(),
						array( Lockout_Log::LOCKOUT_UA )
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
						array( Lockout_Log::AUTH_LOCK )
					),
					'404_lockout'        => Lockout_Log::count(
						strtotime( '-30 days' ),
						time(),
						array( Lockout_Log::LOCKOUT_404 )
					),
					'user_agent_lockout' => Lockout_Log::count(
						strtotime( '-30 days' ),
						time(),
						array( Lockout_Log::LOCKOUT_UA )
					),
				),
				'notification_status'        => array(
					'login_lockout' => $login_lockout,
					'404_lockout'   => $nf_lockout,
					'ua_lockout'    => $ua_lockout,
				),
				'login_lockout_enabled'      => wd_di()->get( Login_Lockout::class )->enabled,
				'lockout_404_enabled'        => wd_di()->get( Notfound_Lockout::class )->enabled,
				'user_agent_lockout_enabled' => wd_di()->get( User_Agent_Lockout::class )->enabled,
				'global_ip_list_enabled'     => wd_di()->get( Global_Ip_Lockout::class )->enabled,
				'antibot_enabled'            => $antibot_service->frontend_is_enabled(),
				'antibot_mode'               => $antibot_service->frontend_mode(),
			),
			'audit'             => array(
				'last_event' => $audit['lastEvent'],
				'24_hours'   => $audit['dayCount'],
				'7_days'     => $audit['weekCount'],
				'30_days'    => $audit['monthCount'],
				'enabled'    => $audit_log->model->is_active(),
			),
			'advanced_tools'    => array(
				'security_headers'    => array(
					'sh_xframe'               => $model_sec_headers->sh_xframe,
					'sh_xss_protection'       => $model_sec_headers->sh_xss_protection,
					'sh_content_type_options' => $model_sec_headers->sh_content_type_options,
					'sh_strict_transport'     => $model_sec_headers->sh_strict_transport,
					'sh_referrer_policy'      => $model_sec_headers->sh_referrer_policy,
					'sh_feature_policy'       => $model_sec_headers->sh_feature_policy,
				),
				'mask_login'          => wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->is_active(),
				'google_recaptcha'    => array(
					'status' => wd_di()->get( \WP_Defender\Model\Setting\Recaptcha::class )->is_active(),
				),
				'password_protection' => array(
					'status' => wd_di()->get( \WP_Defender\Model\Setting\Password_Protection::class )->is_active(),
				),
			),
			'two_fa'            => array(
				'status'     => $two_fa->enabled,
				'lost_phone' => $two_fa->lost_phone,
			),
			'quarantined_files' => $quarantined_files,
		);

		wp_send_json_success(
			array( 'stats' => $ret )
		);
	}

	/**
	 * Synchronizes the data with the hub by making a POST request to the WPMUDEV API.
	 *
	 * @return void
	 */
	public function hub_sync() {
		$data = $this->build_stats_to_hub();
		$this->make_wpmu_request(
			WPMUDEV::API_HUB_SYNC,
			$data,
			array( 'method' => 'POST' )
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Placeholder for frontend data.
	 *
	 * @return void
	 */
	public function data_frontend() {
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Placeholder for importing data.
	 *
	 * @param  array $data  Data to import.
	 *
	 * @return void
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Display Onboard if the bool value 'true' and vice versa.
	 *
	 * @param  bool $is_show  Settings to display.
	 */
	public function set_onboarding_status( $is_show ) {
		$this->view_onboard = $is_show;
	}

	/**
	 * Retrieve the Onboard status.
	 *
	 * @return bool
	 */
	public function get_onboarding_status(): bool {
		return $this->view_onboard;
	}

	/**
	 * Activate/deactivate reCaptcha from HUB.
	 */
	public function manage_recaptcha() {
		$response = null;
		if ( class_exists( \WP_Defender\Model\Setting\Recaptcha::class ) ) {
			$settings = new \WP_Defender\Model\Setting\Recaptcha();
			$response = array();
			if ( true === $settings->enabled ) {
				$settings->enabled   = false;
				$response['enabled'] = false;
			} else {
				$settings->enabled   = true;
				$response['enabled'] = true;
			}
			$settings->save();
			// Track.
			if ( $this->is_tracking_active() ) {
				$this->track_feature_from_hub( ! $settings->enabled, 'Google reCAPTCHA' );
			}
		}
		wp_send_json_success( $response );
	}

	/**
	 * Activate/deactivate 2FA from HUB.
	 *
	 * @param array $params Request parameters.
	 */
	public function manage_2fa( array $params ) {
		$response = null;
		if ( class_exists( Two_Fa::class ) ) {
			$settings = wd_di()->get( Two_Fa::class );
			$response = array();
			if ( true === $settings->enabled ) {
				$settings->enabled   = false;
				$response['enabled'] = false;
			} else {
				$settings->enabled   = true;
				$response['enabled'] = true;
			}
			$settings->save();
			// Track.
			if ( $this->is_tracking_active() ) {
				// Only deactivation from the Hub.
				$this->track_feature_from_hub( false, 'Two-Factor Authentication' );
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Activate/deactivate Global IP list from HUB.
	 *
	 * @param  object $params  Request parameters.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function manage_global_ip_list( object $params ): void {
		if ( ! isset( $params->enable ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Missing parameter(s)', 'wpdef' ) )
			);
		}

		$response = null;
		if ( class_exists( Global_Ip_Lockout::class ) ) {
			$settings = wd_di()->get( Global_Ip_Lockout::class );

			$response = array();
			if ( true === $params->enable ) {
				$settings->enabled   = true;
				$response['enabled'] = true;
			} else {
				$settings->enabled   = false;
				$response['enabled'] = false;
			}
			$settings->save();
		}
		wp_send_json_success( $response );
	}

	/**
	 * Set Global IP list.
	 *
	 * @param  object $params  Request parameters.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function set_global_ips( object $params ): void {
		$global_ip_component = wd_di()->get( Global_IP::class );
		$result              = $global_ip_component->set_global_ip_list( (array) $params );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => implode( ' ', $result->get_error_messages() ),
				)
			);
		}

		wp_send_json_success(
			array( 'enabled' => $global_ip_component->is_global_ip_enabled() )
		);
	}

	/**
	 * Get recent quarantined files.
	 */
	public function get_quarantined_files(): void {
		if ( ! class_exists( 'WP_Defender\Component\Quarantine' ) ) {
			$result = array(
				'message' => defender_quarantine_pro_only(),
				'success' => false,
			);

			wp_send_json_error( $result );
		}

		$quarantine_obj = wd_di()->get( Quarantine::class );

		$quarantined_files = $quarantine_obj->hub_list();

		wp_send_json_success(
			array( 'quarantined_files' => $quarantined_files )
		);
	}

	/**
	 * Restores a quarantined file based on the provided parameters.
	 *
	 * @param  object $params  The parameters for restoring the quarantined file.
	 *                     Requires the 'id' property to be set.
	 *
	 * @return void
	 */
	public function restore_quarantined_file( object $params ): void {
		if ( ! class_exists( 'WP_Defender\Component\Quarantine' ) ) {
			$result = array(
				'message' => defender_quarantine_pro_only(),
				'success' => false,
			);

			wp_send_json_error( $result );
		}

		if ( isset( $params->id ) ) {
			$id = (int) $params->id;

			$quarantine_obj = wd_di()->get( Quarantine::class );

			$result = $quarantine_obj->restore_file( $id );

			if ( isset( $result['success'] ) && false === $result['success'] ) {
				wp_send_json_error( $result );
			}

			wp_send_json_success( $result );
		}

		wp_send_json_error(
			array(
				'message' => esc_html__( 'Missing parameter: id.', 'wpdef' ),
			)
		);
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @param  string $plugin  Path to the plugin file relative to the plugins directory.
	 *
	 * @return void
	 */
	public function intercept_deactivate( $plugin ) {
		if ( ! $this->is_tracking_active() ) {
			return;
		}

		// Only if Defender.
		if ( DEFENDER_PLUGIN_BASENAME !== $plugin ) {
			return;
		}

		$action = defender_get_data_from_request( 'action', 'r' );
		// Todo: this is a default value. Need to update it in the future.
		$triggered_from = 'Unknown';
		// Deactivated from WPMUDEV Dashboard.
		if ( 'wdp-project-deactivate' === $action ) {
			$triggered_from = 'Plugin deactivation - dashboard';
		} elseif ( 'deactivate' === $action ) {
			// Deactivated from WP plugins page.
			$triggered_from = 'Plugin deactivation - wpadmin';
		} elseif ( $this->is_hub_request() ) {
			$triggered_from = 'Plugin deactivation - hub';
		}

		// Send plugin deactivation event.
		$this->track_opt_toggle( false, $triggered_from );
	}

	/**
	 * Enable/Disable Antibot Functionality. Only for third party site.
	 *
	 * @param  object $params  Request parameters.
	 *
	 * @return void
	 * @since 4.11.0
	 */
	public function set_antibot_status( object $params ): void {
		if ( ! isset( $params->enable ) && ! isset( $params->mode ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Missing parameter(s)', 'wpdef' ) )
			);
		}

		if ( ! class_exists( Antibot_Global_Firewall_Setting::class ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Missing class', 'wpdef' ) )
			);
		}

		$is_updated       = false;
		$antibot_settings = wd_di()->get( Antibot_Global_Firewall_Setting::class );

		if ( isset( $params->mode ) ) {
			if ( ! in_array( $params->mode, Antibot_Global_Firewall_Setting::get_valid_modes(), true ) ) {
				wp_send_json_error(
					array( 'message' => esc_html__( 'Invalid mode', 'wpdef' ) )
				);
			}

			$old_mode = $antibot_settings->mode;
			$new_mode = $params->mode;
			if ( $old_mode !== $new_mode ) {
				$is_updated             = true;
				$antibot_settings->mode = $new_mode;
			}
		}

		if ( isset( $params->enable ) ) {
			$old_enabled = $antibot_settings->enabled;
			$new_enabled = (bool) $params->enable;
			if ( $old_enabled !== $new_enabled ) {
				$is_updated                = true;
				$antibot_settings->enabled = $new_enabled;

				// Track.
				if ( $this->is_tracking_active() ) {
					wd_di()->get( \WP_Defender\Helper\Analytics\Antibot::class )
						->track_antibot( $old_enabled, 'Hub' );
				}
			}
		}

		if ( $is_updated ) {
			$antibot_settings->save();

			/**
			 * Download and store the blocklist.
			 *
			 * @var Antibot_Global_Firewall $antibot_service
			 */
			$antibot_service = wd_di()->get( Antibot_Global_Firewall::class );
			if ( 'plugin' === $antibot_service->get_managed_by() && $antibot_service->is_enabled() ) {
				$antibot_service->download_and_store_blocklist();
			}
		}

		wp_send_json_success(
			array(
				'enabled' => $antibot_settings->enabled,
				'mode'    => $antibot_settings->mode,
			)
		);
	}
}