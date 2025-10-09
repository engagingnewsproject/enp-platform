<?php
/**
 * Handle HUB based functionalities of WPMUDEV class.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_Error;
use DateTime;
use Exception;
use WP_User_Query;
use WPMUDEV_Dashboard;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Notification;
use WP_Defender\Controller\Firewall;
use WP_Defender\Component\Quarantine;
use WP_Defender\Model\Setting\Two_Fa;
use WP_Defender\Component\IP\Antibot_Global_Firewall;
use WP_Defender\Model\Setting\Recaptcha;
use WP_Defender\Model\Setting\Mask_Login;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Setting\Password_Protection;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Setting\Security_Tweaks as Model_Security_Tweaks;

trait Defender_Hub_Client {

	use IP;

	/**
	 * Get API base URL.
	 *
	 * @return string
	 * @since 3.4.0
	 */
	public function get_api_base_url(): string {
		return defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER
			? trailingslashit( WPMUDEV_CUSTOM_API_SERVER )
			: 'https://wpmudev.com/';
	}

	/**
	 * Retrieves the endpoint URL based on the given scenario.
	 *
	 * @param string $scenario  The scenario identifier.
	 *
	 * @return string The endpoint URL.
	 */
	public function get_endpoint( $scenario ): string {
		$base = $this->get_api_base_url();
		switch ( $scenario ) {
			case self::API_SCAN_KNOWN_VULN:
				return $base . 'api/defender/v1/vulnerabilities';
			case self::API_SCAN_SIGNATURE:
				return $base . 'api/defender/v1/yara-signatures';
			case self::API_AUDIT:
				// This is from another endpoint.
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					: 'https://audit.wpmudev.org/';

				return $base . 'logs';
			case self::API_AUDIT_ADD:
				$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
					: 'https://audit.wpmudev.org/';

				return $base . 'logs/add_multiple';
			case self::API_BLACKLIST:
				return $base . 'api/defender/v1/blacklist-monitoring?domain=' . network_site_url();
			case self::API_GLOBAL_IP_LIST:
				return $base . 'api/hub/v1/global-ip-list';
			case self::API_PACKAGE_CONFIGS:
				return $base . 'api/hub/v1/package-configs';
			case self::API_IP_BLOCKLIST_SUBMIT_LOGS:
				return $base . 'api/blocklist/v1/logs';
			case self::API_ANTIBOT_GLOBAL_FIREWALL:
				$site_id = $this->get_site_id();

				return $base . "api/hub/v1/sites/$site_id/modules/hosting/antibot";
			case self::API_HUB_SYNC:
			default:
				return $base . 'api/defender/v1/scan-results';
		}
	}

	/**
	 * Get WPMUDEV site id.
	 *
	 * @return int|bool
	 */
	public function get_site_id() {
		if ( false !== $this->get_apikey() ) {
			return (int) WPMUDEV_Dashboard::$api->get_site_id();
		}

		return false;
	}

	/**
	 * HUB API remote request method.
	 *
	 * @param  string $scenario  The scenario for the API request.
	 * @param  array  $body  The body of the API request. Default is an empty array.
	 * @param  array  $args  The arguments for the API request. Default is an empty array.
	 * @param  bool   $recheck  Whether to recheck the API request. Default is false.
	 *
	 * @return array|WP_Error The response body of the API request or a WP_Error object.
	 * @throws Exception If the Dash plugin authentication API key is missing.
	 */
	private function hub_api_request(
		string $scenario,
		array $body = array(),
		array $args = array(),
		bool $recheck = false
	) {
		$api_key          = $this->get_api_key();
		$body['domain'] ??= network_site_url();

		$headers = array(
			'Authorization' => 'Basic ' . $api_key,
			'apikey'        => $api_key,
		);

		$timeout = isset( $args['timeout'] ) ? $args['timeout'] : 30;

		$args = array_merge(
			$args,
			array(
				'body'      => $body,
				'headers'   => $headers,
				'timeout'   => $timeout,
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
			return new WP_Error(
				wp_remote_retrieve_response_code( $request ),
				$result['message'] ?? wp_remote_retrieve_response_message( $request )
			);
		}

		return $result;
	}

	/**
	 * Makes a request to the WPMU API.
	 *
	 * @param  string $scenario  The scenario for the request.
	 * @param  array  $body  The body of the request. Default is an empty array.
	 * @param  array  $args  Additional arguments for the request. Default is an empty array.
	 * @param  bool   $recheck  Whether to recheck the request. Default is false.
	 *
	 * @return array|WP_Error The response from the API request.
	 */
	public function make_wpmu_request(
		string $scenario,
		array $body = array(),
		array $args = array(),
		bool $recheck = false
	) {
		$api_key = $this->get_api_key();

		if ( '' === $api_key ) {
			$link_text = sprintf(
				'<a target="_blank" href="%s">%s</a>',
				'https://wpmudev.com/project/wpmu-dev-dashboard/',
				esc_html__( 'here', 'wpdef' )
			);

			return new WP_Error(
				'dashboard_required',
				sprintf(
				/* translators: %s - wpmudev link */
					esc_html__(
						'WPMU DEV Dashboard will be required for this action. Please visit %s and install the WPMU DEV Dashboard.',
						'wpdef'
					),
					$link_text
				)
			);
		}

		return $this->hub_api_request( $scenario, $body, $args, $recheck );
	}

	/**
	 * Makes a request to the WPMU API for free and higher account holding users.
	 *
	 * @param  string $scenario  The scenario for the request.
	 * @param  array  $body  The body of the request. Default is an empty array.
	 * @param  array  $args  Additional arguments for the request. Default is an empty array.
	 * @param  bool   $recheck  Whether to recheck the request. Default is false.
	 *
	 * @return array|WP_Error The response from the API request.
	 * @throws Exception If the Dash plugin authentication API key is missing.
	 * @throws Exception If permission is denied for the API call.
	 */
	public function make_wpmu_free_request(
		string $scenario,
		array $body = array(),
		array $args = array(),
		bool $recheck = false
	) {
		if ( ! $this->hub_connector_connected() ) {
			throw new Exception( esc_html__( 'API key missing.', 'wpdef' ) );
		}
		if ( $this->can_wpmu_free_request() === false ) {
			throw new Exception( esc_html__( 'Permission denied API call.', 'wpdef' ) );
		}
		return $this->hub_api_request( $scenario, $body, $args, $recheck );
	}

	/**
	 * Check if the current request can be made as a free API request.
	 *
	 * @return bool True if the request is allowed, false otherwise.
	 * @throws Exception If the WPMU DEV Dashboard plugin is missing.
	 */
	public function can_wpmu_free_request() {
		return in_array( $this->get_membership_type(), array( 'free', 'full', 'unit' ), true );
	}

	/**
	 * This will build data relate to scan module, so we can push to hub.
	 *
	 * @return array
	 * @since 2.4.7 add 'plugin_integrity' args
	 */
	protected function build_scan_hub_data(): array {
		$scan         = Scan::get_last();
		$scan_result  = array(
			'core_integrity'     => 0,
			'plugin_integrity'   => 0,
			'vulnerability_db'   => 0,
			'file_suspicious'    => 0,
			'outdated_plugin'    => 0,
			'closed_plugin'      => 0,
			'last_completed'     => false,
			'scan_items'         => array(),
			'num_issues'         => 0,
			'num_ignored_issues' => 0,
		);
		$total_issues = 0;
		if ( is_object( $scan ) ) {
			$data = $scan->prepare_issues( 10, 1 );

			$scan_result['core_integrity']     = $data['count_core'];
			$scan_result['plugin_integrity']   = $data['count_plugin'];
			$scan_result['vulnerability_db']   = $data['count_vuln'];
			$scan_result['file_suspicious']    = $data['count_malware'];
			$scan_result['outdated_plugin']    = $data['count_outdated_plugin'];
			$scan_result['closed_plugin']      = $data['count_closed_plugin'];
			$scan_result['last_completed']     = $scan->date_end;
			$scan_result['num_ignored_issues'] = $data['count_ignored'];

			if ( isset( $data['issues'] ) && is_array( $data['issues'] ) && array() !== $data['issues'] ) {
				$total_issues = $data['count_issues'];
				foreach ( $data['issues'] as $issue ) {
					$scan_result['scan_items'][] = array(
						'file'   => $issue['full_path'] ?? $issue['file_name'],
						'detail' => $issue['short_desc'],
					);
				}
			}
			$scan_result['num_issues'] = $total_issues + $data['count_ignored'];
		}

		$settings = new Scan_Settings();

		return array(
			'timestamp'     => is_object( $scan ) ? strtotime( $scan->date_start ) : '',
			'warning'       => $total_issues,
			'scan_result'   => $scan_result,
			'scan_schedule' => array(
				// @since 2.7.0 change scheduled scan logic.
				'is_activated' => $settings->scheduled_scanning,
				// Example of frequency, day, time in build_notification_hub_data() method.
				'time'         => $settings->time,
				'day'          => $this->get_notification_day( $settings ),
				'frequency'    => $this->backward_frequency_compatibility( $settings->frequency ),
			),
		);
	}

	/**
	 * Converts a string frequency to an integer value for backward compatibility.
	 *
	 * @param  string $frequency  The frequency to convert. Must be one of 'daily', 'weekly', or 'monthly'.
	 *
	 * @return int The converted integer value. Returns 1 for 'daily', 7 for 'weekly', and 30 for 'monthly' (default).
	 */
	public function backward_frequency_compatibility( string $frequency ): int {
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
	protected function build_security_tweaks_hub_data(): array {
		// Exclude direct call of data_frontend().
		$tweak_arr = wd_di()->get( Model_Security_Tweaks::class )->get_tweak_types();

		$data  = array(
			'cautions' => $tweak_arr['count_issues'],
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
			foreach ( wd_di()->get( Security_Tweaks::class )->init_tweaks( $type ) as $slug => $tweak ) {
				$data[ $type ][] = array(
					'label' => $tweak->get_label(),
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' . $view . '#' . $slug ),
				);
			}
		}

		return $data;
	}

	/**
	 * Builds an array of audit data to be sent to the hub.
	 *
	 * @return array An array containing the number of audit log entries, the timestamp of the
	 *               last audit log entry, and a boolean indicating if audit logging is enabled.
	 */
	public function build_audit_hub_data(): array {
		$date_from   = ( new DateTime( wp_date( 'Y-m-d', strtotime( '-30 days' ) ) ) )->setTime(
			0,
			0,
			0
		)->getTimestamp();
		$date_to     = ( new DateTime( wp_date( 'Y-m-d' ) ) )->setTime( 23, 59, 59 )->getTimestamp();
		$month_count = Audit_Log::count( $date_from, $date_to );
		$last        = Audit_Log::get_last();
		if ( is_object( $last ) ) {
			$last = wp_date( 'Y-m-d g:i a', $last->timestamp );
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

	/**
	 * Builds an array of lockout data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_lockout_hub_data(): array {
		$firewall = wd_di()->get( Firewall::class )->data_frontend();
		$antibot  = wd_di()->get( Antibot_Global_Firewall::class );

		return array(
			'last_lockout'           => $firewall['last_lockout'],
			'lp'                     => wd_di()->get( Login_Lockout::class )->enabled,
			'lp_week'                => $firewall['login']['week'],
			'nf'                     => wd_di()->get( Notfound_Lockout::class )->enabled,
			'nf_week'                => $firewall['nf']['week'],
			'ua'                     => wd_di()->get( User_Agent_Lockout::class )->enabled,
			'ua_week'                => $firewall['ua']['week'],
			'global_ip_list_enabled' => wd_di()->get( Global_Ip_Lockout::class )->enabled,
			'antibot_enabled'        => $antibot->frontend_is_enabled(),
			'antibot_mode'           => $antibot->frontend_mode(),
		);
	}

	/**
	 * Builds an array of 2fa data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_2fa_hub_data(): array {
		$settings     = new Two_Fa();
		$service      = wd_di()->get( \WP_Defender\Component\Two_Fa::class );
		$query        = new WP_User_Query(
			array(
				// Look over the network.
				'blog_id'    => 0,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => $service::DEFAULT_PROVIDER_USER_KEY,
						'value'   => array_keys( $service->get_providers() ),
						'compare' => 'IN',
					),
				),
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
			'active'       => $settings->enabled && array() !== $settings->user_roles,
			'enabled'      => $settings->enabled,
			'active_users' => $active_users,
		);
	}

	/**
	 * Builds an array of mask login data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_mask_login_hub_data(): array {
		$settings = new Mask_Login();

		return array(
			'active'     => $settings->is_active(),
			'masked_url' => $settings->mask_url,
		);
	}

	/**
	 * Builds an array of security headers data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_recaptcha_hub_data(): array {
		$settings = new Recaptcha();

		return array(
			'active' => $settings->is_active(),
		);
	}

	/**
	 * Builds an array of password protection data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_password_protection_hub_data(): array {
		$settings = new Password_Protection();

		return array(
			'active' => $settings->is_active(),
		);
	}

	/**
	 * Get the notification day.
	 *
	 * @param  object $module_report  The report object.
	 *
	 * @return string
	 */
	private function get_notification_day( $module_report ): string {
		if ( ! ( isset( $module_report->frequency ) && isset( $module_report->day ) && isset( $module_report->day_n ) ) ) {
			return '';
		}

		if ( 'daily' === $module_report->frequency ) {
			$day = '1';
		} elseif ( 'weekly' === $module_report->frequency ) {
			$day = $module_report->day;
		} else {
			// For "monthly" frequency.
			$day = $module_report->day_n;
		}

		return $day;
	}

	/**
	 * Frequency format:
	 * if frequency is day, e.g.: 'frequency' => 1, 'day' => '1', 'time' => '20:30'
	 * if frequency is week, e.g.: 'frequency' => 7, 'day' => 'wednesday', 'time' => '14:00'
	 * if frequency is month, e.g.: 'frequency' => 30, 'day' => '4', 'time' => '4:30'
	 *
	 * @return array
	 */
	public function build_notification_hub_data(): array {
		$audit_settings  = new Audit_Logging();
		$audit_report    = new Audit_Report();
		$firewall_report = new Firewall_Report();
		$malware_report  = new Malware_Report();
		$scan_settings   = new Scan_Settings();

		return array(
			'file_scanning' => array(
				'active'    => true,
				// @since 2.7.0 move scheduled options to Scan settings, but we get status of Malware Scanning - Reporting here.
				'enabled'   => Notification::STATUS_ACTIVE === $malware_report->status,
				// Report enabled bool value.
				'frequency' => array(
					'frequency' => $this->backward_frequency_compatibility( $scan_settings->frequency ),
					'day'       => $this->get_notification_day( $scan_settings ),
					'time'      => $scan_settings->time,
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

	/**
	 * Build data for firewall notification.
	 *
	 * @return array
	 */
	public function build_firewall_notification_hub_data(): array {
		$firewall_notification = new Firewall_Notification();
		if ( 'enabled' === $firewall_notification->status ) {
			$login_lockout = $firewall_notification->configs['login_lockout'];
			$nf_lockout    = $firewall_notification->configs['nf_lockout'];
			$ua_lockout    = $firewall_notification->configs['ua_lockout'] ?? false;
		} else {
			$login_lockout = false;
			$nf_lockout    = false;
			$ua_lockout    = false;
		}

		return array(
			'firewall' => array(
				'login_lockout' => $login_lockout,
				'404_lockout'   => $nf_lockout,
				'ua_lockout'    => $ua_lockout,
			),
		);
	}

	/**
	 * Build security headers hub data.
	 *
	 * @return array
	 */
	public function build_security_headers_hub_data(): array {
		$security_headers = wd_di()->get( Security_Headers::class )->get_type_headers();

		return array(
			'active'   => $security_headers['active'],
			'inactive' => $security_headers['inactive'],
		);
	}

	/**
	 * Build data to be sent to the hub.
	 *
	 * @return array
	 */
	public function build_stats_to_hub(): array {
		$scan_data         = $this->build_scan_hub_data();
		$tweaks_data       = $this->build_security_tweaks_hub_data();
		$audit_data        = $this->build_audit_hub_data();
		$firewall_data     = $this->build_lockout_hub_data();
		$two_fa            = $this->build_2fa_hub_data();
		$mask_login        = $this->build_mask_login_hub_data();
		$sec_headers       = $this->build_security_headers_hub_data();
		$recaptcha         = $this->build_recaptcha_hub_data();
		$pwned_password    = $this->build_password_protection_hub_data();
		$quarantined_files = $this->build_quarantined_files_hub_data();

		return array(
			// Domain name.
			'domain'       => network_home_url(),
			// Last scan date.
			'timestamp'    => $scan_data['timestamp'],
			// Scan issue count.
			'warnings'     => $scan_data['warning'],
			// Security tweaks issue count.
			'cautions'     => $tweaks_data['cautions'],
			'data_version' => wp_date( 'Ymd' ),
			'scan_data'    => wp_json_encode(
				array(
					'scan_result'            => $scan_data['scan_result'],
					'hardener_result'        => array(
						'issues'   => $tweaks_data[ Security_Tweaks::STATUS_ISSUES ],
						'ignored'  => $tweaks_data[ Security_Tweaks::STATUS_IGNORE ],
						'resolved' => $tweaks_data[ Security_Tweaks::STATUS_RESOLVE ],
					),
					'scan_schedule'          => $scan_data['scan_schedule'],
					'audit_status'           => array(
						'events_in_month' => $audit_data['month'],
						'audit_enabled'   => $audit_data['enabled'],
						'last_event_date' => $audit_data['last_event'],
					),
					'audit_page_url'         => network_admin_url( 'admin.php?page=wdf-logging' ),
					'labels'                 => array(
						// Todo: maybe should it remove because Scan Settings model has label() method for that?
						'parent_integrity' => esc_html__( 'File change detection', 'wpdef' ),
						'core_integrity'   => esc_html__( 'Scan core files', 'wpdef' ),
						'plugin_integrity' => esc_html__( 'Scan plugin files', 'wpdef' ),
						'vulnerability_db' => esc_html__( 'Known vulnerabilities', 'wpdef' ),
						'file_suspicious'  => esc_html__( 'Suspicious code', 'wpdef' ),
					),
					'scan_page_url'          => network_admin_url( 'admin.php?page=wdf-scan' ),
					'hardener_page_url'      => network_admin_url( 'admin.php?page=wdf-hardener' ),
					'new_scan_url'           => network_admin_url( 'admin.php?page=wdf-scan&wdf-action=new_scan' ),
					'schedule_scans_url'     => network_admin_url( 'admin.php?page=wdf-schedule-scan' ),
					'settings_page_url'      => network_admin_url( 'admin.php?page=wdf-settings' ),
					'ip_lockout_page_url'    => network_admin_url( 'admin.php?page=wdf-ip-lockout' ),
					'last_lockout'           => $firewall_data['last_lockout'],
					'login_lockout_enabled'  => $firewall_data['lp'],
					'login_lockout'          => $firewall_data['lp_week'],
					'lockout_404_enabled'    => $firewall_data['nf'],
					'lockout_404'            => $firewall_data['nf_week'],
					'lockout_ua_enabled'     => $firewall_data['ua'],
					'lockout_ua'             => $firewall_data['ua_week'],
					'total_lockout'          => (int) $firewall_data['lp_week'] + (int) $firewall_data['nf_week'] + (int) $firewall_data['ua_week'],
					'global_ip_list_enabled' => $firewall_data['global_ip_list_enabled'],
					'antibot_enabled'        => $firewall_data['antibot_enabled'],
					'antibot_mode'           => $firewall_data['antibot_mode'],
					'advanced'               => array(
						// This is moved but still keep here for backward compatibility.
						'multi_factors_auth'  => array(
							'active'       => $two_fa['active'],
							'enabled'      => $two_fa['enabled'],
							'active_users' => $two_fa['active_users'],
						),
						'mask_login'          => array(
							'activate'   => $mask_login['active'],
							'masked_url' => $mask_login['masked_url'],
						),
						'security_headers'    => array(
							'active'   => $sec_headers['active'],
							'inactive' => $sec_headers['inactive'],
						),
						'google_recaptcha'    => array(
							'active' => $recaptcha['active'],
						),
						'password_protection' => array(
							'active' => $pwned_password['active'],
						),
					),
					'reports'                => $this->build_notification_hub_data(),
					'notifications'          => $this->build_firewall_notification_hub_data(),
					'quarantined_files'      => $quarantined_files,
				)
			),
		);
	}

	/**
	 * Checks whether we're on WPMU DEV Hosting.
	 *
	 * @return bool
	 */
	public function is_wpmu_hosting(): bool {
		return isset( $_SERVER['WPMUDEV_HOSTED'] );
	}

	/**
	 * Checks whether we're on The Free HUB.
	 *
	 * @return bool
	 */
	protected function is_tfh_account(): bool {
		return class_exists( 'WPMUDEV_Dashboard' ) &&
				is_object( WPMUDEV_Dashboard::$api ) &&
				method_exists( WPMUDEV_Dashboard::$api, 'get_membership_status' ) &&
				'free' === WPMUDEV_Dashboard::$api->get_membership_status();
	}

	/**
	 * Check if WPMUDEV Hosted site is connected to The Free HUB.
	 *
	 * @return bool
	 * @since 3.3.0
	 */
	public function is_hosted_site_connected_to_tfh(): bool {
		return $this->is_tfh_account() && $this->is_wpmu_hosting();
	}

	/**
	 * Check if a site from 3rd party hosting is connected to The Free HUB.
	 *
	 * @return bool
	 * @since 3.6.0
	 */
	public function is_another_hosted_site_connected_to_tfh(): bool {
		return $this->is_tfh_account() && ! $this->is_wpmu_hosting();
	}

	/**
	 * Build notification hub data.
	 *
	 * @return array
	 */
	protected function build_quarantined_files_hub_data(): array {
		if ( ! class_exists( 'WP_Defender\Component\Quarantine' ) ) {
			return array();
		}

		return wd_di()->get( Quarantine::class )->hub_list();
	}

	/**
	 * Schedule Hub Synchronization event.
	 */
	public function schedule_hub_sync(): void {
		if ( ! wp_next_scheduled( 'defender_hub_sync' ) ) {
			wp_schedule_single_event( time(), 'defender_hub_sync' );
		}
	}

	/**
	 * Returns the signup url.
	 * If Dashboard plugin is active the signup url returned will be the Dashboard signup page. Else Hub signup page.
	 *
	 * @return string
	 */
	public function signup_url(): string {
		if ( class_exists( 'WPMUDEV_Dashboard' ) && is_object( WPMUDEV_Dashboard::$api ) ) {
			return add_query_arg(
				array( 'page' => 'wpmudev' ),
				is_multisite() ? network_admin_url() : get_admin_url()
			);
		}

		return $this->hub_signup_url();
	}

	/**
	 * Returns the hub's signup url.
	 *
	 * @return string
	 */
	public function hub_signup_url(): string {
		return $this->get_api_base_url() . 'register/?signup=defender&defender_url=' . site_url();
	}
}