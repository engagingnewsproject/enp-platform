<?php
/**
 * Responsible for upgrading and managing configuration data.
 *
 * @package    WP_Defender\Component\Config
 */

namespace WP_Defender\Component\Config;

use WP_Defender\Component;
use WP_Defender\Model\Notification;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Model\Setting\Security_Headers;
use WP_Defender\Model\Setting\Login_Lockout as Model_Login_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout as Model_Ua_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout as Model_Notfound_Lockout;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;

/**
 * Handles the configuration data adapter.
 *
 * @since 2.4.0
 */
class Config_Adapter extends Component {

	/**
	 * Status of notification receiver.
	 *
	 * @var string
	 */
	public $status_recipient;

	/**
	 * Upgrade the structure of config data from older versions.
	 *
	 * @param  array $old_data  The configuration data from the older version.
	 *
	 * @return array
	 */
	public function upgrade( array $old_data ): array {
		$this->status_recipient = Notification::USER_SUBSCRIBED;

		return array(
			'security_tweaks'   => $this->update_security_tweaks( $old_data['security_tweaks'] ),
			'scan'              => empty( $old_data['scan'] ) ? array() : $this->update_scan( $old_data['scan'] ),
			'iplockout'         => empty( $old_data['iplockout'] )
				? array()
				: $this->update_ip_lockout( $old_data['iplockout'] ),
			// Empty data if Audit module is disabled.
			'audit'             => empty( $old_data['audit'] ) ? array() : $this->update_audit( $old_data['audit'] ),
			'two_factor'        => empty( $old_data['two_factor'] )
				? array()
				: $this->update_two_factor( $old_data['two_factor'] ),
			// Checks for empty values Mask Login and Security Headers inside methods.
			'mask_login'        => $this->update_mask_login( $old_data['mask_login'] ),
			'security_headers'  => $this->update_security_headers( $old_data['security_headers'] ),
			'settings'          => empty( $old_data['settings'] ) ? array() : $old_data['settings'],
			'blocklist_monitor' => empty( $old_data['blocklist_monitor'] ) ? array() : $old_data['blocklist_monitor'],
			'pwned_passwords'   => empty( $old_data['pwned_passwords'] ) ? array() : $old_data['pwned_passwords'],
		);
	}

	/**
	 * Convert key type from frequency to text.
	 * Attention: it's NOT translation lines.
	 *
	 * @param  int $freq  The frequency as a number.
	 *
	 * @return string
	 */
	private function number_frequency_to_text( int $freq ): string {
		switch ( $freq ) {
			case 1:
				$text = 'daily';
				break;
			case 7:
				$text = 'weekly';
				break;
			case 30:
			default:
				$text = 'monthly';
				break;
		}

		return $text;
	}

	/**
	 * Determines the frequency type based on the input, which can be either numeric or textual.
	 *
	 * @param  string|int $frequency_type  The frequency type which can be a number or a string.
	 *
	 * @return string
	 */
	private function frequency_type( $frequency_type ) {

		return is_numeric( $frequency_type )
			? $this->number_frequency_to_text( (int) $frequency_type )
			: $frequency_type;
	}

	/**
	 * Report instance uses the 'day' property if a frequency type is daily and 'day_n' if a frequency type is monthly.
	 *
	 * @param  string|int $frequency_type  The frequency type.
	 * @param  string     $report_day  The day of the week or month.
	 *
	 * @return array
	 */
	private function frequency_day( $frequency_type, $report_day ): array {
		$days = array(
			'day_n' => '1',
			'day'   => 'sunday',
		);
		if (
			( ( is_numeric( $frequency_type ) && 30 === (int) $frequency_type ) )
			|| 'monthly' === $frequency_type
		) {
			if ( empty( $report_day ) ) {
				return $days;
			}
			if ( is_numeric( $report_day ) ) {
				$days['day_n'] = $report_day;

				return $days;
			}
			// Otherwise, get a number of the first day of the month by the day of the week.
			$format        = sprintf( 'first %s of next month', $report_day );
			$days['day_n'] = wp_date( 'j', strtotime( $format ) );

			return $days;
		}
		// Otherwise, we get report day for daily or weekly.
		$days['day'] = $report_day;

		return $days;
	}

