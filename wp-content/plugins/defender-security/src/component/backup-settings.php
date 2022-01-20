<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Model\Setting\Security_Tweaks as Model_Security_Tweaks;
use WP_Defender\Model\Setting\Scan as Model_Scan;
use WP_Defender\Model\Setting\Audit_Logging as Model_Audit_Logging;
use WP_Defender\Model\Setting\Firewall as Model_Firewall;
use WP_Defender\Model\Setting\Login_Lockout as Model_Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout as Model_Notfound_Lockout;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;
use WP_Defender\Model\Setting\Two_Fa as Model_Two_Fa;
use WP_Defender\Model\Setting\Mask_Login as Model_Mask_Login;
use WP_Defender\Model\Setting\Security_Headers as Model_Security_Headers;
use WP_Defender\Model\Setting\Main_Setting as Model_Main_Setting;
use WP_Defender\Model\Setting\Password_Protection as Model_Password_Protection;
use WP_Defender\Model\Setting\User_Agent_Lockout as Model_Ua_Lockout;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Controller\Security_Tweaks as Controller_Security_Tweaks;
use WP_Defender\Controller\Scan as Controller_Scan;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Controller\Firewall as Controller_Firewall;
use WP_Defender\Controller\Mask_Login as Controller_Mask_Login;
use WP_Defender\Controller\Blocklist_Monitor;
use WP_Defender\Controller\Password_Protection as Controller_Password_Protection;
use WP_Defender\Controller\UA_Lockout as Controller_Ua_Lockout;

class Backup_Settings extends Component {
	const KEY = 'defender_last_settings', INDEXER = 'defender_config_indexer';

	/**
	 * @param object $notification_object
	 *
	 * @return array
	 */
	public function change_subscriber_format( $notification_object ) {
		if (
			! is_object( $notification_object )
			&& ! is_array( $notification_object->in_house_recipients )
			&& ! is_array( $notification_object->out_house_recipients )
		) {
			return array();
		}
		if ( empty( $notification_object->in_house_recipients ) && empty( $notification_object->out_house_recipients ) ) {
			return array();
		}

		$subscribers = array();
		if ( ! empty( $notification_object->in_house_recipients ) ) {
			$subscribers['in_house_recipients'] = $notification_object->in_house_recipients;
		}
		if ( ! empty( $notification_object->out_house_recipients ) ) {
			$subscribers['out_house_recipients'] = $notification_object->out_house_recipients;
		}

		return $subscribers;
	}

