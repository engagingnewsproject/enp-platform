<?php
/**
 * Responsible for handling AntiBot Global Firewall.
 *
 * @package WP_Defender\Component\IP
 */

namespace WP_Defender\Component\IP;

use Exception;
use Generator;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
use WP_Defender\Controller\Firewall;
use WP_Defender\Integrations\Antibot_Global_Firewall_Client;
use WP_Defender\Model\Antibot_Global_Firewall as Antibot_Global_Firewall_Model;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;
use WP_Defender\Traits\Defender_Dashboard_Client;
use WP_Error;

/**
 * Provides methods for downloading IPs from AntiBot Global Firewall, storing, deleting and checking IPs in DB table.
 */
class Antibot_Global_Firewall extends Component {
	use Defender_Dashboard_Client;

	public const REASON_SLUG = 'antibot_global_firewall';

	public const LOG_FILE_NAME = 'antibot-global-firewall.log';

	public const DOWNLOAD_SYNC_SCHEDULE = 'twicedaily';

	public const DOWNLOAD_SYNC_NEXT_RUN_OPTION = 'wpdef_antibot_global_firewall_download_sync_next_run';

	public const LAST_SYNC_OPTION = 'wpdef_antibot_global_firewall_last_sync';

	public const NOTICE_SLUG = 'wpdef_show_antibot_global_firewall_notice';

	public const BLOCKLIST_STATS_KEY = 'wpdef_antibot_global_firewall_stats';

	public const IS_SWITCHING_TO_PLUGIN_IN_PROGRESS = 'wpdef_antibot_global_firewall_switching_to_plugin_in_progress';

	/**
	 * The AntiBot Global Firewall model for storing IPs.
	 *
	 * @var Antibot_Global_Firewall_Model
	 */
	private $model;

	/**
	 * The AntiBot Global Firewall setting model.
	 *
	 * @var Antibot_Global_Firewall_Setting
	 */
	private $model_setting;

	/**
	 * The WPMUDEV object.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * The client object for AntiBot Global Firewall.
	 *
	 * @var Antibot_Global_Firewall_Client
	 */
	private $antibot_client;

	/**
	 * Lock file name for firewall updates.
	 *
	 * @var string
	 */
	protected string $lock_filename = 'antibot_global_firewall.lock';

	/**
	 * Initializes the class with the Antibot_Global_Firewall_Model, Antibot_Global_Firewall_Setting, WPMUDEV and Antibot_Global_Firewall_Client instances.
	 *
	 * @param Antibot_Global_Firewall_Model   $model          The AntiBot Global Firewall model.
	 * @param Antibot_Global_Firewall_Setting $model_setting  The AntiBot Global FIrewall setting model.
	 * @param WPMUDEV                         $wpmudev        The WPMUDEV object.
	 * @param Antibot_Global_Firewall_Client  $antibot_client The client object for AntiBot Global Firewall API.
	 */
	public function __construct( Antibot_Global_Firewall_Model $model, Antibot_Global_Firewall_Setting $model_setting, WPMUDEV $wpmudev, Antibot_Global_Firewall_Client $antibot_client ) {
		$this->model          = $model;
		$this->model_setting  = $model_setting;
		$this->wpmudev        = $wpmudev;
		$this->antibot_client = $antibot_client;

		add_action( 'wpdef_confirm_antibot_toggle_on_hosting', array( $this, 'confirm_toggle_on_hosting' ) );
		add_action( 'wp_loaded', array( $this, 'clear_antibot_on_disconnection' ) );
	}

	/**
	 * Check if AntiBot Global Firewall feature is enabled.
	 *
	 * @return bool True for enabled or false for disabled.
	 */
	public function is_enabled(): bool {
		$is_enabled = $this->model_setting->enabled;

		/**
		 * Filter to enable or disable the AntiBot Global Firewall.
		 *
		 * @param bool $is_enabled True for enabled or false for disabled.
		 */
		return (bool) apply_filters( 'wpdef_antibot_enabled', $is_enabled );
	}

