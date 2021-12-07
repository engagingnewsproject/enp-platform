<?php

namespace WP_Defender\Behavior;

use Calotes\Component\Behavior;
use WP_Defender\Controller\Firewall;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Notification;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Mask_Login;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Formats;

/**
 * This class contains everything relate to WPMUDEV.
 * Class WPMUDEV
 * @package WP_Defender\Behavior
 * @since 2.2
 */
class WPMUDEV extends Behavior {
	use IO, Formats;

	const API_SCAN_SIGNATURE  = 'yara_scan', API_SCAN_KNOWN_VULN = 'known_vulnerability';
	const API_AUDIT           = 'audit_logging', API_AUDIT_ADD = 'audit_logging_add';
	const API_BLACKLIST       = 'blacklist', API_WAF = 'waf', API_HUB_SYNC = 'hub_sync';
	const API_PACKAGE_CONFIGS = 'package_configs';

	/**
	 * Get membership status.
	 *
	 * @return bool
	 */
	public function is_pro() {
		return false;
	}

	/**
	 * Get WPMUDEV API KEY.
	 *
	 * @return bool|string
	 */
	public function get_apikey() {
		if ( ! class_exists( '\WPMUDEV_Dashboard' ) ) {
			return false;
		}

		\WPMUDEV_Dashboard::instance();

		$membership_status = \WPMUDEV_Dashboard::$api->get_membership_data();

		$key = \WPMUDEV_Dashboard::$api->get_key();

		if (
			! empty( $membership_status['hub_site_id'] )
			&& ! empty( $key )
		) {
			return $key;
		}

		return false;
	}

	/**
	 * Get the current membership status using Dash plugin.
	 *
	 * @return string
	 */
	public function membership_status() {
		return 'free';
	}

	/**
	 * @param string $campaign
	 *
	 * @return string
	 */
	public function campaign_url( $campaign ) {

		return 'https://wpmudev.com/project/wp-defender/?utm_source=defender&utm_medium=plugin&utm_campaign=' . $campaign;
	}

	/**
	 * Return the high contrast css class if it is.
	 *
	 * @return string
	 */
	public function maybe_high_contrast() {
		$model = new \WP_Defender\Model\Setting\Main_Setting();

		return $model->high_contrast_mode;
	}

	/**
	 * @param $scenario
	 *
	 * @return string
	 */
	public function get_endpoint( $scenario ) {
		$base = defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER
			? WPMUDEV_CUSTOM_API_SERVER
			: 'https://wpmudev.com/';
		switch ( $scenario ) {
			case self::API_SCAN_KNOWN_VULN:
				return "{$base}api/defender/v1/vulnerabilities";
			case self::API_SCAN_SIGNATURE:
				return "{$base}api/defender/v1/yara-signatures";
			case self::API_AUDIT:
				// This is from another endpoint.
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					: 'https://audit.wpmudev.org/';

				return "{$base}logs";
			case self::API_AUDIT_ADD:
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					: 'https://audit.wpmudev.org/';

				return "{$base}logs/add_multiple";
			case self::API_BLACKLIST:
				return "{$base}api/defender/v1/blacklist-monitoring?domain=" . network_site_url();
			case self::API_WAF:
				$site_id = $this->get_site_id();

				return "{$base}api/hub/v1/sites/$site_id/modules/hosting";
			case self::API_PACKAGE_CONFIGS:
				return "{$base}api/hub/v1/package-configs";
			case self::API_HUB_SYNC:
			default:
				return "{$base}api/defender/v1/scan-results";
		}
	}

	/**
	 * Get WPMUDEV site id.
	 *
	 * @return bool
	 */
	public function get_site_id() {
		if ( $this->get_apikey() !== false ) {
			return \WPMUDEV_Dashboard::$api->get_site_id();
		}

		return false;
	}

	/**
	 * @since 2.5.5 Use Whitelabel filters instead of calling the whitelabel functions directly.
	 * @return bool
	 */
	public function is_whitelabel_enabled() {
		return false;
	}

	/**
	 * Show support links if:
	 * plugin version isn't Free,
	 * Whitelabel is disabled.
	 *
	 * @return bool
	 * @since 2.5.5
	 */
	public function show_support_links() {
		return false;
	}

