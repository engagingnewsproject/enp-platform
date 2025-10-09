<?php
/**
 * Handles operations related to IP and country-based blacklisting and
 * whitelisting, as well as managing firewall settings and logs.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Exception;
use WPMUDEV_Dashboard;
use WP_Defender\Component;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Onboard;
use MaxMind\Db\Reader\InvalidDatabaseException;
use WP_Defender\Model\Setting\Firewall as Model_Firewall;
use WP_Defender\Component\Trusted_Proxy_Preset\Cloudflare_Proxy;
use WP_Defender\Component\Trusted_Proxy_Preset\Trusted_Proxy_Preset;
use WP_Defender\Controller\Firewall as Firewall_Controller;
use WP_Defender\Component\Smart_Ip_Detection;

/**
 * Handles operations related to IP and country-based blacklisting and whitelisting,
 * managing firewall settings, logs, and automatic detection of proxy configurations.
 */
class Firewall extends Component {

	/**
	 * The notice slug if there is switching IP Detection option to Cloudflare (CF).
	 */
	public const IP_DETECTION_CF_SHOW_SLUG = 'wd_show_ip_detection_cf_notice';

	/**
	 * The notice slug if CF IP Detection notice is rejected.
	 */
	public const IP_DETECTION_CF_DISMISS_SLUG = 'wd_dismiss_ip_detection_cf_notice';

	/**
	 * The notice slug if there is switching IP Detection option to X-Forwarded-For (XFF).
	 */
	public const IP_DETECTION_XFF_SHOW_SLUG = 'wd_show_ip_detection_xff_notice';

	/**
	 * The notice slug if CF IP Detection notice is rejected.
	 */
	public const IP_DETECTION_XFF_DISMISS_SLUG = 'wd_dismiss_ip_detection_xff_notice';

	/**
	 * The option name for the whitelist server public IP.
	 */
	public const WHITELIST_SERVER_PUBLIC_IP_OPTION = 'wpdef_firewall_whitelist_server_public_ip';

	/**
	 * Check if the first commencing request is proper staff remote access.
	 *
	 * @param  array $access  The access details including key.
	 *
	 * @return bool
	 */
	private function is_commencing_staff_access( $access ): bool {
		$action   = defender_get_data_from_request( 'action', 'g' );
		$wdpunkey = defender_get_data_from_request( 'wdpunkey', 'p' );

		return wp_doing_ajax() && 'wdpunauth' === $action && hash_equals( $wdpunkey, $access['key'] );
	}

	/**
	 * Check is the access from authenticated staff.
	 *
	 * @return bool
	 */
	private function is_authenticated_staff_access(): bool {
		return '1' === defender_get_data_from_request( 'wpmudev_is_staff', 'c' );
	}

	/**
	 * Check if the access is from our staff access.
	 *
	 * @return bool
	 */
	private function is_a_staff_access(): bool {
		if ( defined( 'WPMUDEV_DISABLE_REMOTE_ACCESS' ) && true === constant( 'WPMUDEV_DISABLE_REMOTE_ACCESS' ) ) {
			return false;
		}

		$wpmu_dev         = new WPMUDEV();
		$is_remote_access = $wpmu_dev->get_apikey() &&
							true === WPMUDEV_Dashboard::$api->remote_access_details( 'enabled' );

		if ( $is_remote_access ) {
			$access = $wpmu_dev->get_remote_access();
			if ( $this->is_authenticated_staff_access() || $this->is_commencing_staff_access( $access ) ) {
				$this->log( $access, Firewall_Controller::FIREWALL_LOG );

				return true;
			}
		}

		return false;
	}

	/**
	 * Cron for delete old log.
	 *
	 * @throws Exception On failure.
	 */
	public function firewall_clean_up_logs() {
		$settings     = new Model_Firewall();
		$storage_days = apply_filters( 'ip_lockout_logs_store_backward', $settings->storage_days );
		if ( ! is_numeric( $storage_days ) ) {
			return;
		}
		$time_string = '-' . $storage_days . ' days';
		$timestamp   = $this->local_to_utc( $time_string );
		Lockout_Log::remove_logs( $timestamp, 50 );
	}