	/**
	 * Extracts subscriber information from the provided data array.
	 *
	 * @param  array $data  Data containing subscriber information.
	 *
	 * @return array
	 */
	private function get_subscribers( array $data ): array {
		$subscribers = array();
		if ( ! empty( $data ) ) {
			foreach ( $data as $receipt ) {
				$subscribers['out_house_recipients'][] = array(
					'name'   => $receipt['first_name'],
					'email'  => $receipt['email'],
					'status' => $this->status_recipient,
					'avatar' => get_avatar_url( $receipt['email'] ),
				);
			}
		}

		return $subscribers;
	}

	/**
	 * Updates the security tweaks configuration from the old format to the new format.
	 *
	 * @param  array $old_tweaks  The old security tweaks configuration.
	 *
	 * @return array
	 */
	public function update_security_tweaks( $old_tweaks ): array {
		$security_tweaks = array(
			'issues'              => $old_tweaks['issues'] ?? array(),
			'fixed'               => $old_tweaks['fixed'] ?? array(),
			'ignore'              => $old_tweaks['ignore'] ?? array(),
			'automate'            => isset( $old_tweaks['automate'] )
				// Sometimes string value from old version.
				? ( is_bool( $old_tweaks['automate'] ) ? $old_tweaks['automate'] : (bool) $old_tweaks['automate'] )
				: true,
			'notification'        => isset( $old_tweaks['notification'] )
				? ( $old_tweaks['notification'] ? 'enabled' : 'disabled' )
				: 'disabled',
			// Prepare from 'notification_repeat' to configs['reminder'] with default value 'weekly'.
			'notification_repeat' => empty( $old_tweaks['notification_repeat'] )
				? 'weekly'
				: ( $old_tweaks['notification_repeat'] ? 'daily' : 'weekly' ),
			'data'                => $old_tweaks['data'] ?? array(),
		);
		if ( isset( $old_tweaks['last_sent'] ) ) {
			$security_tweaks['last_sent'] = $old_tweaks['last_sent'];
		}

		$security_tweaks['subscribers'] = isset( $old_tweaks['receipts'] )
			? $this->get_subscribers( $old_tweaks['receipts'] )
			: array();

		return $security_tweaks;
	}

	/**
	 * Updates the scan configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old scan configuration.
	 *
	 * @return array
	 */
	public function update_scan( array $old_data ): array {
		$scan = array(
			'integrity_check'               => empty( $old_data['scan_core'] ) ? true : $old_data['scan_core'],
			'check_core'                    => empty( $old_data['check_core'] ) ? true : $old_data['check_core'],
			'check_plugins'                 => empty( $old_data['check_plugins'] ) ? false : $old_data['check_plugins'],
			'check_known_vuln'              => empty( $old_data['scan_vuln'] ) ? true : $old_data['scan_vuln'],
			'scan_malware'                  => empty( $old_data['scan_content'] ) ? false : $old_data['scan_content'],
			'filesize'                      => empty( $old_data['max_filesize'] ) ? 3 : $old_data['max_filesize'],
			// Should get bool value.
			'report'                        => isset( $old_data['report'] ) && $old_data['report'] ? 'enabled' : 'disabled',
			'always_send'                   => empty( $old_data['always_send'] ) ? false : $old_data['always_send'],
			'time'                          => empty( $old_data['time'] ) ? '4:00' : $old_data['time'],
			'frequency'                     => empty( $old_data['frequency'] )
				? 'weekly'
				: $this->frequency_type( $old_data['frequency'] ),
			// Should get bool value.
			'notification'                  => isset( $old_data['notification'] ) && $old_data['notification']
				? 'enabled'
				: 'disabled',
			'always_send_notification'      => empty( $old_data['always_send_notification'] )
				? false
				: $old_data['always_send_notification'],
			'error_send'                    => empty( $old_data['error_send'] ) ? false : $old_data['error_send'],
			'email_subject_issue_found'     => $old_data['email_subject_issue'] ?? '',
			'email_subject_issue_not_found' => $old_data['email_subject'] ?? '',
			'email_subject_error'           => $old_data['email_subject_error'] ?? '',
			'email_content_issue_found'     => $old_data['email_has_issue'] ?? '',
			'email_content_issue_not_found' => $old_data['email_all_ok'] ?? '',
			'email_content_error'           => $old_data['email_content_error'] ?? '',
			// since 2.7.0.
			'scheduled_scanning'            => false,
		);

		$scan['report_subscribers']       = empty( $old_data['recipients'] )
			? array()
			: $this->get_subscribers( $old_data['recipients'] );
		$scan['notification_subscribers'] = empty( $old_data['recipients_notification'] )
			? array()
			: $this->get_subscribers( $old_data['recipients_notification'] );
		// Todo: need the key 'last_report_sent'? It's no always in the unixtime format.
		if ( empty( $old_data['frequency'] ) ) {
			$scan['day']   = 'sunday';
			$scan['day_n'] = '1';

			return $scan;
		} else {
			return array_merge( $scan, $this->frequency_day( $old_data['frequency'], $old_data['day'] ) );
		}
	}