	/**
	 * Gather settings from all modules.
	 *
	 * @return array
	 */
	public function gather_data() {
		$tweak_class = wd_di()->get( Controller_Security_Tweaks::class );
		$tweak_class->refresh_tweaks_status();
		$settings           = new Model_Security_Tweaks();
		$tweak_notification = new Tweak_Reminder();
		$security_tweaks    = array(
			'notification_repeat' => $tweak_notification->configs['reminder'],
			'subscribers'         => $this->change_subscriber_format( $tweak_notification ),
			'notification'        => $tweak_notification->status,
			'data'                => $settings->data,
			'fixed'               => $settings->fixed,
			'issues'              => $settings->issues,
			'ignore'              => $settings->ignore,
			'automate'            => $settings->automate,
		);
		$settings           = new Model_Scan();
		$scan_report        = new Malware_Report();
		$scan_notification  = new Malware_Notification();
		$scan               = array(
			'integrity_check'               => $settings->integrity_check,
			'check_core'                    => $settings->check_core,
			// Leave for migration from prev version to 2.5.0 and vice versa.
			'check_themes'                  => false,
			'check_plugins'                 => $settings->check_plugins,
			'check_known_vuln'              => $settings->check_known_vuln,
			'scan_malware'                  => $settings->scan_malware,
			'filesize'                      => $settings->filesize,
			'report'                        => $scan_report->status,
			'always_send'                   => $scan_report->configs['always_send'],
			'report_subscribers'            => $this->change_subscriber_format( $scan_report ),
			'day'                           => $scan_report->day,
			'day_n'                         => $scan_report->day_n,
			'time'                          => $scan_report->time,
			'frequency'                     => $scan_report->frequency,
			'dry_run'                       => $scan_report->dry_run,
			'notification'                  => $scan_notification->status,
			'always_send_notification'      => $scan_notification->configs['always_send'],
			'error_send'                    => $scan_notification->configs['error_send'],
			'notification_subscribers'      => $this->change_subscriber_format( $scan_notification ),
			'email_subject_issue_not_found' => $scan_notification->configs['template']['not_found']['subject'],
			'email_subject_issue_found'     => $scan_notification->configs['template']['found']['subject'],
			'email_subject_error'           => $scan_notification->configs['template']['error']['subject'],
			'email_content_issue_not_found' => $scan_notification->configs['template']['not_found']['body'],
			'email_content_issue_found'     => $scan_notification->configs['template']['found']['body'],
			'email_content_error'           => $scan_notification->configs['template']['error']['body'],
		);

		$is_pro = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
		if ( class_exists( Model_Audit_Logging::class ) ) {
			$settings     = new Model_Audit_Logging();
			$audit_report = new Audit_Report();
			$audit        = array(
				'enabled'      => $settings->is_active(),
				'report'       => $audit_report->status,
				'subscribers'  => $this->change_subscriber_format( $audit_report ),
				'frequency'    => $audit_report->frequency,
				'day'          => $audit_report->day,
				'day_n'        => $audit_report->day_n,
				'time'         => $audit_report->time,
				'dry_run'      => $audit_report->dry_run,
				'storage_days' => $settings->storage_days,
			);
			if ( ! $is_pro ) {
				$audit['enabled'] = false;
			}
		} else {
			$audit['enabled'] = false;
		}
		$settings_firewall    = new Model_Firewall();
		$settings_ll          = new Model_Login_Lockout();
		$settings_nl          = new Model_Notfound_Lockout();
		$settings_bl          = new Model_Blacklist_Lockout();
		$lockout_notification = new Firewall_Notification();
		$lockout_report       = new Firewall_Report();
		$ua_banning_model     = new Model_Ua_Lockout();
		$iplockout            = array(
			'login_protection'                       => $settings_ll->enabled,
			'login_protection_login_attempt'         => $settings_ll->attempt,
			'login_protection_lockout_timeframe'     => $settings_ll->timeframe,
			'login_protection_lockout_ban'           => $settings_ll->lockout_type,
			'login_protection_lockout_duration'      => $settings_ll->duration,
			'login_protection_lockout_duration_unit' => $settings_ll->duration_unit,
			'login_protection_lockout_message'       => $settings_ll->lockout_message,
			'username_blacklist'                     => $settings_ll->username_blacklist,
			'detect_404'                             => $settings_nl->enabled,
			'detect_404_threshold'                   => $settings_nl->attempt,
			'detect_404_timeframe'                   => $settings_nl->timeframe,
			'detect_404_lockout_ban'                 => $settings_nl->lockout_type,
			'detect_404_lockout_duration'            => $settings_nl->duration,
			'detect_404_lockout_duration_unit'       => $settings_nl->duration_unit,
			'detect_404_lockout_message'             => $settings_nl->lockout_message,
			'detect_404_blacklist'                   => $settings_nl->blacklist,
			'detect_404_whitelist'                   => $settings_nl->whitelist,
			'detect_404_logged'                      => $settings_nl->detect_logged,
			'ip_blacklist'                           => $settings_bl->ip_blacklist,
			'ip_whitelist'                           => $settings_bl->ip_whitelist,
			'country_blacklist'                      => $settings_bl->country_blacklist,
			'country_whitelist'                      => $settings_bl->country_whitelist,
			'ip_lockout_message'                     => $settings_bl->ip_lockout_message,
			'login_lockout_notification'             => $lockout_notification->configs['login_lockout'],
			'ip_lockout_notification'                => $lockout_notification->configs['nf_lockout'],
			'notification'                           => $lockout_notification->status,
			'notification_subscribers'               => $this->change_subscriber_format( $lockout_notification ),
			'cooldown_enabled'                       => $lockout_notification->configs['limit'],
			'cooldown_number_lockout'                => $lockout_notification->configs['threshold'],
			'cooldown_period'                        => $lockout_notification->configs['cool_off'],
			'report'                                 => $lockout_report->status,
			'report_subscribers'                     => $this->change_subscriber_format( $lockout_report ),
			'report_frequency'                       => $lockout_report->frequency,
			'day'                                    => $lockout_report->day,
			'day_n'                                  => $lockout_report->day_n,
			'report_time'                            => $lockout_report->time,
			'dry_run'                                => $lockout_report->dry_run,
			'storage_days'                           => $settings_firewall->storage_days,
			'geoIP_db'                               => $settings_bl->geodb_path,
			'ip_blocklist_cleanup_interval'          => $settings_firewall->ip_blocklist_cleanup_interval,
			// For UA Banning.
			'ua_banning_enabled'                     => $ua_banning_model->enabled,
			'ua_banning_message'                     => $ua_banning_model->message,
			'ua_banning_blacklist'                   => $ua_banning_model->blacklist,
			'ua_banning_whitelist'                   => $ua_banning_model->whitelist,
			'ua_banning_empty_headers'               => $ua_banning_model->empty_headers,
		);
		$settings_two_fa      = new Model_Two_Fa();
		$settings_mask_login  = new Model_Mask_Login();
		$advanced_tools       = array(
			'two_factor' => $settings_two_fa->export(),
			'mask_login' => $settings_mask_login->export(),
		);
		$settings_main        = new Model_Main_Setting();
		$main_settings        = $settings_main->export();
		$settings_sec_headers = new Model_Security_Headers();
		$security_headers     = $settings_sec_headers->export();
		$ret                  = array(
			'security_tweaks'  => $security_tweaks,
			'scan'             => $scan,
			'iplockout'        => $iplockout,
			'two_factor'       => $advanced_tools['two_factor'],
			'mask_login'       => $advanced_tools['mask_login'],
			'settings'         => $main_settings,
			'security_headers' => $security_headers,
		);
		if ( isset( $audit ) ) {
			$ret['audit'] = $audit;
		}
		// For Blocklist_Monitor.
		if ( $is_pro ) {
			$blocklist_monitor_class             = wd_di()->get( Blocklist_Monitor::class );
			$status                              = (string) $blocklist_monitor_class->get_status();
			$ret['blocklist_monitor']['enabled'] = '1' === $status;
			$ret['blocklist_monitor']['status']  = $status;
		} else {
			$ret['blocklist_monitor']['enabled'] = false;
		}
		// For Pwned passwords.
		$pwned_password_model                     = wd_di()->get( Model_Password_Protection::class );
		$ret['pwned_passwords']['enabled']        = $pwned_password_model->is_active();
		$ret['pwned_passwords']['user_roles']     = $pwned_password_model->user_roles;
		$ret['pwned_passwords']['custom_message'] = $pwned_password_model->pwned_actions['force_change_message'];

		return $ret;
	}

	/**
	 * @return array
	 */
	public function get_configs() {
		$keys    = get_site_option( self::INDEXER, false );
		$results = array();
		if ( empty( $keys ) ) {

			return $results;
		}

		foreach ( $keys as $key ) {
			$config = get_site_option( $key );

			if ( false === $config ) {
				$this->remove_index( $key );
			} else {
				$results[ $key ] = $config;
			}
		}

		return $results;
	}

	/**
	 * @param string $key
	 */
	public function make_config_active( $key ) {
		$configs = $this->get_configs();
		foreach ( $configs as $k => $config ) {
			if ( $k === $key ) {
				$config['is_active'] = true;
			} else {
				$config['is_active'] = false;
			}
			update_site_option( $k, $config );
		}
	}

	public function clear_configs() {
		$keys = get_site_option( self::INDEXER, false );
		if ( is_array( $keys ) && ! empty( $keys ) ) {
			foreach ( $keys as $key ) {
				delete_site_option( $key );
			}
		}
		delete_site_option( self::INDEXER );
		delete_site_option( self::KEY );
	}

	/**
	 * Create a default config.
	 */
	public function maybe_create_default_config() {
		$keys = get_site_option( self::INDEXER, false );
		if ( false === $keys ) {
			$key = 'wp_defender_config_default' . time();
			if ( ! get_site_option( $key ) ) {
				$this->create_basic_config();
			}
		}
	}