	/**
	 * @param string $scenario
	 * @param array  $body
	 * @param array  $args
	 * @param bool   $recheck
	 *
	 * @return array|\WP_Error
	 */
	public function make_wpmu_request( $scenario, $body = array(), $args = array(), $recheck = false ) {
		$api_key = $this->get_apikey();
		if ( false === $api_key ) {
			$link_text = sprintf( '<a target="_blank" href="%s">%s</a>', 'https://wpmudev.com/project/wpmu-dev-dashboard/', __( 'here', 'wpdef' ) );
			return new \WP_Error(
				'dashboard_required',
				sprintf(
					/* translators: %s - wpmudev link */
					esc_html__( 'WPMU DEV Dashboard will be required for this action. Please visit %s and install the WPMU DEV Dashboard.', 'wpdef' ),
					$link_text
				)
			);
		}
		if ( ! isset( $body['domain'] ) ) {
			$body['domain'] = network_site_url();
		}
		$headers = array(
			'Authorization' => 'Basic ' . $api_key,
			'apikey'        => $api_key,
		);

		$args    = array_merge(
			$args,
			array(
				'body'      => $body,
				'headers'   => $headers,
				'timeout'   => '30',
				'sslverify' => apply_filters( 'https_ssl_verify', true ),
			)
		);
		$request = wp_remote_request( $this->get_endpoint( $scenario ), $args );
		if ( is_wp_error( $request ) ) {
			if ( ! $recheck ) {
				return $request;
			}
			// Sometimes a response comes with a curl error #52 so should delete Authorization header.
			$args['headers'] = array( 'apikey' => $api_key );
			$request         = wp_remote_request( $this->get_endpoint( $scenario ), $args );
			if ( is_wp_error( $request ) ) {
				return $request;
			}
		}
		$result = wp_remote_retrieve_body( $request );
		$result = json_decode( $result, true );
		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return new \WP_Error(
				wp_remote_retrieve_response_code( $request ),
				isset( $result['message'] ) ? $result['message'] : wp_remote_retrieve_response_message( $request )
			);
		}