	/**
	 * Updates the IP lockout configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old IP lockout configuration.
	 *
	 * @return array
	 */
	public function update_ip_lockout( array $old_data ): array {
		$merged_bl_file_data = $old_data['detect_404_blacklist'] ?? '';
		// Merge blacklist file & filetype data.
		if ( isset( $old_data['detect_404_filetypes_blacklist'] ) && '' !== trim( $old_data['detect_404_filetypes_blacklist'] ) ) {
			$merged_bl_file_data .= PHP_EOL . $old_data['detect_404_filetypes_blacklist'];
		}
		$merged_wl_file_data = $old_data['detect_404_whitelist'] ?? '';
		// Merge whitelist file & filetype data.
		if ( isset( $old_data['detect_404_whitelist'] ) && '' !== trim( $old_data['detect_404_ignored_filetypes'] ) ) {
			$merged_wl_file_data .= PHP_EOL . $old_data['detect_404_ignored_filetypes'];
		}

		$default_login_lockout_values = ( new Model_Login_Lockout() )->get_default_values();
		$default_404_lockout_values   = ( new Model_Notfound_Lockout() )->get_default_values();
		$default_ip_lockout_values    = ( new Model_Blacklist_Lockout() )->get_default_values();
		$default_ua_lockout_values    = ( new Model_Ua_Lockout() )->get_default_values();

		$iplockout = array(
			'login_protection'                       => empty( $old_data['login_protection'] )
				? true : $old_data['login_protection'],
			'login_protection_login_attempt'         => empty( $old_data['login_protection_login_attempt'] )
				? '5' : $old_data['login_protection_login_attempt'],
			'login_protection_lockout_timeframe'     => empty( $old_data['login_protection_lockout_timeframe'] )
				? '300' : $old_data['login_protection_lockout_timeframe'],
			'login_protection_lockout_ban'           => empty( $old_data['login_protection_lockout_ban'] )
				? false : $old_data['login_protection_lockout_ban'],
			'login_protection_lockout_duration'      => empty( $old_data['login_protection_lockout_duration'] )
				? '4' : $old_data['login_protection_lockout_duration'],
			'login_protection_lockout_duration_unit' => empty( $old_data['login_protection_lockout_duration_unit'] )
				? 'hours' : $old_data['login_protection_lockout_duration_unit'],
			'login_protection_lockout_message'       => empty( $old_data['login_protection_lockout_message'] )
				? $default_login_lockout_values['message']
				: $old_data['login_protection_lockout_message'],
			'username_blacklist'                     => empty( $old_data['username_blacklist'] )
				? '' : $old_data['username_blacklist'],
			'detect_404'                             => empty( $old_data['detect_404'] )
				? true : $old_data['detect_404'],
			'detect_404_threshold'                   => empty( $old_data['detect_404_threshold'] )
				? '20' : $old_data['detect_404_threshold'],
			'detect_404_timeframe'                   => empty( $old_data['detect_404_timeframe'] )
				? '300' : $old_data['detect_404_timeframe'],
			'detect_404_lockout_ban'                 => empty( $old_data['detect_404_lockout_ban'] )
				? false : $old_data['detect_404_lockout_ban'],
			'detect_404_lockout_duration'            => empty( $old_data['detect_404_lockout_duration'] )
				? '4' : $old_data['detect_404_lockout_duration'],
			'detect_404_lockout_duration_unit'       => empty( $old_data['detect_404_lockout_duration_unit'] )
				? 'hours' : $old_data['detect_404_lockout_duration_unit'],
			'detect_404_lockout_message'             => empty( $old_data['detect_404_lockout_message'] )
				? $default_404_lockout_values['message']
				: $old_data['detect_404_lockout_message'],
			'detect_404_blacklist'                   => $merged_bl_file_data,
			'detect_404_whitelist'                   => $merged_wl_file_data,
			'detect_404_logged'                      => empty( $old_data['detect_404_logged'] )
				? true : $old_data['detect_404_logged'],
			'ip_blacklist'                           => empty( $old_data['ip_blacklist'] )
				? '' : $old_data['ip_blacklist'],
			'ip_whitelist'                           => empty( $old_data['ip_whitelist'] )
				? '' : $old_data['ip_whitelist'],
			'country_blacklist'                      => empty( $old_data['country_blacklist'] )
				? '' : $old_data['country_blacklist'],
			'country_whitelist'                      => empty( $old_data['country_whitelist'] )
				? '' : $old_data['country_whitelist'],
			'ip_lockout_message'                     => empty( $old_data['ip_lockout_message'] )
				? $default_ip_lockout_values['message']
				: $old_data['ip_lockout_message'],
			'login_lockout_notification'             => empty( $old_data['login_lockout_notification'] )
				? true : $old_data['login_lockout_notification'],
			'ip_lockout_notification'                => empty( $old_data['ip_lockout_notification'] )
				? true : $old_data['ip_lockout_notification'],
			'notification'                           => 'enabled',
			'cooldown_enabled'                       => empty( $old_data['cooldown_enabled'] )
				? false : $old_data['cooldown_enabled'],
			'cooldown_number_lockout'                => empty( $old_data['cooldown_number_lockout'] )
				? '3' : $old_data['cooldown_number_lockout'],
			'cooldown_period'                        => empty( $old_data['cooldown_period'] )
				? '24' : $old_data['cooldown_period'],
			'report'                                 => isset( $old_data['report'] ) && $old_data['report'] ? 'enabled' : 'disabled',
			'report_frequency'                       => empty( $old_data['report_frequency'] )
				? 'weekly'
				: $this->frequency_type( $old_data['report_frequency'] ),
			// Data for 'day' below.
			'report_time'                            => empty( $old_data['report_time'] )
				? '4:00' : $old_data['report_time'],
			'storage_days'                           => empty( $old_data['storage_days'] )
				? '180' : $old_data['storage_days'],
			'geoIP_db'                               => $old_data['geoIP_db'] ?? '',
			'ip_blocklist_cleanup_interval'          => empty( $old_data['ip_blocklist_cleanup_interval'] )
				? 'never' : $old_data['ip_blocklist_cleanup_interval'],
			// For UA Banning.
			'ua_banning_enabled'                     => $old_data['ua_banning_enabled'] ?? false,
			'ua_banning_message'                     => $old_data['ua_banning_message'] ?? $default_ua_lockout_values['message'],
			'ua_banning_blacklist'                   => $old_data['ua_banning_blacklist'] ?? $default_ua_lockout_values['blacklist'],
			'ua_banning_whitelist'                   => $old_data['ua_banning_whitelist'] ?? $default_ua_lockout_values['whitelist'],
			'ua_banning_empty_headers'               => $old_data['ua_banning_empty_headers'] ?? false,
			'maxmind_license_key'                    => $old_data['maxmind_license_key'] ?? '',
			// Global IP list.
			'global_ip_list'                         => $old_data['global_ip_list'] ?? false,
			'global_ip_list_blocklist_autosync'      => $old_data['global_ip_list_blocklist_autosync'] ?? false,
		);
		if ( isset( $old_data['lastReportSent'] ) && ! empty( $old_data['lastReportSent'] ) ) {
			$iplockout['last_sent'] = $old_data['lastReportSent'];
		}

		$iplockout['report_subscribers']       = empty( $old_data['report_receipts'] )
			? array()
			: $this->get_subscribers( $old_data['report_receipts'] );
		$iplockout['notification_subscribers'] = empty( $old_data['receipts'] )
			? array()
			: $this->get_subscribers( $old_data['receipts'] );

		if ( empty( $old_data['report_frequency'] ) ) {
			$iplockout['day']   = 'sunday';
			$iplockout['day_n'] = '1';

			return $iplockout;
		} else {
			return array_merge(
				$iplockout,
				$this->frequency_day( $old_data['report_frequency'], $old_data['report_day'] )
			);
		}
	}