	/**
	 * @since 2.6.5 Reduce differences between Basic and Default security configs.
	*/
	private function create_basic_config() {
		// @since 2.6.5 Clear recipients.
		$default_recipients = array();
		// Init Tweaks class and refresh status.
		$tweak_class = wd_di()->get( Controller_Security_Tweaks::class );
		$tweak_class->refresh_tweaks_status();
		// All required models.
		$model_tweaks   = new Model_Security_Tweaks();
		$model_settings = new Model_Main_Setting();
		$user_roles     = array_keys( get_editable_roles() );
		// Default values.
		$default_scan_notification_values   = ( new Malware_Notification() )->get_default_values();
		$default_login_lockout_values       = ( new Model_Login_Lockout() )->get_default_values();
		$default_404_lockout_values         = ( new Model_Notfound_Lockout() )->get_default_values();
		$default_ip_lockout_values          = ( new Model_Blacklist_Lockout() )->get_default_values();
		$default_ua_lockout_values          = ( new Model_Ua_Lockout() )->get_default_values();
		$default_password_protection_values = ( new Model_Password_Protection() )->get_default_values();
		$default_2fa_values                 = ( new Model_Two_Fa() )->get_default_values();
		// Total data.
		$data = array(
			'security_tweaks'  => array(
				'notification_repeat' => 'weekly',
				'subscribers'         => $default_recipients,
				'notification'        => 'enabled',
				'automate'            => true,
				'data'                => $model_tweaks->data,
				'fixed'               => $model_tweaks->fixed,
				'issues'              => $model_tweaks->issues,
				'ignore'              => $model_tweaks->ignore,
			),
			'scan'             => array(
				'integrity_check'               => true,
				'check_core'                    => true,
				// Leave for migration from prev version to 2.5.0 and vice versa.
				'check_themes'                  => false,
				'check_plugins'                 => false,
				'check_known_vuln'              => true,
				'scan_malware'                  => false,
				'filesize'                      => 3,
				'report'                        => 'enabled',
				'always_send'                   => false,
				'report_subscribers'            => $default_recipients,
				'day'                           => 'sunday',
				'day_n'                         => '1',
				'time'                          => '4:00',
				'frequency'                     => 'weekly',
				'dry_run'                       => false,
				'notification'                  => 'enabled',
				'always_send_notification'      => false,
				'error_send'                    => false,
				'notification_subscribers'      => $default_recipients,
				'email_subject_issue_found'     => $default_scan_notification_values['subject_issue_found'],
				'email_subject_issue_not_found' => $default_scan_notification_values['subject_issue_not_found'],
				'email_subject_error'           => $default_scan_notification_values['subject_error'],
				'email_content_issue_found'     => $default_scan_notification_values['content_issue_found'],
				'email_content_issue_not_found' => $default_scan_notification_values['content_issue_not_found'],
				'email_content_error'           => $default_scan_notification_values['content_error'],
			),
			'iplockout'        => array(
				'login_protection'                       => true,
				'login_protection_login_attempt'         => '5',
				'login_protection_lockout_timeframe'     => '300',
				'login_protection_lockout_ban'           => 'timeframe',
				'login_protection_lockout_duration'      => '4',
				'login_protection_lockout_duration_unit' => 'hours',
				'login_protection_lockout_message'       => $default_login_lockout_values['message'],
				'username_blacklist'                     => 'admin',
				'detect_404'                             => true,
				'detect_404_threshold'                   => '20',
				'detect_404_timeframe'                   => '300',
				'detect_404_lockout_ban'                 => 'timeframe',
				'detect_404_lockout_duration'            => '4',
				'detect_404_lockout_duration_unit'       => 'hours',
				'detect_404_lockout_message'             => $default_404_lockout_values['message'],
				'detect_404_blacklist'                   => '',
				'detect_404_whitelist'                   => ".css\n.js\n.jpg\n.png\n.gif",
				'detect_404_logged'                      => true,
				'ip_blacklist'                           => '',
				// @since 2.6.5 Clear the current user IP.
				'ip_whitelist'                           => '',
				'country_blacklist'                      => '',
				'country_whitelist'                      => '',
				'ip_lockout_message'                     => $default_ip_lockout_values['message'],
				'login_lockout_notification'             => true,
				'ip_lockout_notification'                => true,
				'notification'                           => 'enabled',
				'notification_subscribers'               => $default_recipients,
				'cooldown_enabled'                       => false,
				'cooldown_number_lockout'                => '3',
				'cooldown_period'                        => '24',
				'report'                                 => 'enabled',
				'report_subscribers'                     => $default_recipients,
				'report_frequency'                       => 'weekly',
				'day'                                    => 'sunday',
				'day_n'                                  => '1',
				'report_time'                            => '4:00',
				'dry_run'                                => false,
				'storage_days'                           => '180',
				'geoIP_db'                               => '',
				'ip_blocklist_cleanup_interval'          => 'never',
				'ua_banning_enabled'                     => false,
				'ua_banning_message'                     => $default_ua_lockout_values['message'],
				'ua_banning_blacklist'                   => $default_ua_lockout_values['blacklist'],
				'ua_banning_whitelist'                   => $default_ua_lockout_values['whitelist'],
				'ua_banning_empty_headers'               => false,
			),
			'two_factor'       => array(
				// @since 2.6.5 Disabled module.
				'enabled'            => false,
				'lost_phone'         => true,
				'force_auth'         => false,
				'force_auth_mess'    => $default_2fa_values['message'],
				'user_roles'         => $user_roles,
				'force_auth_roles'   => array(),
				'custom_graphic'     => false,
				'custom_graphic_url' => '',
				'email_subject'      => $default_2fa_values['email_subject'],
				'email_sender'       => $default_2fa_values['email_sender'],
				'email_body'         => $default_2fa_values['email_body'],
				'app_title'          => $default_2fa_values['app_title'],
			),
			'mask_login'       => array(
				'mask_url'                 => '',
				'redirect_traffic'         => 'off',
				'redirect_traffic_url'     => '',
				'enabled'                  => false,
				'redirect_traffic_page_id' => 0,
			),
			'security_headers' => array(
				'sh_xframe'                    => true,
				'sh_xframe_mode'               => 'sameorigin',
				'sh_xframe_urls'               => '',
				'sh_xss_protection'            => true,
				'sh_xss_protection_mode'       => 'sanitize',
				'sh_content_type_options'      => true,
				'sh_content_type_options_mode' => 'nosniff',
				'sh_strict_transport'          => true,
				'hsts_preload'                 => false,
				'include_subdomain'            => false,
				'hsts_cache_duration'          => '30 days',
				'sh_referrer_policy'           => true,
				'sh_referrer_policy_mode'      => 'origin-when-cross-origin',
				'sh_feature_policy'            => true,
				'sh_feature_policy_mode'       => 'self',
				'sh_feature_policy_urls'       => '',
			),
			'settings'         => array(
				'uninstall_data'     => 'keep',
				'uninstall_settings' => 'preserve',
				'translate'          => $model_settings->translate,
				'usage_tracking'     => false,
				'high_contrast_mode' => false,
			),
			'pwned_passwords'  => array(
				'enabled'        => false,
				'user_roles'     => $user_roles,
				'custom_message' => $default_password_protection_values['message'],
			),
		);

		$is_pro = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
		if ( $is_pro ) {
			$data['audit']             = array(
				'enabled'      => true,
				'report'       => 'enabled',
				'subscribers'  => $default_recipients,
				'frequency'    => 'weekly',
				'day'          => 'sunday',
				'day_n'        => '1',
				'time'         => '4:00',
				'dry_run'      => false,
				'storage_days' => '6 months',
			);
			$blocklist_monitor_class   = wd_di()->get( Blocklist_Monitor::class );
			$data['blocklist_monitor'] = array(
				'enabled' => false,
				'status'  => $blocklist_monitor_class->get_status(),
			);
		} else {
			$data['audit']['enabled']             = false;
			$data['blocklist_monitor']['enabled'] = false;
		}

		$configs['configs']     = $data;
		$configs['strings']     = $this->create_default_module_strings( $data, $is_pro );
		$configs['name']        = __( 'Basic Config', 'wpdef' );
		$configs['description'] = __( 'Recommended default protection for every site', 'wpdef' );
		$configs['immortal']    = true;
		$key                    = 'wp_defender_config_default' . time();
		update_site_option( $key, $configs );
		$this->index_key( $key );
	}