		return $result;
	}

	/**
	 * This will build data relate to scan module, so we can push to hub.
	 * @since 2.5.0 remove 'theme_integrity'
	 * @since 2.4.7 add 'theme_integrity', 'plugin_integrity' args
	 *
	 * @return array
	 */
	protected function build_scan_hub_data() {
		$scan         = Scan::get_last();
		$scan_result  = array(
			'core_integrity'     => 0,
			// Leave for migration to 2.5.0.
			'theme_integrity'    => 0,
			'plugin_integrity'   => 0,
			'vulnerability_db'   => 0,
			'file_suspicious'    => 0,
			'last_completed'     => false,
			'scan_items'         => array(),
			'num_issues'         => 0,
			'num_ignored_issues' => 0,
		);
		$total_issues = 0;
		if ( is_object( $scan ) ) {
			$data = $scan->prepare_issues();

			$scan_result['core_integrity']     = $data['count_core'];
			// Leave for migration to 2.5.0.
			$scan_result['theme_integrity']    = 0;
			$scan_result['plugin_integrity']   = $data['count_plugin'];
			$scan_result['vulnerability_db']   = $data['count_vuln'];
			$scan_result['file_suspicious']    = $data['count_malware'];
			$scan_result['last_completed']     = $scan->date_end;
			$scan_result['num_ignored_issues'] = count( $data['ignored'] );

			if ( ! empty( $data['issues'] ) ) {
				$total_issues = count( $data['issues'] );
				foreach ( $data['issues'] as $key => $issue ) {
					$scan_result['scan_items'][] = array(
						'file'   => isset( $issue['full_path'] ) ? $issue['full_path'] : $issue['file_name'],
						'detail' => $issue['short_desc'],
					);
				}
			}
			$scan_result['num_issues'] = $total_issues + $scan_result['num_ignored_issues'];
		}

		$report = new Malware_Report();

		return array(
			'timestamp'     => is_object( $scan ) ? strtotime( $scan->date_end ) : '',
			'warning'       => $total_issues,
			'scan_result'   => $scan_result,
			'scan_schedule' => array(
				'is_activated' => Notification::STATUS_ACTIVE === $report->status,
				// Example of frequency, day, time in build_notification_hub_data() method.
				'time'         => $report->time,
				'day'          => $this->get_notification_day( $report ),
				'frequency'    => $this->backward_frequency_compatibility( $report->frequency ),
			),
		);
	}

	public function backward_frequency_compatibility( $frequency ) {
		switch ( $frequency ) {
			case 'daily':
				return 1;
			case 'weekly':
				return 7;
			case 'monthly':
			default:
				return 30;
		}
	}

	/**
	 * Build data for security tweaks.
	 *
	 * @return array
	 */
	protected function build_security_tweaks_hub_data() {
		$arr   = wd_di()->get( Security_Tweaks::class )->data_frontend();
		$data  = array(
			'cautions' => $arr['summary']['issues_count'],
			'issues'   => array(),
			'ignore'   => array(),
			'fixed'    => array(),
		);
		$types = array(
			Security_Tweaks::STATUS_ISSUES,
			Security_Tweaks::STATUS_IGNORE,
			Security_Tweaks::STATUS_RESOLVE,
		);
		$view  = '';
		foreach ( $types as $type ) {
			if ( 'ignore' === $type ) {
				$view = '&view=ignored';
			} elseif ( 'fixed' === $type ) {
				$view = '&view=resolved';
			}
			foreach ( wd_di()->get( Security_Tweaks::class )->init_tweaks( $type, 'array' ) as $tweak ) {
				$data[ $type ][] = array(
					'label' => $tweak['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' . $view . '#' . $tweak['slug'] ),
				);
			}
		}

		return $data;
	}

	public function build_audit_hub_data() {
		$date_from   = ( new \DateTime( date( 'Y-m-d', strtotime( '-30 days' ) ) ) )->setTime(
			0,
			0,
			0
		)->getTimestamp();
		$date_to     = ( new \DateTime( date( 'Y-m-d' ) ) )->setTime( 23, 59, 59 )->getTimestamp();
		$month_count = Audit_Log::count( $date_from, $date_to );
		$last        = Audit_Log::get_last();
		if ( is_object( $last ) ) {
			$last = gmdate( 'Y-m-d g:i a', $last->timestamp );
		} else {
			$last = 'n/a';
		}

		$settings = new Audit_Logging();

		return array(
			'month'      => $month_count,
			'last_event' => $last,
			'enabled'    => $settings->is_active(),
		);
	}

	public function build_lockout_hub_data() {
		$firewall = wd_di()->get( Firewall::class )->data_frontend();

		return array(
			// Todo: add data of UA-lockouts.
			'last_lockout' => $firewall['last_lockout'],
			'lp'           => wd_di()->get( Login_Lockout::class )->enabled,
			'lp_week'      => $firewall['login']['week'],
			'nf'           => wd_di()->get( Notfound_Lockout::class )->enabled,
			'nf_week'      => $firewall['nf']['week'],
		);
	}

	public function build_2fa_hub_data() {
		$settings = new Two_Fa();

		$query        = new \WP_User_Query(
			array(
				// Look over the network.
				'blog_id'    => 0,
				'meta_key'   => 'defenderAuthOn',
				'meta_value' => true,
			)
		);
		$active_users = array();
		if ( $query->get_total() > 0 ) {
			foreach ( $query->get_results() as $obj_user ) {
				$active_users[] = array(
					'display_name' => $obj_user->data->display_name,
				);
			}
		}

		return array(
			'active'       => $settings->enabled && count( $settings->user_roles ),
			'enabled'      => $settings->enabled,
			'active_users' => $active_users,
		);
	}

	public function build_mask_login_hub_data() {
		$settings = new Mask_Login();

		return array(
			'active'     => $settings->is_active(),
			'masked_url' => $settings->mask_url,
		);
	}

	/**
	 * @param object $module_report
	 *
	 * @return string
	 */
	private function get_notification_day( $module_report ) {
		if ( ! is_object( $module_report ) ) {
			return '';
		}

		if ( 'daily' === $module_report->frequency ) {
			$day = '1';
		} elseif ( 'weekly' === $module_report->frequency ) {
			$day = $module_report->day;
		} else {
			// For 'monthly'.
			$day = $module_report->day_n;
		}

		return $day;
	}

	/**
	 * Frequency format:
	 * if frequency is day, e.g.: 'frequency' => 1, 'day' => '1', 'time' => '20:30'
	 * if frequency is week, e.g.: 'frequency' => 7, 'day' => 'wednesday', 'time' => '14:00'
	 * if frequency is month, e.g.: 'frequency' => 30, 'day' => '4', 'time' => '4:30'
	*/
	public function build_notification_hub_data() {
		$malware_report  = new Malware_Report();
		$audit_settings  = new Audit_Logging();
		$audit_report    = new Audit_Report();
		$firewall_report = new Firewall_Report();

		return array(
			'file_scanning' => array(
				'active'    => true,
				'enabled'   => Notification::STATUS_ACTIVE === $malware_report->status,
				// Report enabled bool value.
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $malware_report->frequency ),
					'day'       => $this->get_notification_day( $malware_report ),
					'time'      => $malware_report->time,
				),
			),
			'audit_logging' => array(
				'active'    => $audit_settings->is_active(),
				'enabled'   => Notification::STATUS_ACTIVE === $audit_report->status,
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $audit_report->frequency ),
					'day'       => $this->get_notification_day( $audit_report ),
					'time'      => $audit_report->time,
				),
			),
			'ip_lockouts'   => array(
				// Always true as we have blacklist listening.
				'active'    => true,
				'enabled'   => Notification::STATUS_ACTIVE === $firewall_report->status,
				// Report enabled bool value.
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $firewall_report->frequency ),
					'day'       => $this->get_notification_day( $firewall_report ),
					'time'      => $firewall_report->time,
				),
			),
		);
	}

	public function build_firewall_notification_hub_data() {
		$firewall_notification = new Firewall_Notification();
		if ( 'enabled' === $firewall_notification->status ) {
			$login_lockout = $firewall_notification->configs['login_lockout'];
			$nf_lockout    = $firewall_notification->configs['nf_lockout'];
		} else {
			$login_lockout = false;
			$nf_lockout    = false;
		}

		return array(
			'firewall' => array(
				'login_lockout' => $login_lockout,
				'404_lockout'   => $nf_lockout,
			),
		);
	}

	public function build_security_headers_hub_data() {
		$security_headers = wd_di()->get( Security_Headers::class )->get_type_headers();

		return array(
			'active'   => $security_headers['active'],
			'inactive' => $security_headers['inactive'],
		);
	}

	public function build_stats_to_hub() {
		$scan_data     = $this->build_scan_hub_data();
		$tweaks_data   = $this->build_security_tweaks_hub_data();
		$audit_data    = $this->build_audit_hub_data();
		$firewall_data = $this->build_lockout_hub_data();
		$two_fa        = $this->build_2fa_hub_data();
		$mask_login    = $this->build_mask_login_hub_data();
		$sec_headers   = $this->build_security_headers_hub_data();

		$data = array(
			// Domain name.
			'domain'       => network_home_url(),
			// Last scan date.
			'timestamp'    => $scan_data['timestamp'],
			// Scan issue count.
			'warnings'     => $scan_data['warning'],
			// Security tweaks issue count.
			'cautions'     => $tweaks_data['cautions'],
			'data_version' => gmdate( 'Ymd' ),
			'scan_data'    => json_encode(
				array(
					'scan_result'           => $scan_data['scan_result'],
					'hardener_result'       => array(
						'issues'   => $tweaks_data[ Security_Tweaks::STATUS_ISSUES ],
						'ignored'  => $tweaks_data[ Security_Tweaks::STATUS_IGNORE ],
						'resolved' => $tweaks_data[ Security_Tweaks::STATUS_RESOLVE ],
					),
					'scan_schedule'         => $scan_data['scan_schedule'],
					'audit_status'          => array(
						'events_in_month' => $audit_data['month'],
						'audit_enabled'   => $audit_data['enabled'],
						'last_event_date' => $audit_data['last_event'],
					),
					'audit_page_url'        => network_admin_url( 'admin.php?page=wdf-logging' ),
					'labels'                => array(
						'parent_integrity' => esc_html__( 'File change detection', 'wpdef' ),
						'core_integrity'   => esc_html__( 'Scan core files', 'wpdef' ),
						'plugin_integrity' => esc_html__( 'Scan plugin files', 'wpdef' ),
						'vulnerability_db' => esc_html__( 'Known vulnerabilities', 'wpdef' ),
						'file_suspicious'  => esc_html__( 'Suspicious code', 'wpdef' ),
					),
					'scan_page_url'         => network_admin_url( 'admin.php?page=wdf-scan' ),
					'hardener_page_url'     => network_admin_url( 'admin.php?page=wdf-hardener' ),
					'new_scan_url'          => network_admin_url( 'admin.php?page=wdf-scan&wdf-action=new_scan' ),
					'schedule_scans_url'    => network_admin_url( 'admin.php?page=wdf-schedule-scan' ),
					'settings_page_url'     => network_admin_url( 'admin.php?page=wdf-settings' ),
					'ip_lockout_page_url'   => network_admin_url( 'admin.php?page=wdf-ip-lockout' ),
					// Todo: add data of UA-lockouts.
					'last_lockout'          => $firewall_data['last_lockout'],
					'login_lockout_enabled' => $firewall_data['lp'],
					'login_lockout'         => $firewall_data['lp_week'],
					'lockout_404_enabled'   => $firewall_data['nf'],
					'lockout_404'           => $firewall_data['nf_week'],
					'total_lockout'         => (int) $firewall_data['lp_week'] + (int) $firewall_data['nf_week'],
					'advanced'              => array(
						// This is moved but still keep here for backward compatibility.
						'multi_factors_auth' => array(
							'active'       => $two_fa['active'],
							'enabled'      => $two_fa['enabled'],
							'active_users' => $two_fa['active_users'],
						),
						'mask_login'         => array(
							'activate'   => $mask_login['active'],
							'masked_url' => $mask_login['masked_url'],
						),
						'security_headers'   => array(
							'active'   => $sec_headers['active'],
							'inactive' => $sec_headers['inactive'],
						),
					),
					'reports'               => $this->build_notification_hub_data(),
					'notifications'         => $this->build_firewall_notification_hub_data(),
				)
			),
		);

		return $data;
	}

	public function get_menu_title() {
		if ( $this->is_pro() ) {
			$menu_title = esc_html__( 'Defender Pro', 'wpdef' );
		} else {
			// Check if it's Pro but user logged the WPMU Dashboard out.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$menu_title = file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'wp-defender/wp-defender.php' )
			&& is_plugin_active( 'wp-defender/wp-defender.php' )
				? esc_html__( 'Defender Pro', 'wpdef' )
				: esc_html__( 'Defender', 'wpdef' );
		}

		return $menu_title;
	}

	/**
	 * Check if user is a paid one in WPMU DEV.
	 *
	 * @return bool
	 */
	public function is_member() {
		if (
			class_exists( 'WPMUDEV_Dashboard' )
			&& method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_projects' )
			&& method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_type' )
		) {
			$type     = \WPMUDEV_Dashboard::$api->get_membership_type();
			$projects = \WPMUDEV_Dashboard::$api->get_membership_projects();
			$def_pid  = 1081723;

			if (
				( 'unit' === $type && in_array( $def_pid, $projects, true ) )
				|| ( 'single' === $type && $def_pid === $projects )
			) {
				return true;
			}

			if ( function_exists( 'is_wpmudev_member' ) ) {
				return is_wpmudev_member();
			}

			return false;
		}

		return false;
	}

	/**
	 * Get our special WDP ID header line from the file.
	 *
	 * @return array Plugin details: name, id.
	 */
	public function get_plugin_details() {

		return get_file_data(
			WP_DEFENDER_FILE,
			array(
				'name' => 'Plugin Name',
				'id'   => 'WDP ID',
			)
		);
	}

	/**
	 * Check if user is a WPMU DEV admin.
	 * @since 2.6.3
	 *
	 * @return bool
	 */
	public function is_wpmu_dev_admin() {
		if (
			class_exists( 'WPMUDEV_Dashboard' )
			&& method_exists( 'WPMUDEV_Dashboard_Site', 'allowed_user' )
		) {
			$user_id = get_current_user_id();

			return \WPMUDEV_Dashboard::$site->allowed_user( $user_id );
		}

		return false;
	}
}