	/**
	 * Cron for clean up temporary IP block list.
	 */
	public function firewall_clean_up_temporary_ip_blocklist() {
		$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED );
		foreach ( $models as $model ) {
			$model->status = Lockout_Ip::STATUS_NORMAL;
			$model->save();
		}
	}

	/**
	 * Update temporary IP block list of Firewall, clear cron job.
	 * The interval settings value is updated once.
	 *
	 * @param  string $new_interval  The new interval to set.
	 */
	public function update_cron_schedule_interval( $new_interval ) {
		$settings = new Model_Firewall();
		// If a new interval is different from the saved value, we need to clear the cron job.
		if ( $new_interval !== $settings->ip_blocklist_cleanup_interval ) {
			update_site_option( 'wpdef_clear_schedule_firewall_cleanup_temp_blocklist_ips', true );
		}
	}

	/**
	 * Skip priority lockout checks for a given IP.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool True if the IP should be skipped from lockout checks, false otherwise.
	 * @throws InvalidDatabaseException Thrown for unexpected data is found in DB.
	 */
	public function skip_priority_lockout_checks( string $ip ): bool {
		/**
		 * Retrieve Global_IP instance.
		 *
		 * @var IP\Global_IP $global_ip
		 */
		$global_ip = wd_di()->get( IP\Global_IP::class );

		if (
			$global_ip->is_global_ip_enabled() &&
			$global_ip->is_ip_allowed( $ip )
		) {
			return true;
		}

		/**
		 * Retrieve Blacklist_Lockout instance.
		 *
		 * @var Blacklist_Lockout $service
		 */
		$service = wd_di()->get( Blacklist_Lockout::class );

		$model         = Lockout_Ip::get( $ip );
		$is_lockout_ip = is_object( $model ) && $model->is_locked();

		$is_country_whitelisted = ! $service->is_blacklist( $ip ) &&
									$service->is_country_whitelist( $ip ) && ! $is_lockout_ip;

		// If this IP is whitelisted, so we don't need to blacklist this.
		if ( $service->is_ip_whitelisted( $ip ) || $is_country_whitelisted ) {
			return true;
		}
		// Green light if access staff is enabled.
		if ( $this->is_a_staff_access() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if an IP is blacklisted.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return array An array containing the reason and result of the blacklist check.
	 * @throws InvalidDatabaseException Thrown for unexpected data is found in DB.
	 */
	public function is_blocklisted_ip( string $ip ): array {
		$array = array(
			'reason' => '',
			'result' => false,
		);
		/**
		 * Retrieve Blacklist_Lockout instance.
		 *
		 * @var Blacklist_Lockout $service
		 */
		$service = wd_di()->get( Blacklist_Lockout::class );

		if ( $service->is_blacklist( $ip ) ) {
			return array(
				'reason' => 'local_ip',
				'result' => true,
			);
		}

		if ( $service->is_country_blacklist( $ip ) ) {
			return array(
				'reason' => 'country',
				'result' => true,
			);
		}

		/**
		 * Retrieve Global_IP instance.
		 *
		 * @var IP\Global_IP $global_ip
		 */
		$global_ip = wd_di()->get( IP\Global_IP::class );

		if (
			$global_ip->is_active() &&
			$global_ip->is_ip_blocked( $ip )
		) {
			return array(
				'reason' => IP\Global_IP::REASON_SLUG,
				'result' => true,
			);
		}

		/**
		 * Retrieve Antibot_Global_Firewall instance.
		 *
		 * @var IP\Antibot_Global_Firewall $antibot
		 */
		$antibot = wd_di()->get( IP\Antibot_Global_Firewall::class );

		if (
			$antibot->is_active() &&
			$antibot->is_ip_blocked( $ip ) &&
			'plugin' === $antibot->get_managed_by()
		) {
			return array(
				'reason' => IP\Antibot_Global_Firewall::REASON_SLUG,
				'result' => true,
			);
		}

		return $array;
	}

	/**
	 * Get the limit of Lockout records.
	 *
	 * @return int
	 * @since 3.7.0 Get the limit of Lockout records.
	 */
	public function get_lockout_record_limit() {
		return (int) apply_filters( 'wd_lockout_record_limit', 10000 );
	}

	/**
	 * Cron for deleting unwanted lockout records.
	 *
	 * @return void
	 * @since 3.8.0
	 */
	public function firewall_clean_up_lockout(): void {
		global $wpdb;

		$current_timestamp = time();
		$limit             = $this->get_lockout_record_limit();
		$table             = $wpdb->base_prefix . ( new Lockout_Ip() )->get_table();

		do {
			$affected_rows = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE (release_time = 0 OR release_time < %d) AND meta IN (%s, %s, %s, %s, %s) ORDER BY id LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$current_timestamp,
					'[]',
					'{"nf":[]}',
					'{"login":[]}',
					'{"nf":[],"login":[]}',
					'{"login":[],"nf":[]}',
					$limit
				)
			);

		} while ( $affected_rows === $limit );
	}

	/**
	 * Gather IP(s) from headers.
	 *
	 * @since 4.4.2
	 * @deprecated 5.1.0 This method and the 'wpdef_firewall_gathered_ips' filter are no longer in use.
	 * @return array
	 */
	public function gather_ips(): array {
		$ip_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'HTTP_CF_CONNECTING_IP',
			'REMOTE_ADDR',
		);

		$gathered_ips = array();
		$server       = defender_get_data_from_request( null, 's' );
		foreach ( $ip_headers as $header ) {
			if ( ! empty( $server[ $header ] ) ) {
				// Handle multiple IP addresses.
				$ips = array_map( 'trim', explode( ',', $server[ $header ] ) );

				foreach ( $ips as $ip ) {
					if ( $this->validate_ip( $ip ) ) {
						$gathered_ips[] = $ip;
					}
				}
			}
		}

		/**
		 * Filter the gathered IPs before checking the lockout records.
		 *
		 * @param  array  $gathered_ips  IPs gathered from request headers.
		 *
		 * @since 4.5.1
		 * @deprecated 5.1.0 No longer used and will be removed in a future version.
		 */
		$gathered_ips = (array) apply_filters_deprecated(
			'wpdef_firewall_gathered_ips',
			array_unique( $gathered_ips ),
			'5.1.0',
			'',
			'This filter will be removed in a future version. If you are using it, update your implementation.'
		);

		return $this->filter_user_ips( $gathered_ips );
	}

	/**
	 * Check if the current request is recognized as coming from Cloudflare.
	 *
	 * @return bool
	 */
	public function is_cloudflare_request(): bool {
		$is_cloudflare = true;

		$server = defender_get_data_from_request( null, 's' );
		if ( ! (
			isset( $server['HTTP_CF_CONNECTING_IP'] ) ||
			isset( $server['HTTP_CF_IPCOUNTRY'] ) ||
			isset( $server['HTTP_CF_RAY'] ) ||
			isset( $server['HTTP_CF_VISITOR'] )
		) ) {
			$is_cloudflare = false;
		}

		return $is_cloudflare;
	}

	/**
	 * Auto-detect proxy server and switch to appropriate IP Detection option.
	 *
	 * @return void
	 */
	public function auto_switch_ip_detection_option(): void {
		$model = wd_di()->get( Model_Firewall::class );

		if ( $this->is_cloudflare_request() ) {
			if (
				'HTTP_CF_CONNECTING_IP' !== $model->http_ip_header &&
				! self::is_switched_ip_detection_notice( self::IP_DETECTION_CF_SHOW_SLUG )
			) {
				$model->http_ip_header = 'HTTP_CF_CONNECTING_IP';
				update_site_option( self::IP_DETECTION_CF_SHOW_SLUG, true );
			}

			$model->trusted_proxy_preset = Cloudflare_Proxy::PROXY_SLUG;
			$model->save();

			// Fetch trusted proxy ips.
			$this->update_trusted_proxy_preset_ips();
		}
	}

	/**
	 * Update trusted proxy preset IPs.
	 *
	 * @return void
	 */
	public function update_trusted_proxy_preset_ips(): void {
		$model = wd_di()->get( Model_Firewall::class );
		if ( ! empty( $model->trusted_proxy_preset ) ) {
			/**
			 * Retrieve Trusted_Proxy_Preset instance.
			 *
			 * @var Trusted_Proxy_Preset $trusted_proxy_preset
			 */
			$trusted_proxy_preset = wd_di()->get( Trusted_Proxy_Preset::class );
			$trusted_proxy_preset->set_proxy_preset( $model->trusted_proxy_preset );
			$trusted_proxy_preset->update_ips();
		}
	}

	/**
	 * Show a notice if wrong IP Detection option is configured for the site.
	 *
	 * @return void
	 */
	public function maybe_show_misconfigured_ip_detection_option_notice(): void {
		$model = wd_di()->get( Model_Firewall::class );
		$xff   = defender_get_data_from_request( 'HTTP_X_FORWARDED_FOR', 's' );

		if (
			'HTTP_X_FORWARDED_FOR' !== $model->http_ip_header &&
			( is_string( $xff ) && 0 < strlen( $xff ) ) &&
			! $this->is_cloudflare_request() &&
			! self::is_xff_notice_ready()
		) {
			update_site_option( self::IP_DETECTION_XFF_SHOW_SLUG, true );
		}
	}

	/**
	 * Check if a switched IP detection notice has been shown.
	 *
	 * @param  string $key  The notice key to check.
	 *
	 * @return bool True if the notice has been shown, false otherwise.
	 */
	public static function is_switched_ip_detection_notice( string $key ): bool {
		return (bool) get_site_option( $key );
	}

	/**
	 * Check if the XFF notice is ready to be shown.
	 *
	 * @return bool
	 */
	public static function is_xff_notice_ready(): bool {
		return ! Onboard::maybe_show_onboarding() &&
				self::is_switched_ip_detection_notice( self::IP_DETECTION_XFF_SHOW_SLUG )
				&& ! self::is_switched_ip_detection_notice( self::IP_DETECTION_XFF_DISMISS_SLUG )
				&& ! wd_di()->get( Smart_Ip_Detection::class )->is_smart_ip_detection_enabled()
				&& 'HTTP_X_FORWARDED_FOR' !== wd_di()->get( Model_Firewall::class )->http_ip_header;
	}

	/**
	 * Check if the CF notice is ready to be shown.
	 *
	 * @return bool
	 */
	public static function is_cf_notice_ready(): bool {
		return self::is_switched_ip_detection_notice( self::IP_DETECTION_CF_SHOW_SLUG )
				&& ! self::is_switched_ip_detection_notice( self::IP_DETECTION_CF_DISMISS_SLUG )
				&& ! wd_di()->get( Smart_Ip_Detection::class )->is_smart_ip_detection_enabled();
	}

	/**
	 * Delete all notice slugs related to IP detection switching.
	 */
	public static function delete_slugs(): void {
		delete_site_option( self::IP_DETECTION_CF_SHOW_SLUG );
		delete_site_option( self::IP_DETECTION_CF_DISMISS_SLUG );
		delete_site_option( self::IP_DETECTION_XFF_SHOW_SLUG );
		delete_site_option( self::IP_DETECTION_XFF_DISMISS_SLUG );
	}

	/**
	 * Get the first blocked IP.
	 *
	 * @param  array $ips  The array of IPs to check.
	 *
	 * @return string
	 * @throws InvalidDatabaseException Thrown for unexpected data is found in DB.
	 */
	public function get_blocked_ip( $ips ): string {
		$blocked_ip = '';
		foreach ( $ips as $ip ) {
			$is_blocklisted = $this->is_blocklisted_ip( $ip );
			if ( $is_blocklisted['result'] ) {
				$blocked_ip = $ip;
				break;
			}
		}
		// Do not continue if there is not a single blocked IP.
		if ( '' === $blocked_ip ) {
			// Maybe IP(-s) in Active lockouts?
			if ( count( $ips ) > 1 ) {
				$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED, $ips );
				foreach ( $models as $model ) {
					$blocked_ip = $model->ip;
					break;
				}
			} elseif ( null !== Lockout_Ip::is_blocklisted_ip( $ips[0] ) ) {
				$blocked_ip = $ips[0];
			}
		}

		return $blocked_ip;
	}

	/**
	 * Get custom HTTP headers used for IP detection.
	 *
	 * @return array List of custom HTTP headers.
	 */
	public static function custom_http_headers(): array {
		return array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CF_CONNECTING_IP',
		);
	}

	/**
	 * Get trusted proxy presets.
	 *
	 * @return array List of trusted proxy presets.
	 */
	public static function trusted_proxy_presets(): array {
		return array(
			Cloudflare_Proxy::PROXY_SLUG => esc_html__( 'Cloudflare', 'wpdef' ),
		);
	}

	/**
	 * Dismiss the CF notice if the IP Detection is set to automatic.
	 */
	public function maybe_dismiss_cf_notice(): void {
		if (
			wd_di()->get( Smart_Ip_Detection::class )->is_smart_ip_detection_enabled()
			&& self::is_switched_ip_detection_notice( self::IP_DETECTION_CF_SHOW_SLUG )
			&& ! self::is_switched_ip_detection_notice( self::IP_DETECTION_CF_DISMISS_SLUG )
		) {
			update_site_option( self::IP_DETECTION_CF_DISMISS_SLUG, true );
		}
	}

	/**
	 * Is whitelist server public IP enabled.
	 *
	 * @return bool
	 * @since 5.0.2
	 */
	public function is_whitelist_server_public_ip_enabled(): bool {
		/**
		 * Filter to enable/disable fetching server public IP.
		 *
		 * @param bool $enable True to enable whitelist server public IP, false otherwise.
		 *
		 * @since 5.0.2
		 */
		return (bool) apply_filters( 'wpdef_firewall_whitelist_server_public_ip_enabled', true );
	}

	/**
	 * Set whitelist server public IP.
	 *
	 * @return bool
	 * @since 5.0.2
	 */
	public function set_whitelist_server_public_ip(): bool {
		if ( ! $this->is_whitelist_server_public_ip_enabled() ) {
			return false;
		}

		$ip = wd_di()->get( Smart_Ip_Detection::class )->get_server_public_ip();
		if ( empty( $ip ) ) {
			$this->log( 'Failed to whitelist server public IP.', Firewall_Controller::FIREWALL_LOG );
			return false;
		}

		$stored_ip = $this->get_whitelist_server_public_ip();
		if ( $stored_ip !== $ip ) {
			update_site_option( self::WHITELIST_SERVER_PUBLIC_IP_OPTION, $ip );
		}

		$this->log( 'Server public IP whitelisted successfully.', Firewall_Controller::FIREWALL_LOG );
		return true;
	}

	/**
	 * Get whitelist server public IP.
	 *
	 * @return string
	 * @since 5.0.2
	 */
	public function get_whitelist_server_public_ip(): string {
		return $this->is_whitelist_server_public_ip_enabled() ?
			get_site_option( self::WHITELIST_SERVER_PUBLIC_IP_OPTION, '' ) :
			'';
	}
}