	/**
	 * @param string $key
	 */
	public function index_key( $key ) {
		$keys         = get_site_option( self::INDEXER, false );
		$keys[ $key ] = $key;
		update_site_option( self::INDEXER, $keys );
	}

	/**
	 * @param string $key
	 */
	public function remove_index( $key ) {
		$keys = get_site_option( self::INDEXER, false );
		unset( $keys[ array_search( $key, $keys, true ) ] );
		update_site_option( self::INDEXER, $keys );
	}

	/**
	 * Backup the previous data before we process new version.
	 *
	 * @return array
	 */
	public function backup_data() {
		$data       = $this->get_prev_settings();
		$old_backup = get_site_option( self::KEY );
		if ( ! is_array( $old_backup ) ) {
			$old_backup = array();
		}
		if ( count( $old_backup ) > 20 ) {
			// Remove the oldest key.
			$old_backup = array_shift( $old_backup );
		}
		$version                               = get_site_option( 'wd_db_version' );
		$old_backup[ $version . '_' . time() ] = $data;
		update_site_option( self::KEY, $old_backup );

		return $data;
	}

	/**
	 * @param array $data
	 * @param bool  $is_migration
	 *
	 * @return bool
	 */
	public function restore_data( $data, $is_migration = false ) {
		$need_reauth = false;
		foreach ( $data as $module => $module_data ) {
			if ( ! is_array( $module_data ) ) {
				continue;
			}

			$controller = $this->module_to_controller( $module, true );
			// Return array of objects if the module is IP Lockout.
			if ( is_object( $controller ) || is_array( $controller ) ) {
				$is_pro = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
				foreach ( $module_data as &$value ) {
					if ( ! is_array( $value ) && ! filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ) {
						$value = str_replace( '{nl}', PHP_EOL, $value );
					}
				}
				// Import data.
				if ( 'iplockout' === $module ) {
					foreach ( $controller as $lockout_controller ) {
						$lockout_controller->import_data( $module_data );
					}
				} else {
					$controller->import_data( $module_data );
				}

				if ( 'security_tweaks' === $module ) {
					if ( ! $is_migration ) {
						// There is some tweaks that require re-login. If so, then we should output a message.
						// If this is combined with mask login, then we need to redirect to new URL.
						// The automate function should return this.
						$tweak_class = wd_di()->get( Controller_Security_Tweaks::class );
						$need_reauth = $tweak_class->automate( $module_data );
					}
					if ( ! empty( $module_data ) && isset( $module_data['notification'] ) ) {
						$tweak_notification = new Tweak_Reminder();
						if ( $tweak_notification->status !== $module_data['notification'] ) {
							$tweak_notification->status = $module_data['notification'];
						}

						if ( isset( $module_data['notification_repeat'] ) ) {
							// Temporary check for older versions.
							if ( is_bool( $module_data['notification_repeat'] ) ) {
								$tweak_notification->configs['reminder'] = $module_data['notification_repeat']
									? 'daily'
									: 'weekly';
							} elseif (
								is_string( $module_data['notification_repeat'] )
								&& in_array( $module_data['notification_repeat'], array( 'daily', 'weekly', 'monthly' ), true )
							) {
								$tweak_notification->configs['reminder'] = $module_data['notification_repeat'];
							} else {
								$tweak_notification->configs['reminder'] = 'weekly';
							}
						} else {
							$tweak_notification->configs['reminder'] = 'weekly';
						}
						if ( ! empty( $module_data['subscribers'] ) ) {
							// Reset all recipients before.
							$tweak_notification->in_house_recipients  = array();
							$tweak_notification->out_house_recipients = array();
							foreach ( $module_data['subscribers'] as $key => $subscribers ) {
								$tweak_notification->$key = $subscribers;
							}
						}
						if ( isset( $module_data['last_sent'] ) ) {
							$tweak_notification->last_sent = $module_data['last_sent'];
						}
						$tweak_notification->save();
					}
				} elseif ( 'scan' === $module ) {
					$scan_notification = new Malware_Notification();
					$scan_report       = new Malware_Report();
					if ( ! empty( $module_data ) ) {
						// For Scan notification.
						if ( isset( $module_data['notification'] ) ) {
							if ( $scan_notification->status !== $module_data['notification'] ) {
								$scan_notification->status = $module_data['notification'];
							}
							if ( isset( $module_data['always_send_notification'] ) ) {
								$scan_notification->configs['always_send'] = $module_data['always_send_notification'];
							}
							if ( isset( $module_data['error_send'] ) ) {
								$scan_notification->configs['error_send'] = $module_data['error_send'];
							}
							if ( ! empty( $module_data['email_subject_issue_found'] ) ) {
								$scan_notification->configs['template']['found']['subject'] = $module_data['email_subject_issue_found'];
							}
							if ( ! empty( $module_data['email_subject_issue_not_found'] ) ) {
								$scan_notification->configs['template']['not_found']['subject'] = $module_data['email_subject_issue_not_found'];
							}
							if ( ! empty( $module_data['email_subject_error'] ) ) {
								$scan_notification->configs['template']['error']['subject'] = $module_data['email_subject_error'];
							}
							if ( ! empty( $module_data['email_content_issue_found'] ) ) {
								$scan_notification->configs['template']['found']['body'] = $module_data['email_content_issue_found'];
							}
							if ( ! empty( $module_data['email_content_issue_not_found'] ) ) {
								$scan_notification->configs['template']['not_found']['body'] = $module_data['email_content_issue_not_found'];
							}
							if ( ! empty( $module_data['email_content_error'] ) ) {
								$scan_notification->configs['template']['error']['body'] = $module_data['email_content_error'];
							}
							if ( ! empty( $module_data['notification_subscribers'] ) ) {
								// Reset all recipients before.
								$scan_notification->in_house_recipients  = array();
								$scan_notification->out_house_recipients = array();
								foreach ( $module_data['notification_subscribers'] as $key => $subscribers ) {
									$scan_notification->$key = $subscribers;
								}
							}
							$scan_notification->save();
						}
						// For Scan report.
						if (
							$is_pro
							&& isset( $module_data['report'] )
							&& $scan_report->status !== $module_data['report']
						) {
							$scan_report->status = $module_data['report'];
						}
						if ( isset( $module_data['day'] ) ) {
							$scan_report->day = $module_data['day'];
						}
						if ( isset( $module_data['frequency'] ) ) {
							$scan_report->frequency = $module_data['frequency'];
						}
						if ( isset( $module_data['day_n'] ) ) {
							$scan_report->day_n = $module_data['day_n'];
						}
						if ( isset( $module_data['time'] ) ) {
							$scan_report->time = $module_data['time'];
						}
						if ( isset( $module_data['always_send'] ) ) {
							$scan_report->configs['always_send'] = $module_data['always_send'];
						}
						if ( ! empty( $module_data['report_subscribers'] ) ) {
							// Reset all recipients before.
							$scan_report->in_house_recipients  = array();
							$scan_report->out_house_recipients = array();
							foreach ( $module_data['report_subscribers'] as $key => $subscribers ) {
								$scan_report->$key = $subscribers;
							}
						}
						if ( isset( $module_data['dry_run'] ) ) {
							$scan_report->dry_run = $module_data['dry_run'];
						}
						$scan_report->save();
					} else {
						// Default data for scan notification.
						$scan_notification->status  = 'disabled';
						$scan_notification->dry_run = false;
						$scan_notification->save();
						// For scan report.
						$scan_report->status    = 'disabled';
						$scan_report->dry_run   = false;
						$scan_report->frequency = 'weekly';
						$scan_report->day_n     = '1';
						$scan_report->day       = 'sunday';
						$scan_report->time      = '4:00';
						$scan_report->save();
					}
				} elseif ( 'iplockout' === $module ) {
					if ( ! empty( $module_data ) ) {
						// Get string values for notification & report.
						if ( isset( $module_data['notification'] ) ) {
							$lockout_notification = new Firewall_Notification();
							if ( $lockout_notification->status !== $module_data['notification'] ) {
								$lockout_notification->status = $module_data['notification'];
							}
							if ( isset( $module_data['login_lockout_notification'] ) ) {
								$lockout_notification->configs['login_lockout'] = $module_data['login_lockout_notification'];
							}
							if ( isset( $module_data['ip_lockout_notification'] ) ) {
								$lockout_notification->configs['nf_lockout'] = $module_data['ip_lockout_notification'];
							}
							if ( isset( $module_data['cooldown_enabled'] ) ) {
								$lockout_notification->configs['limit'] = $module_data['cooldown_enabled'];
							}
							if ( isset( $module_data['cooldown_number_lockout'] ) ) {
								$lockout_notification->configs['threshold'] = $module_data['cooldown_number_lockout'];
							}
							if ( isset( $module_data['cooldown_period'] ) ) {
								$lockout_notification->configs['cool_off'] = $module_data['cooldown_period'];
							}
							if ( ! empty( $module_data['notification_subscribers'] ) ) {
								//Reset all recipients before
								$lockout_notification->in_house_recipients  = array();
								$lockout_notification->out_house_recipients = array();
								foreach ( $module_data['notification_subscribers'] as $key => $subscribers ) {
									$lockout_notification->$key = $subscribers;
								}
							}

							$lockout_notification->save();
						}
						if ( $is_pro && isset( $module_data['report'] ) ) {
							$lockout_report = new Firewall_Report();
							if ( $lockout_report->status !== $module_data['report'] ) {
								$lockout_report->status = $module_data['report'];
							}
							if ( isset( $module_data['day'] ) ) {
								$lockout_report->day = $module_data['day'];
							}
							if ( isset( $module_data['report_frequency'] ) ) {
								$lockout_report->frequency = $module_data['report_frequency'];
							}
							if ( isset( $module_data['day_n'] ) ) {
								$lockout_report->day_n = $module_data['day_n'];
							}
							if ( isset( $module_data['report_time'] ) ) {
								$lockout_report->time = $module_data['report_time'];
							}
							if ( ! empty( $module_data['report_subscribers'] ) ) {
								// Reset all recipients before.
								$lockout_report->in_house_recipients  = array();
								$lockout_report->out_house_recipients = array();
								foreach ( $module_data['report_subscribers'] as $key => $subscribers ) {
									$lockout_report->$key = $subscribers;
								}
							}
							if ( isset( $module_data['last_sent'] ) ) {
								$lockout_report->last_sent = $module_data['last_sent'];
							}
							if ( isset( $module_data['dry_run'] ) ) {
								$lockout_report->dry_run = $module_data['dry_run'];
							}
							$lockout_report->save();
						}
						// For UA Banning.
						if ( isset( $module_data['ua_banning_enabled'] ) ) {
							$model_ua_lockout          = new Model_Ua_Lockout();
							$model_ua_lockout->enabled = (bool) $module_data['ua_banning_enabled'];
							if ( isset( $module_data['ua_banning_message'] ) ) {
								$model_ua_lockout->message = $module_data['ua_banning_message'];
							}
							if ( isset( $module_data['ua_banning_blacklist'] ) ) {
								$model_ua_lockout->blacklist = $module_data['ua_banning_blacklist'];
							}
							if ( isset( $module_data['ua_banning_whitelist'] ) ) {
								$model_ua_lockout->whitelist = $module_data['ua_banning_whitelist'];
							}
							if ( isset( $module_data['ua_banning_empty_headers'] ) ) {
								$model_ua_lockout->empty_headers = (bool) $module_data['ua_banning_empty_headers'];
							}
							$model_ua_lockout->save();
						}
					} else {
						// Default data for lockout notification.
						$lockout_notification          = new Firewall_Notification();
						$lockout_notification->status  = 'disabled';
						$lockout_notification->dry_run = false;
						$lockout_notification->configs = array(
							'login_lockout' => false,
							'nf_lockout'    => false,
							'limit'         => false,
							'threshold'     => 3,
							'cool_off'      => 24,
						);
						$lockout_notification->save();
						// For lockout report.
						$lockout_report            = new Firewall_Report();
						$lockout_report->status    = 'disabled';
						$lockout_report->dry_run   = false;
						$lockout_report->frequency = 'weekly';
						$lockout_report->day_n     = '1';
						$lockout_report->day       = 'sunday';
						$lockout_report->time      = '4:00';
						$lockout_report->save();
						// For UA banning.
						$model_ua_lockout                = new Model_Ua_Lockout();
						$default_ua_values               = $model_ua_lockout->get_default_values();
						$model_ua_lockout->enabled       = false;
						$model_ua_lockout->message       = $default_ua_values['message'];
						$model_ua_lockout->blacklist     = $default_ua_values['blacklist'];
						$model_ua_lockout->whitelist     = $default_ua_values['whitelist'];
						$model_ua_lockout->empty_headers = false;
						$model_ua_lockout->save();
					}
				} elseif ( 'audit' === $module ) {
					$audit_report = new Audit_Report();
					if ( ! empty( $module_data ) ) {
						if (
							$is_pro
							&& isset( $module_data['report'] )
							&& $audit_report->status !== $module_data['report']
						) {
							$audit_report->status = $module_data['report'];
						}
						if ( isset( $module_data['frequency'] ) ) {
							$audit_report->frequency = $module_data['frequency'];
						}
						if ( isset( $module_data['day_n'] ) ) {
							$audit_report->day_n = $module_data['day_n'];
						}
						if ( isset( $module_data['day'] ) ) {
							$audit_report->day = $module_data['day'];
						}
						if ( isset( $module_data['time'] ) ) {
							$audit_report->time = $module_data['time'];
						}
						if ( ! empty( $module_data['subscribers'] ) ) {
							// Reset all recipients before.
							$audit_report->in_house_recipients  = array();
							$audit_report->out_house_recipients = array();
							foreach ( $module_data['subscribers'] as $key => $subscribers ) {
								$audit_report->$key = $subscribers;
							}
						}
						if ( isset( $module_data['last_sent'] ) ) {
							$audit_report->last_sent = $module_data['last_sent'];
						}
						if ( isset( $module_data['dry_run'] ) ) {
							$audit_report->dry_run = $module_data['dry_run'];
						}
					} else {
						// Default data for audit settings.
						$audit_settings               = new Model_Audit_Logging();
						$audit_settings->enabled      = false;
						$audit_settings->storage_days = '6 months';
						$audit_settings->save();
						// For audit report.
						$audit_report->status    = 'disabled';
						$audit_report->dry_run   = false;
						$audit_report->frequency = 'weekly';
						$audit_report->day_n     = '1';
						$audit_report->day       = 'sunday';
						$audit_report->time      = '4:00';
					}

					$audit_report->save();
					/**
					 * If 'blocklist_monitor' module is activated, 'enabled' set as true.
					*/
				} elseif (
					'blocklist_monitor' === $module
					&& $is_pro
					&& isset( $module_data['status'] )
				) {
					( new Blocklist_Monitor() )->change_status( $module_data['status'] );
				} elseif (
					'pwned_passwords' === $module
					&& isset( $module_data['custom_message'] )
				) {
					$model_pwned                                        = new Model_Password_Protection();
					$model_pwned->pwned_actions['force_change_message'] = $module_data['custom_message'];
					$model_pwned->save();
				}
			}
		}
		// We should disable quick setup.
		update_site_option( 'wp_defender_is_activated', 1 );

		return $need_reauth;
	}