	/**
	 * Check if the AntiBot Global Firewall is enabled on WPMU DEV.
	 *
	 * @return bool True if the AntiBot Global Firewall is enabled, false otherwise.
	 */
	public function hosting_is_enabled(): bool {
		return true === defender_get_hosting_feature_state( 'antibot' );
	}

	/**
	 * Check if the AntiBot Global Firewall is enabled based on managed by.
	 *
	 * @return bool True if the AntiBot Global Firewall is enabled, false otherwise.
	 */
	public function frontend_is_enabled(): bool {
		return 'plugin' === $this->get_managed_by() ?
			! $this->is_expired_membership_type() && $this->is_enabled() :
			$this->hosting_is_enabled();
	}

	/**
	 * Check if the AntiBot Global Firewall is enabled and the site is connected to the HUB.
	 *
	 * @return bool True if the AntiBot Global Firewall is active, false otherwise.
	 */
	public function is_active(): bool {
		return $this->frontend_is_enabled() && $this->is_site_connected_to_hub_via_hcm_or_dash();
	}

	/**
	 * Check if the AntiBot Global Firewall is active via plugin.
	 *
	 * @since 5.1.1
	 * @return bool True if the AntiBot Global Firewall is active via plugin, false otherwise.
	 */
	public function is_active_via_plugin(): bool {
		return 'plugin' === $this->get_managed_by() && $this->is_enabled() && $this->is_site_connected_to_hub_via_hcm_or_dash();
	}

	/**
	 * Check if the AntiBot Global Firewall is active via hosting.
	 *
	 * @since 5.6.0
	 * @return bool True if the AntiBot Global Firewall is active via hosting, false otherwise.
	 */
	public function is_active_via_hosting(): bool {
		return 'hosting' === $this->get_managed_by() && $this->hosting_is_enabled();
	}

	/**
	 * Check if the IP is blocked.
	 *
	 * @param string $ip The IP address.
	 *
	 * @return bool True if the IP is blocked, false otherwise.
	 */
	public function is_ip_blocked( string $ip ): bool {
		$result = $this->model->get_by_ip( $ip );

		return isset( $result->unlocked ) && 1 !== $result->unlocked;
	}

	/**
	 * Delete complete blocklist.
	 */
	public function delete_blocklist() {
		$this->model->truncate();

		delete_site_option( self::LAST_SYNC_OPTION );
	}

