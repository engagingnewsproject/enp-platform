<?php

namespace WP_Defender\Behavior;

use Calotes\Component\Behavior;
use Calotes\Helper\Array_Cache;
use WP_Defender\Controller\Firewall;
use WP_Defender\Controller\Nf_Lockout;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Notification;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Mask_Login;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Formats;

/**
 * This class contains everything relate to WPMUDEV
 * Class WPMUDEV
 * @package WP_Defender\Behavior
 * @since 2.2
 */
class WPMUDEV extends Behavior {
	use IO, Formats;

	const API_SCAN_SIGNATURE = 'yara_scan', API_SCAN_KNOWN_VULN = 'known_vulnerability';
	const API_AUDIT = 'audit_logging', API_AUDIT_ADD = 'audit_logging_add';
	const API_BLACKLIST = 'blacklist', API_WAF = 'waf', API_HUB_SYNC = 'hub_sync';

	/**
	 * Get membership status
	 *
	 * @return bool
	 * @method
	 */
	public function is_pro() {
		return false;
	}

	/**
	 * Get WPMUDEV API KEY
	 *
	 * @return bool|string
	 */
	public function get_apikey() {
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
	 * @param $campaign
	 *
	 * @return string
	 */
	public function campaign_url( $campaign ) {

		return "https://wpmudev.com/project/wp-defender/?utm_source=defender&utm_medium=plugin&utm_campaign=" . $campaign;
	}

	/**
	 * Get whitelabel status from Dev Dashboard
	 * Properties
	 *  - hide_branding
	 *  - hero_image
	 *  - footer_text
	 *  - change_footer
	 *  - hide_doc_link
	 *
	 * @return mixed
	 */
	public function white_label_status() {
		return [
			'hide_branding' => false,
			'hero_image'    => '',
			'footer_text'   => '',
			'change_footer' => false,
			'hide_doc_link' => false
		];
	}

	/**
	 * Return the highcontrast css class if it is
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
				//this is from another endpoint
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) ? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) : 'https://audit.wpmudev.org/';

				return "{$base}logs";
			case self::API_AUDIT_ADD:
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) ? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) : 'https://audit.wpmudev.org/';

				return "{$base}logs/add_multiple";
			case self::API_BLACKLIST:
				return "{$base}api/defender/v1/blacklist-monitoring?domain=" . network_site_url();
			case self::API_WAF:
				$site_id = $this->get_site_id();

				return "{$base}api/hub/v1/sites/$site_id/modules/hosting";
			case self::API_HUB_SYNC :
			default:
				return "https://wpmudev.com/api/defender/v1/scan-results";
		}
	}

	/**
	 * Get WPMUDEV site id
	 * @return bool
	 */
	public function get_site_id() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function is_whitelabel_enabled() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function is_doc_links_enabled() {
		return false;
	}

	/**
	 * @return array
	 */
	public function get_whitelabel_data() {

		return array();
	}

	/**
	 * @param $scenario
	 * @param array $body
	 * @param array $args
	 *
	 * @return \WP_Error
	 */
	public function make_wpmu_request( $scenario, $body = [], $args = [] ) {
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
		$headers = [
			'Authorization' => 'Basic ' . $api_key,
			'apikey'        => $api_key
		];

		$args    = array_merge( $args, [
			'body'      => $body,
			'headers'   => $headers,
			'timeout'   => '30',
			'sslverify' => apply_filters( 'https_ssl_verify', true )
		] );
		$request = wp_remote_request( $this->get_endpoint( $scenario ), $args );
		if ( is_wp_error( $request ) ) {
			return $request;
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
	 * This will build data relate to scan module so we can push to hub
	 * @return array
	 */
	protected function build_scan_hub_data() {
		$scan        = Scan::get_last();
		$scan_result = array(
			'core_integrity'   => 0,
			'vulnerability_db' => 0,
			'file_suspicious'  => 0,
			'last_completed'   => false,
			'scan_items'       => []
		);
		if ( is_object( $scan ) ) {
			$scan_result['core_integrity']   = count( $scan->get_issues( Scan_Item::TYPE_INTEGRITY ) );
			$scan_result['vulnerability_db'] = count( $scan->get_issues( Scan_Item::TYPE_VULNERABILITY ) );
			$scan_result['file_suspicious']  = count( $scan->get_issues( Scan_Item::TYPE_SUSPICIOUS ) );
			$scan_result['last_completed']   = $scan->date_end;
			foreach ( $scan->get_issues() as $issue ) {
				$arr                         = $issue->to_array();
				$scan_result['scan_items'][] = [
					'file'   => isset( $arr['full_path'] ) ? $arr['full_path'] : $arr['file_name'],
					'detail' => $arr['short_desc']
				];
			}
		}

		$report = new Malware_Report();

		return [
			'timestamp'     => is_object( $scan ) ? strtotime( $scan->date_end ) : "",
			'warning'       => is_object( $scan ) ? count( $scan->get_issues() ) : 0,
			'scan_result'   => $scan_result,
			'scan_schedule' => [
				'is_activated' => $report->status === Notification::STATUS_ACTIVE,
				'time'         => $report->time,
				'day'          => $report->day,
				'frequency'    => $this->backward_frequency_compatibility( $report->frequency )
			]
		];
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
	 * Build data for security tweaks
	 * @return array
	 */
	protected function build_security_tweaks_hub_data() {
		$arr   = wd_di()->get( Security_Tweaks::class )->data_frontend();
		$data  = [
			'cautions' => $arr['summary']['issues_count'],
			'issues'   => [],
			'ignore'   => [],
			'fixed'    => []
		];
		$types = [
			Security_Tweaks::STATUS_ISSUES,
			Security_Tweaks::STATUS_IGNORE,
			Security_Tweaks::STATUS_RESOLVE
		];
		$view  = '';
		foreach ( $types as $type ) {
			if ( 'ignore' === $type ) {
				$view = '&view=ignored';
			} elseif ( 'fixed' === $type ) {
				$view = '&view=resolved';
			}
			foreach ( wd_di()->get( Security_Tweaks::class )->init_tweaks( $type, 'array' ) as $tweak ) {
				$data[ $type ][] = [
					'label' => $tweak['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' . $view . '#' . $tweak['slug'] )
				];
			}
		}

		return $data;
	}

	public function build_audit_hub_data() {
		$date_from   = ( new \DateTime( date( 'Y-m-d', strtotime( '-30 days' ) ) ) )->setTime( 0, 0,
			0 )->getTimestamp();
		$date_to     = ( new \DateTime( date( 'Y-m-d' ) ) )->setTime( 23, 59, 59 )->getTimestamp();
		$month_count = Audit_Log::count( $date_from, $date_to );
		$last        = Audit_Log::get_last();
		if ( is_object( $last ) ) {
			$last = $this->format_date_time( $last->timestamp );
		} else {
			$last = 'n/a';
		}

		$settings = new Audit_Logging();

		return [
			'month'      => $month_count,
			'last_event' => $last,
			'enabled'    => $settings->enabled
		];
	}

	public function build_lockout_hub_data() {
		$firewall = wd_di()->get( Firewall::class )->data_frontend();

		return [
			'last_lockout' => $firewall['last_lockout'],
			'lp'           => wd_di()->get( Login_Lockout::class )->enabled,
			'lp_week'      => $firewall['login']['week'],
			'nf'           => wd_di()->get( Notfound_Lockout::class )->enabled,
			'nf_week'      => $firewall['nf']['week'],
		];
	}

	public function build_2fa_hub_data() {
		$settings = new Two_Fa();

		$query        = new \WP_User_Query( [
			//look over the network
			'blog_id'    => 0,
			'meta_key'   => 'defenderAuthOn',
			'meta_value' => true
		] );
		$active_users = array();
		if ( $query->get_total() > 0 ) {
			foreach ( $query->get_results() as $obj_user ) {
				$active_users[] = array(
					'display_name' => $obj_user->data->display_name
				);
			}
		}

		return [
			'active'       => $settings->enabled && count( $settings->user_roles ),
			'enabled'      => $settings->enabled,
			'active_users' => $active_users,
		];
	}

	public function build_mask_login_hub_data() {
		$settings = new Mask_Login();

		return [
			'active'     => $settings->is_active(),
			'masked_url' => $settings->mask_url,
		];
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
			//for 'monthly'
			$day = $module_report->day_n;
		}

		return $day;
	}

	public function build_notification_hub_data() {
		$malware_report  = new Malware_Report();
		$audit_settings  = new Audit_Logging();
		$audit_report    = new Audit_Report();
		$firewall_report = new Firewall_Report();

		return [
			'file_scanning' => array(
				'active'    => true,
				'enabled'   => $malware_report->status === Notification::STATUS_ACTIVE,
				//Report enabled Bool
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $malware_report->frequency ),
					'day'       => $this->get_notification_day( $malware_report ),
					'time'      => $malware_report->time
				)
			),
			'audit_logging' => array(
				'active'    => $audit_settings->enabled,
				'enabled'   => $audit_report->status === Notification::STATUS_ACTIVE,
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $audit_report->frequency ),
					'day'       => $this->get_notification_day( $audit_report ),
					'time'      => $audit_report->time
				)
			),
			'ip_lockouts'   => array(
				//always true as we have blacklist listening
				'active'    => true,
				'enabled'   => $firewall_report->status === Notification::STATUS_ACTIVE,
				//Report enabled Bool
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $firewall_report->frequency ),
					'day'       => $this->get_notification_day( $firewall_report ),
					'time'      => $firewall_report->time
				),
			)
		];
	}

	public function build_security_headers_hub_data() {
		$security_headers = wd_di()->get( Security_Headers::class )->get_type_headers();

		return [
			'active'   => $security_headers['active'],
			'inactive' => $security_headers['inactive'],
		];
	}

	public function build_stats_to_hub() {
		$scan_data     = $this->build_scan_hub_data();
		$tweaks_data   = $this->build_security_tweaks_hub_data();
		$audit_data    = $this->build_audit_hub_data();
		$firewall_data = $this->build_lockout_hub_data();
		$two_fa        = $this->build_2fa_hub_data();
		$mask_login    = $this->build_mask_login_hub_data();
		$sec_headers   = $this->build_security_headers_hub_data();

		$data = [
			//domain name
			'domain'       => network_home_url(),
			//last scan date
			'timestamp'    => $scan_data['timestamp'],
			//scan issue count
			'warnings'     => $scan_data['warning'],
			//security tweaks issue count
			'cautions'     => $tweaks_data['cautions'],
			'data_version' => '20170801',
			'scan_data'    => json_encode(
				array(
					'scan_result'           => $scan_data,
					'hardener_result'       => array(
						'issues'   => $tweaks_data[ Security_Tweaks::STATUS_ISSUES ],
						'ignored'  => $tweaks_data[ Security_Tweaks::STATUS_IGNORE ],
						'resolved' => $tweaks_data[ Security_Tweaks::STATUS_RESOLVE ]
					),
					'scan_schedule'         => $scan_data['scan_schedule'],
					'audit_status'          => array(
						'events_in_month' => $audit_data['month'],
						'audit_enabled'   => $audit_data['enabled'],
						'last_event_date' => $audit_data['last_event'],
					),
					'audit_page_url'        => network_admin_url( 'admin.php?page=wdf-logging' ),
					'labels'                => [
						'core_integrity'   => esc_html__( 'File change detection', 'wpdef' ),
						'vulnerability_db' => esc_html__( 'Known Vulnerabilities', 'wpdef' ),
						'file_suspicious'  => esc_html__( 'Suspicious Code', 'wpdef' ),
						//Todo: add new Scan labels
					],
					'scan_page_url'         => network_admin_url( 'admin.php?page=wdf-scan' ),
					'hardener_page_url'     => network_admin_url( 'admin.php?page=wdf-hardener' ),
					'new_scan_url'          => network_admin_url( 'admin.php?page=wdf-scan&wdf-action=new_scan' ),
					'schedule_scans_url'    => network_admin_url( 'admin.php?page=wdf-schedule-scan' ),
					'settings_page_url'     => network_admin_url( 'admin.php?page=wdf-settings' ),
					'ip_lockout_page_url'   => network_admin_url( 'admin.php?page=wdf-ip-lockout' ),
					'last_lockout'          => $firewall_data['last_lockout'],
					'login_lockout_enabled' => $firewall_data['lp'],
					'login_lockout'         => $firewall_data['lp_week'],
					'lockout_404_enabled'   => $firewall_data['nf'],
					'lockout_404'           => $firewall_data['nf_week'],
					'total_lockout'         => (int) $firewall_data['lp_week'] + (int) $firewall_data['nf_week'],
					'advanced'              => array(
						//this is moved but still keep here for backward compatibility
						'multi_factors_auth' => array(
							'active'       => $two_fa['active'],
							'enabled'      => $two_fa['enabled'],
							'active_users' => $two_fa['active_users'],
						),
						'mask_login'         => array(
							'activate'   => $mask_login['active'],
							'masked_url' => $mask_login['masked_url']
						),
						'security_headers'   => array(
							'active'   => $sec_headers['active'],
							'inactive' => $sec_headers['inactive'],
						),
					),
					'reports'               => $this->build_notification_hub_data()
				)
			)
		];

		return $data;
	}

	public function get_menu_title() {
		if ( $this->is_pro() ) {
			$menu_title = esc_html__( 'Defender Pro', 'wpdef' );
		} else {
			// Check if it's Pro but user logged the WPMU Dashboard out
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$menu_title = file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'wp-defender/wp-defender.php' )
			              && is_plugin_active( 'wp-defender/wp-defender.php' )
				? esc_html__( 'Defender Pro', 'wpdef' )
				: esc_html__( 'Defender', 'wpdef' );
		}

		return $menu_title;
	}

	/**
	 * Check if user is a paid one in WPMU DEV
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
				'name'    => 'Plugin Name',
				'id'      => 'WDP ID',
			)
		);
	}

}