	/**
	 * Parse current data for import.
	 * @param null|array $configs
	 *
	 * @return array
	 */
	public function parse_data_for_import( $configs = null ) {
		if ( empty( $configs ) ) {
			$configs = $this->gather_data();
		}
		$strings = array();
		foreach ( $configs as $module => $module_data ) {
			$controller = $this->module_to_controller( $module, false );
			if ( ! is_object( $controller ) ) {
				// In free, when audit not present.
				$strings[ $module ][] = sprintf(
					/* translators: %s: Html for Pro-tag. */
					__( 'Inactive %s', 'wpdef' ),
					'<span class="sui-tag sui-tag-pro">Pro</span>'
				);
				continue;
			}
			$strings[ $module ] = $controller->export_strings();
		}

		return array(
			'configs' => $configs,
			'strings' => $strings,
		);
	}

	public function module_to_controller( $module, $all_firewall_lockouts = false ) {
		switch ( $module ) {
			case 'security_tweaks':
				return new Controller_Security_Tweaks();
			case 'scan':
				return new Controller_Scan();
			case 'audit':
				if ( class_exists( Audit_Logging::class ) ) {
					return new Audit_Logging();
				}
				break;
			case 'iplockout':
				if ( $all_firewall_lockouts ) {
					return array(
						new Controller_Firewall(),
						new \WP_Defender\Controller\Login_Lockout(),
						new \WP_Defender\Controller\Nf_Lockout(),
						new \WP_Defender\Controller\Blacklist(),
						new Controller_Ua_Lockout(),
					);
				} else {
					return new Controller_Firewall();
				}
			case 'settings':
				return new \WP_Defender\Controller\Main_Setting();
			case 'two_factor':
				return new Two_Factor();
			case 'mask_login':
				return new Controller_Mask_Login();
			case 'security_headers':
				return new \WP_Defender\Controller\Security_Headers();
			case 'blocklist_monitor':
				return new Blocklist_Monitor();
			case 'pwned_passwords':
				return new Controller_Password_Protection();
			default:
				break;
		}
	}