	/**
	 * Updates the audit configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old audit configuration.
	 *
	 * @return array
	 */
	public function update_audit( array $old_data ): array {
		$audit = array(
			'enabled'      => is_bool( $old_data['enabled'] )
				? $old_data['enabled']
				: (bool) $old_data['enabled'],
			'report'       => isset( $old_data['notification'] ) && $old_data['notification'] ? 'enabled' : 'disabled',
			'frequency'    => empty( $old_data['frequency'] )
				? 'weekly'
				: $this->frequency_type( $old_data['frequency'] ),
			'time'         => empty( $old_data['time'] )
				? '4:00'
				: $old_data['time'],
			'storage_days' => empty( $old_data['storage_days'] )
				? '6 months'
				: $old_data['storage_days'],
		);
		if ( isset( $old_data['lastReportSent'] ) && ! empty( $old_data['lastReportSent'] ) ) {
			$audit['last_sent'] = $old_data['lastReportSent'];
		}

		$audit['subscribers'] = empty( $old_data['receipts'] )
			? array()
			: $this->get_subscribers( $old_data['receipts'] );

		if ( empty( $old_data['frequency'] ) ) {
			$audit['day']   = 'sunday';
			$audit['day_n'] = '1';

			return $audit;
		} else {
			return array_merge( $audit, $this->frequency_day( $old_data['frequency'], $old_data['day'] ) );
		}
	}