	/**
	 * Check if the Unlock Me Captcha should be displayed.
	 *
	 * @param array $ips The IP addresses.
	 *
	 * @return bool True if the IP is blocked by AntiBot Global Firewall, false otherwise.
	 */
	public function is_displayed( array $ips ): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		foreach ( $ips as $ip ) {
			if ( $this->is_ip_blocked( $ip ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the Unlock Me Captcha button text.
	 *
	 * @return string The button text.
	 */
	public static function get_button_text(): string {
		return esc_html__( 'AntiBot Unlock Me', 'wpdef' );
	}

	/**
	 * Handle the download and store blocklist.
	 *
	 * @since 4.8.0
	 * @return void
	 */
	public function download_and_store_blocklist(): void {
		if ( ! $this->is_site_connected_to_hub_via_hcm_or_dash() ) {
			return;
		}

		if ( $this->has_lock() ) {
			$this->log( 'Fallback as already a process is running', Firewall::FIREWALL_LOG );
			return;
		}

		$this->create_lock();
		$file_path = $this->download_blocklist();

		if ( ! empty( $file_path ) ) {
			$this->store_blocklist( $file_path );
		}
		$this->remove_lock();
	}

	/**
	 * Download blocklist.
	 *
	 * @since 4.8.0
	 * @return string|void
	 */
	private function download_blocklist() {
		$response = $this->antibot_client->get_blocklist_download( $this->model_setting->mode );

		if ( is_wp_error( $response ) ) {
			$this->log( sprintf( 'AntiBot Global Firewall Error: %s', $response->get_error_message() ), Firewall::FIREWALL_LOG );
			return;
		} elseif ( isset( $response['status'] ) && 'error' === $response['status'] ) {
			$this->log( sprintf( 'AntiBot Global Firewall Error: %s', $response['message'] ), Firewall::FIREWALL_LOG );
			return;
		} elseif ( empty( $response['data']['download_url'] ) && empty( $response['data']['hashes']['sha256'] ) ) {
			$this->log( 'AntiBot Global Firewall Error: Download link not found in the response.', Firewall::FIREWALL_LOG );
			return;
		}

		$file_url = $response['data']['download_url'];

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$tmp_file = download_url( $file_url );

		if ( is_wp_error( $tmp_file ) ) {
			$this->log( 'AntiBot Global Firewall Error: Unable to download file', Firewall::FIREWALL_LOG );
			return;
		}

		$expected_file_hash = '';
		$response           = wp_remote_get( $response['data']['hashes']['sha256'] );
		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			$expected_file_hash = trim( wp_remote_retrieve_body( $response ) );
		}

		$file_hash = hash_file( 'sha256', $tmp_file );
		if ( $file_hash !== $expected_file_hash ) {
			$this->log( 'AntiBot Global Firewall Error: File is not legit. Hash does not match.', Firewall::FIREWALL_LOG );
			return;
		}

		$this->log( 'AntiBot Global Firewall: Downloaded ' . $this->model_setting->get_mode_label() . ' blocklist successfully.', Firewall::FIREWALL_LOG );

		return $tmp_file;
	}

	/**
	 * Store blocklist.
	 *
	 * @param string $file_path File path.
	 *
	 * @since 4.8.0
	 * @return void
	 */
	private function store_blocklist( string $file_path ): void {
		try {
			$generator = $this->read_file_generator( $file_path );

			$this->delete_blocklist();
			$this->model->bulk_insert( $generator );

			self::set_last_sync();

			$this->log( 'AntiBot Global Firewall: IPs stored in the DB table.', Firewall::FIREWALL_LOG );
		} catch ( Exception $e ) {
			$this->log( 'AntiBot Global Firewall Error: ' . $e->getMessage(), Firewall::FIREWALL_LOG );
		}

		// Remove temporary file.
		wp_delete_file( $file_path );
	}

	/**
	 * Read file generator.
	 *
	 * @param string $file_path File path.
	 *
	 * @return Generator
	 * @throws Exception If file not found or could not open the file.
	 */
	private function read_file_generator( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			throw new Exception( 'File not found.' );
		}

		global $wp_filesystem;
		$lines = $wp_filesystem->get_contents_array( $file_path );

		if ( empty( $lines ) || ! is_array( $lines ) ) {
			throw new Exception( 'Could not retrieve the file contents!' );
		}

		try {
			foreach ( $lines as $line ) {
				yield trim( $line );
			}
		} catch ( Exception $exception ) {
			throw $exception;
		}
	}

	/**
	 * Get the blocklisted IP count.
	 *
	 * @return string The blocklisted IP count.
	 */
	public function get_blocklisted_ip_count(): string {
		// Check if the feature is enabled.
		if ( ! $this->frontend_is_enabled() ) {
			return '0';
		}
		// Since from v5.0.2 one method is used for counting.
		return number_format( $this->get_cached_blocklisted_ips() );
	}

	/**
	 * Get the last sync timestamp.
	 *
	 * @return false|int The last sync timestamp.
	 */
	public static function get_last_sync() {
		return get_site_option( self::LAST_SYNC_OPTION, false );
	}

	/**
	 * Set the last sync timestamp.
	 */
	public static function set_last_sync(): void {
		update_site_option( self::LAST_SYNC_OPTION, time() );
	}

	/**
	 * Check if the IPs should be downloaded from Antibot service.
	 *
	 * @return bool True if the IPs should be downloaded, false otherwise.
	 */
	public function maybe_download(): bool {
		if ( $this->wpmudev->is_wpmu_hosting() && 'plugin' !== $this->get_managed_by() ) {
			return false;
		}

		$last_sync = self::get_last_sync();

		if ( false === $last_sync ) {
			return true;
		}

		return time() - $last_sync >= 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Get the default managed by.
	 *
	 * @return string The default managed by.
	 */
	public function get_default_managed_by(): string {
		return $this->wpmudev->is_wpmu_hosting() ? 'hosting' : 'plugin';
	}

	/**
	 * Get the AntiBot managed by.
	 *
	 * @return string The AntiBot managed by.
	 */
	public function get_managed_by(): string {
		$managed_by = $this->model_setting->managed_by;

		return '' !== $managed_by ? $managed_by : $this->get_default_managed_by();
	}

	/**
	 * Get the AntiBot managed by label.
	 *
	 * @return string The AntiBot managed by label.
	 */
	public function get_managed_by_label(): string {
		return 'plugin' === $this->get_managed_by() ?
			esc_html__( 'Defender Plugin', 'wpdef' ) :
			esc_html__( 'WPMU DEV Hosting', 'wpdef' );
	}

	/**
	 * Set the AntiBot managed by.
	 *
	 * @param string $managed_by The managed by value.
	 *
	 * @return bool True if the managed by is set, false otherwise.
	 */
	public function set_managed_by( string $managed_by ): bool {
		if ( in_array( $managed_by, Antibot_Global_Firewall_Setting::MANAGED_BY_ALLOWED, true ) ) {
			$this->model_setting->managed_by = $managed_by;
			$this->model_setting->save();

			return true;
		}

		return false;
	}

	/**
	 * Switch the AntiBot managed by.
	 *
	 * @return string|false The managed by value if it's switched, false otherwise.
	 */
	public function switch_managed_by() {
		$managed_by = 'plugin' === $this->get_managed_by() ? 'hosting' : 'plugin';

		if ( $this->set_managed_by( $managed_by ) ) {
			if ( 'plugin' === $managed_by ) {
				return false === $this->managed_by_plugin_action() ? false : $managed_by;
			} else {
				return false === $this->managed_by_hosting_action() ? false : $managed_by;
			}
		}

		return false;
	}

	/**
	 * Action when the AntiBot Global Firewall is enabled/disabled by plugin.
	 *
	 * @param bool $enabled True to enable, false to disable.
	 *
	 * @return bool True if the enabled/disabled action is successful, false otherwise.
	 */
	public function managed_by_plugin_action( bool $enabled = true ): bool {
		if ( $enabled ) {
			set_site_transient( self::IS_SWITCHING_TO_PLUGIN_IN_PROGRESS, true, 30 );

			// Disable AntiBot on hosting if it's currently enabled.
			$this->toggle_on_hosting( false, 0.1 );

			// Schedule a single event to confirm AntiBot is disabled on hosting after 15 seconds.
			if ( ! wp_next_scheduled( 'wpdef_confirm_antibot_toggle_on_hosting' ) ) {
				wp_schedule_single_event( time() + 15, 'wpdef_confirm_antibot_toggle_on_hosting', array( false ) );
			}

			// Enable AntiBot on plugin side.
			$this->model_setting->enabled = true;
			$this->model_setting->save();

			// Download IPs.
			$this->download_and_store_blocklist();
		} else {
			// Disable AntiBot on plugin side.
			$this->model_setting->enabled = false;
			$this->model_setting->save();

			// Remove IPs.
			$this->delete_blocklist();
		}

		return true;
	}

	/**
	 * Action when the AntiBot Global Firewall is enabled/disabled by hosting.
	 *
	 * @param bool $enabled True to enable, false to disable.
	 * @return bool True if the enabled/disabled action is successful, false otherwise.
	 */
	public function managed_by_hosting_action( bool $enabled = true ): bool {
		if ( $enabled ) {
			wp_clear_scheduled_hook( 'wpdef_confirm_antibot_toggle_on_hosting' );
			delete_site_transient( self::IS_SWITCHING_TO_PLUGIN_IN_PROGRESS );

			// Disable AntiBot on plugin side.
			$this->model_setting->enabled = false;
			$this->model_setting->save();

			// Remove IPs from DB table.
			$this->delete_blocklist();

			// Enable AntiBot on hosting if it's currently disabled.
			$result = $this->toggle_on_hosting( true );
			if ( is_wp_error( $result ) ) {
				return false;
			}
		} else {
			$result = $this->toggle_on_hosting( false );
			if ( is_wp_error( $result ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Toggle the AntiBot Global Firewall on hosting.
	 *
	 * @param bool  $enable True to enable, false to disable.
	 * @param float $timeout The timeout for the request.
	 *
	 * @return bool|WP_Error True if the AntiBot Global Firewall is toggled, false if not, WP_Error otherwise.
	 */
	public function toggle_on_hosting( bool $enable, float $timeout = 30 ) {
		if ( ! $this->wpmudev->is_wpmu_hosting() ) {
			return false;
		}

		$hosting_enable = defender_get_hosting_feature_state( 'antibot' );

		if (
			( $enable && ! $hosting_enable ) ||
			( ! $enable && $hosting_enable )
		) {
			$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );

			$body = array( 'is_active' => $enable );
			if ( $enable ) {
				$body['mode'] = $this->get_hosting_mode();
			}

			$data = $this->make_wpmu_request(
				WPMUDEV::API_ANTIBOT_GLOBAL_FIREWALL,
				$body,
				array(
					'method'  => 'PUT',
					'timeout' => $timeout,
				)
			);

			if ( $timeout > 1 && is_wp_error( $data ) ) {
				$this->log( 'AntiBot Global Firewall Error: ' . $data->get_error_message(), Firewall::FIREWALL_LOG );
				return $data;
			}
		}

		return true;
	}

	/**
	 * Fetches the number of blocklisted IPs from cache or from the Blocklist API.
	 *
	 * If the value is not cached, it will fetch the number of blocklisted IPs from the Blocklist API and cache it.
	 *
	 * @return int The number of blocklisted IPs.
	 */
	public function get_cached_blocklisted_ips(): int {
		$mode        = $this->frontend_mode();
		$stats_key   = self::BLOCKLIST_STATS_KEY . '_' . $mode;
		$cached_data = get_site_transient( $stats_key );
		if ( false !== $cached_data ) {
			return (int) $cached_data;
		}

		$blocklist_stats = $this->antibot_client->get_blocklist_stats();

		if ( is_wp_error( $blocklist_stats ) ) {
			$this->log( 'AntiBot Global Firewall Error: ' . $blocklist_stats->get_error_message(), Firewall::FIREWALL_LOG );
			return 0;
		}

		$blocklisted_ips_key = Antibot_Global_Firewall_Setting::MODE_BASIC === $mode ? 'blocked_ips' : 'strict_blocked_ips';
		if ( empty( $blocklist_stats[ $blocklisted_ips_key ] ) ) {
			$this->log( 'AntiBot Global Firewall Error: Stats missing for mode: ' . $mode, Firewall::FIREWALL_LOG );
			return 0;
		}

		$blocklisted_ips = $blocklist_stats[ $blocklisted_ips_key ];
		set_site_transient( $stats_key, $blocklisted_ips, 12 * HOUR_IN_SECONDS );

		return $blocklisted_ips;
	}

	/**
	 * Confirm the toggle on hosting.
	 *
	 * @param bool $enable True to enable, false to disable.
	 *
	 * @return void
	 */
	public function confirm_toggle_on_hosting( bool $enable ): void {
		$this->toggle_on_hosting( $enable );
		delete_site_transient( self::IS_SWITCHING_TO_PLUGIN_IN_PROGRESS );
	}

	/**
	 * Logs IP-related messages if logging is enabled via filter.
	 *
	 * @param string $message The message to be logged.
	 */
	public function log_ip_message( string $message ): void {
		/**
		 * Filters whether IP logging is enabled.
		 *
		 * This filter allows developers to enable or disable IP logging globally.
		 * Returning false will prevent the log message from being written.
		 *
		 * @param bool $is_enabled Whether IP logging is enabled. Default true.
		 * @since 5.1.0
		 */
		$is_logging_enabled = (bool) apply_filters( 'wpdef_antibot_global_firewall_ip_log', true );

		if ( ! $is_logging_enabled ) {
			return;
		}

		$this->log( $message, self::LOG_FILE_NAME );
	}

	/**
	 * Get the AntiBot mode status in DB.
	 *
	 * @return string The AntiBot mode.
	 */
	public function get_mode(): string {
		return $this->model_setting->mode;
	}

	/**
	 * Get the AntiBot mode on WPMU DEV.
	 *
	 * @return string The AntiBot mode label.
	 */
	public function get_hosting_mode(): string {
		$mode = defender_get_hosting_feature_state( 'antibot_mode' );

		return '' !== $mode ? $mode : Antibot_Global_Firewall_Setting::MODE_BASIC;
	}

	/**
	 * Get AntiBot mode based on managed by.
	 *
	 * @return string The AntiBot mode.
	 */
	public function frontend_mode(): string {
		return 'plugin' === $this->get_managed_by() ?
			$this->get_mode() :
			$this->get_hosting_mode();
	}

	/**
	 * Switch the AntiBot mode.
	 *
	 * @return string|false|WP_Error The AntiBot mode value if it's switched, false otherwise.
	 */
	public function switch_mode() {
		if ( 'plugin' === $this->get_managed_by() ) {
			$mode = $this->get_mode();

			$this->model_setting->mode = Antibot_Global_Firewall_Setting::MODE_STRICT === $mode
				? Antibot_Global_Firewall_Setting::MODE_BASIC
				: Antibot_Global_Firewall_Setting::MODE_STRICT;
			$this->model_setting->save();

			$this->download_and_store_blocklist();

			delete_site_transient( self::BLOCKLIST_STATS_KEY . '_' . $mode );
		} else {
			if ( ! $this->wpmudev->is_wpmu_hosting() ) {
				return false;
			}

			$mode = $this->get_hosting_mode();

			$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
			$data = $this->make_wpmu_request(
				WPMUDEV::API_ANTIBOT_GLOBAL_FIREWALL,
				array(
					'is_active' => $this->hosting_is_enabled(),
					'mode'      => Antibot_Global_Firewall_Setting::MODE_STRICT === $mode
						? Antibot_Global_Firewall_Setting::MODE_BASIC
						: Antibot_Global_Firewall_Setting::MODE_STRICT,
				),
				array( 'method' => 'PUT' )
			);

			if ( is_wp_error( $data ) ) {
				$this->log( 'AntiBot Global Firewall Error: ' . $data->get_error_message(), Firewall::FIREWALL_LOG );
				return $data;
			}

			delete_site_transient( self::BLOCKLIST_STATS_KEY . '_' . $mode );
		}

		return $this->frontend_mode();
	}

	/**
	 * Clear antibot table when site is disconnected from HUB.
	 *
	 * @return void
	 */
	public function clear_antibot_on_disconnection(): void {
		if ( $this->is_site_connected_to_hub_via_hcm_or_dash() ) {
			return;
		}
		if ( $this->get_cached_blocklisted_ips() <= 0 ) {
			return;
		}
		$this->delete_blocklist();
		delete_site_transient( self::BLOCKLIST_STATS_KEY . '_' . $this->get_mode() );
		delete_site_transient( self::BLOCKLIST_STATS_KEY . '_' . $this->get_hosting_mode() );
		$this->log( 'Antibot table cleared due to site disconnection.', self::LOG_FILE_NAME );
	}
}