	/**
	 * @param array $configs
	 *
	 * @return bool
	 */
	public function check_for_new_structure( $configs ) {

		return array_key_exists( 'subscribers', $configs['security_tweaks'] );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function verify_config_data( $data ) {
		if ( ! isset( $data['name'], $data['configs'], $data['strings'] )
			|| empty( $data['name'] ) || empty( $data['strings'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Import module 'strings'. Use during the import of configs and when upgrading the version.
	 * @param array $data
	 *
	 * @return array
	 */
	public function import_module_strings( $data ) {
		if ( empty( $data['strings'] ) ) {

			return array();
		}
		$is_pro = ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro();
		foreach ( $data['configs'] as $key => $config ) {
			if (
				'security_tweaks' === $key
				&& 'enabled' === $config['notification']
				&& 1 === count( $data['strings']['security_tweaks'] )
			) {
				$data['strings']['security_tweaks'][] = __( 'Email notifications active', 'wpdef' );
			} elseif ( 'scan' === $key ) {
				$data['strings']['scan'] = wd_di()->get( Controller_Scan::class )->config_strings( $config, $is_pro );
			} elseif ( 'iplockout' === $key ) {
				$data['strings']['iplockout'] = wd_di()->get( Controller_Firewall::class )->config_strings( $config, $is_pro );
			} elseif ( 'audit' === $key ) {
				$data['strings']['audit'] = wd_di()->get( Audit_Logging::class )->config_strings( $config, $is_pro );
			} elseif ( 'blocklist_monitor' === $key ) {
				$data['strings']['blocklist_monitor'] = $this->format_blocklist_monitor_strings( $config, $is_pro );
			} elseif ( 'pwned_passwords' === $key ) {
				$data['strings']['pwned_passwords'] = wd_di()->get( Controller_Password_Protection::class )->config_strings( $config, $is_pro );
			}
		}

		return $data['strings'];
	}

	/**
	 * Import module 'strings' for the Basic config.
	 * @param array $configs
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function create_default_module_strings( $configs, $is_pro ) {
		$strings = array();
		foreach ( $configs as $key => $config ) {
			if ( 'security_tweaks' === $key ) {
				$strings['security_tweaks'] = wd_di()->get( Controller_Security_Tweaks::class )->config_strings( $config, $is_pro );
			} elseif ( 'scan' === $key ) {
				$strings['scan'] = wd_di()->get( Controller_Scan::class )->config_strings( $config, $is_pro );
			} elseif ( 'iplockout' === $key ) {
				$strings['iplockout'] = wd_di()->get( Controller_Firewall::class )->config_strings( $config, $is_pro );
			} elseif ( 'audit' === $key ) {
				$strings['audit'] = wd_di()->get( Audit_Logging::class )->config_strings( $config, $is_pro );
			} elseif ( 'two_factor' === $key ) {
				$strings['two_factor'] = __( 'Inactive', 'wpdef' );
			} elseif ( 'mask_login' === $key ) {
				$strings['mask_login'] = __( 'Inactive', 'wpdef' );
			} elseif ( 'security_headers' === $key ) {
				$strings['security_headers'][] = __( 'Active', 'wpdef' );
			} elseif ( 'blocklist_monitor' === $key ) {
				$strings['blocklist_monitor'] = wd_di()->get( Blocklist_Monitor::class )->config_strings( $config, $is_pro );
			} elseif ( 'pwned_passwords' === $key ) {
				$strings['pwned_passwords'] = __( 'Inactive', 'wpdef' );
			}
		}

		return $strings;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	private function get_decoded_settings( $key ) {
		$data = get_site_option( $key );
		if ( $data && is_string( $data ) ) {
			$decoded_data = json_decode( $data, true );
			if ( is_array( $decoded_data ) ) {
				return $decoded_data;
			}
		}

		return array();
	}

	/**
	 * Get settings of previous version.
	 *
	 * @return array
	 */
	private function get_prev_settings() {
		if ( ( new \WP_Defender\Behavior\WPMUDEV() )->is_pro() ) {
			$status         = (string) wd_di()->get( Blocklist_Monitor::class )->get_status();
			$arr['enabled'] = '1' === $status;
			$arr['status']  = $status;
		} else {
			$arr['enabled'] = false;
		}

		return array(
			// Different keys.
			'security_tweaks'   => $this->get_decoded_settings( 'wd_hardener_settings' ),
			'iplockout'         => $this->get_decoded_settings( 'wd_lockdown_settings' ),
			// Identical keys.
			'scan'              => $this->get_decoded_settings( 'wd_scan_settings' ),
			'audit'             => $this->get_decoded_settings( 'wd_audit_settings' ),
			'two_factor'        => $this->get_decoded_settings( 'wd_2auth_settings' ),
			'mask_login'        => $this->get_decoded_settings( 'wd_masking_login_settings' ),
			'security_headers'  => $this->get_decoded_settings( 'wd_security_headers_settings' ),
			'settings'          => $this->get_decoded_settings( 'wd_main_settings' ),
			'blocklist_monitor' => $arr,
		);
	}

	/**
	 * @param string $module
	 * @return string
	 */
	private function module_to_name( $module ) {
		switch ( $module ) {
			case 'security_tweaks':
				return __( 'Security Recommendations', 'wpdef' );
			case 'scan':
				return __( 'Malware Scanning', 'wpdef' );
			case 'audit':
				return __( 'Audit Logging', 'wpdef' );
			case 'iplockout':
				return __( 'Firewall', 'wpdef' );
			case 'settings':
				return __( 'Settings', 'wpdef' );
			case 'two_factor':
				return __( '2FA', 'wpdef' );
			case 'mask_login':
				return __( 'Mask Login Area', 'wpdef' );
			case 'security_headers':
				return __( 'Security Headers', 'wpdef' );
			case 'blocklist_monitor':
				return __( 'Blocklist Monitor', 'wpdef' );
			case 'pwned_passwords':
				return __( 'Pwned Passwords', 'wpdef' );
			default:
				break;
		}
	}

	/**
	 * @param string $module
	 * @return array|void
	 */
	public function model_labels( $module ) {
		switch ( $module ) {
			case 'security_tweaks':
				return array_merge(
					( new Model_Security_Tweaks() )->labels(),
					( new Tweak_Reminder() )->labels()
				);
			case 'scan':
				return array_merge(
					( new Model_Scan() )->labels(),
					( new Malware_Notification() )->labels(),
					( new Malware_Report() )->labels()
				);
			case 'audit':
				if ( class_exists( Model_Audit_Logging::class ) ) {
					return array_merge(
						( new Model_Audit_Logging() )->labels(),
						( new Audit_Report() )->labels()
					);
				}
				break;
			case 'iplockout':
				return array_merge(
					( new Model_Firewall() )->labels(),
					( new Model_Login_Lockout() )->labels(),
					( new Model_Notfound_Lockout() )->labels(),
					( new Model_Blacklist_Lockout() )->labels(),
					( new Firewall_Notification() )->labels(),
					( new Firewall_Report() )->labels(),
					( new Model_Ua_Lockout() )->labels()
				);
			case 'settings':
				return ( new Model_Main_Setting() )->labels();
			case 'two_factor':
				return ( new Model_Two_Fa() )->labels();
			case 'mask_login':
				return ( new Model_Mask_Login() )->labels();
			case 'security_headers':
				return ( new Model_Security_Headers() )->labels();
			case 'blocklist_monitor':
				// Separate method is not for this model.
				return ( new Blocklist_Monitor() )->labels();
			case 'pwned_passwords':
				return ( new Model_Password_Protection() )->labels();
			default:
				break;
		}
	}

	/**
	 * @return array
	 */
	public function parse_data_for_hub() {
		$configs = $this->gather_data();
		$labels  = array();
		$strings = array();
		foreach ( $configs as $module => $module_data ) {
			$model_labels = $this->model_labels( $module );

			if ( is_array( $module_data ) && is_array( $model_labels ) ) {
				$labels[ $module ]['name'] = $this->module_to_name( $module );
				foreach ( $module_data as $key => $value ) {
					if ( in_array( $key, array( 'geoIP_db', 'geodb_path' ), true ) ) {
						continue;
					}

					if ( array_key_exists( $key, $model_labels ) ) {
						$labels[ $module ]['value'][ $key ] = array(
							'name'  => $model_labels[ $key ],
							'value' => $value,
						);
					}
				}

				$controller = $this->module_to_controller( $module, false );
				if ( ! is_object( $controller ) ) {
					// In free, when audit not present.
					$strings[ $module ] = sprintf(
					/* translators: %s: Html for Pro-tag. */
						__( 'Inactive %s', 'wpdef' ),
						'<span class="sui-tag sui-tag-pro">Pro</span>'
					);
					continue;
				}
				$strings[ $module ] = $controller->export_strings();
			}
		}

		return array(
			'configs' => $configs,
			'labels'  => $labels,
			'strings' => $strings,
		);
	}

	/**
	 * Format strings of Blocklist Monitor config.
	 *
	 * @param array $config Saved config.
	 * @param bool  $is_pro User membership status.
	 *
	 * @return array
	 */
	private function format_blocklist_monitor_strings( $config, $is_pro ) {
		// If Blocklist Monitor is enable.
		if ( isset( $config['status'] ) && '1' === (string) $config['status'] ) {
			if ( $is_pro ) {
				$monitor = array( __( 'Active', 'wpdef' ) );
			} else {
				$monitor = array(
					sprintf(
						/* translators: Status of blocklist monitor */
						__( 'Active %s', 'wpdef' ),
						'<span class="sui-tag sui-tag-pro">Pro</span>'
					),
				);
			}
		} else {
			if ( $is_pro ) {
				$monitor = array( __( 'Inactive', 'wpdef' ) );
			} else {
				$monitor = array(
					sprintf(
						/* translators: Status of blocklist monitor */
						__( 'Inactive %s', 'wpdef' ),
						'<span class="sui-tag sui-tag-pro">Pro</span>'
					),
				);
			}
		}

		return $monitor;
	}

	/**
	 * Generate labels from config.
	 *
	 * @param $configs
	 *
	 * @return array
	 */
	public function prepare_config_labels( $configs ) {
		$labels = array();

		foreach ( $configs as $module => $module_data ) {
			$model_labels = $this->model_labels( $module );

			if ( is_array( $module_data ) && is_array( $model_labels ) ) {
				$labels[ $module ]['name'] = $this->module_to_name( $module );
				foreach ( $module_data as $key => $value ) {
					if ( in_array( $key, array( 'geoIP_db', 'geodb_path' ), true ) ) {
						continue;
					}

					if ( array_key_exists( $key, $model_labels ) ) {
						$labels[ $module ]['value'][ $key ] = array(
							'name'  => $model_labels[ $key ],
							'value' => $value,
						);
					}
				}
			}
		}

		return $labels;
	}
}