	/**
	 * Updates the two-factor authentication configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old two-factor authentication configuration.
	 *
	 * @return array
	 */
	public function update_two_factor( array $old_data ): array {

		return array(
			'enabled'             => $old_data['enabled'],
			'lost_phone'          => $old_data['lost_phone'],
			'force_auth'          => $old_data['force_auth'],
			'force_auth_mess'     => $old_data['force_auth_mess'],
			'user_roles'          => $old_data['user_roles'],
			'force_auth_roles'    => $old_data['force_auth_roles'],
			'custom_graphic'      => $old_data['custom_graphic'],
			'custom_graphic_type' => $old_data['custom_graphic_type'] ?? Two_Fa::CUSTOM_GRAPHIC_TYPE_UPLOAD,
			'custom_graphic_url'  => $old_data['custom_graphic_url'] ?? '',
			'custom_graphic_link' => $old_data['custom_graphic_link'] ?? '',
			'email_subject'       => $old_data['email_subject'] ?? '',
			'email_sender'        => $old_data['email_sender'] ?? '',
			'email_body'          => $old_data['email_body'] ?? '',
			'app_title'           => '',
		);
	}

	/**
	 * Updates the mask login configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old mask login configuration.
	 *
	 * @return array
	 */
	public function update_mask_login( array $old_data ): array {
		if ( empty( $old_data ) ) {
			// Sometimes migrated data is empty.
			return array(
				'mask_url'                 => '',
				'redirect_traffic'         => 'off',
				'redirect_traffic_url'     => '',
				'enabled'                  => false,
				'redirect_traffic_page_id' => 0,
			);
		} else {

			return array(
				'enabled'                  => $old_data['enabled'],
				'mask_url'                 => $old_data['mask_url'] ?? '',
				'redirect_traffic'         => $old_data['redirect_traffic'] ? 'custom_url' : 'off',
				'redirect_traffic_url'     => $old_data['redirect_traffic_url'] ?? '',
				'redirect_traffic_page_id' => 0,
			);
		}
	}

	/**
	 * Updates the security headers configuration from the old format to the new format.
	 *
	 * @param  array $old_data  The old security headers configuration.
	 *
	 * @return  array
	 * @since 2.5.0 Remove 'ALLOW-FROM' directive and move to 'sameorigin' by default.
	 * Leave 'sh_xframe_urls' for config migration.
	 */
	public function update_security_headers( array $old_data ): array {
		if ( empty( $old_data ) ) {
			// Sometimes migrated data is empty.
			$model_sec_headers = new Security_Headers();

			return array(
				'sh_xframe'                    => $model_sec_headers->sh_xframe,
				'sh_xframe_mode'               => $model_sec_headers->sh_xframe_mode,
				// Leave for migration to 2.5.1.
				'sh_xframe_urls'               => '',
				'sh_xss_protection'            => $model_sec_headers->sh_xss_protection,
				'sh_xss_protection_mode'       => $model_sec_headers->sh_xss_protection_mode,
				'sh_content_type_options'      => $model_sec_headers->sh_content_type_options,
				'sh_content_type_options_mode' => $model_sec_headers->sh_content_type_options_mode,
				'sh_strict_transport'          => $model_sec_headers->sh_strict_transport,
				'hsts_preload'                 => $model_sec_headers->hsts_preload,
				'include_subdomain'            => $model_sec_headers->include_subdomain,
				'hsts_cache_duration'          => $model_sec_headers->hsts_cache_duration,
				'sh_referrer_policy'           => $model_sec_headers->sh_referrer_policy,
				'sh_referrer_policy_mode'      => $model_sec_headers->sh_referrer_policy_mode,
				'sh_feature_policy'            => $model_sec_headers->sh_feature_policy,
				'sh_feature_policy_mode'       => $model_sec_headers->sh_feature_policy_mode,
				'sh_feature_policy_urls'       => $model_sec_headers->sh_feature_policy_urls,
			);
		} else {

			return array(
				'sh_xframe'                    => (bool) $old_data['sh_xframe'],
				'sh_xframe_mode'               => $old_data['sh_xframe_mode'],
				// Leave for migration to 2.5.1.
				'sh_xframe_urls'               => $old_data['sh_xframe_urls'] ?? '',
				'sh_xss_protection'            => (bool) $old_data['sh_xss_protection'],
				'sh_xss_protection_mode'       => $old_data['sh_xss_protection_mode'],
				'sh_content_type_options'      => (bool) $old_data['sh_content_type_options'],
				'sh_content_type_options_mode' => $old_data['sh_content_type_options_mode'],
				'sh_strict_transport'          => (bool) $old_data['sh_strict_transport'],
				'hsts_preload'                 => $old_data['hsts_preload'],
				'include_subdomain'            => $old_data['include_subdomain'],
				'hsts_cache_duration'          => $old_data['hsts_cache_duration'],
				'sh_referrer_policy'           => (bool) $old_data['sh_referrer_policy'],
				'sh_referrer_policy_mode'      => $old_data['sh_referrer_policy_mode'],
				'sh_feature_policy'            => (bool) $old_data['sh_feature_policy'],
				'sh_feature_policy_mode'       => $old_data['sh_feature_policy_mode'],
				'sh_feature_policy_urls'       => $old_data['sh_feature_policy_urls'],
			);
		}
	}
}