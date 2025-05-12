<?php
/**
 * Responsible for handling audit logs.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Error;
use DateTime;
use Exception;
use Countable;
use DateInterval;
use WP_Defender\Traits\IO;
use WP_Defender\Component;
use WP_Defender\Traits\Formats;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Setting\Audit_Logging;

/**
 * Provides methods for fetching, querying, and managing audit logs.
 */
class Audit extends Component {

	use IO;
	use Formats;

	public const AUDIT_LOG             = 'audit.log';
	public const CACHE_LAST_CHECKPOINT = 'wd_audit_fetch_checkpoint';

	/**
	 * Fetches audit logs, either from local storage or via API if not available locally.
	 *
	 * @param  int    $date_from  Start date for fetching logs.
	 * @param  int    $date_to  End date for fetching logs.
	 * @param  array  $events  Specific events to fetch.
	 * @param  string $user_id  User ID to filter logs.
	 * @param  string $ip  IP address to filter logs.
	 * @param  int    $paged  Pagination page number.
	 *
	 * @return Audit_Log[]|WP_Error Returns an array of Audit_Log objects or WP_Error on failure.
	 * @throws Exception Throws exception on failure.
	 */
	public function fetch( $date_from, $date_to, $events = array(), $user_id = '', $ip = '', $paged = 1 ) {
		$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
		$this->log( sprintf( 'Found %s from local', count( $internal ) ), self::AUDIT_LOG );
		$checkpoint = get_site_option( self::CACHE_LAST_CHECKPOINT );
		if ( false === $checkpoint ) {
			// This case where user install the plugin, have some local data but never reach to Logs page, then check point will be today.
			$checkpoint = time();
		}
		$checkpoint = (int) $checkpoint;
		$date_from  = (int) $date_from;
		if ( 0 === count( $internal ) && $checkpoint > $date_from ) {
			// Have to fetch from API.
			$this->log( 'fetch from cloud', self::AUDIT_LOG );
			// Todo:need $paged as 'nopaging'-arg?
			$cloud = $this->query_from_api( $date_from, $date_to );
			if ( is_wp_error( $cloud ) ) {
				$this->log( sprintf( 'Fetch error %s', $cloud->get_error_message() ), self::AUDIT_LOG );

				return $cloud;
			}
			if ( count( $cloud ) ) {
				// No data from cloud too.
				Audit_Log::mass_insert( $cloud );
				// Because this is roughly fetch, so we have to filter out again using the local data.
				$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
			}
			// Cache the last time fetch, this will be useful in case of mixed data.
			update_site_option( self::CACHE_LAST_CHECKPOINT, $date_from );
			// This case we have the data, however, maybe it can be out of the cached range, so we have to check.
			// Note that, the out of range only happen with date_from, as the local always have the newest data.
		} elseif ( $checkpoint > $date_from ) {
			// We have some data out of range, fetch and cache.
			$this->log(
				sprintf(
					'checkpoint %s - date from %s',
					wp_date( 'Y-m-d H:i:s', $checkpoint ),
					wp_date( 'Y-m-d H:i:s', $date_from )
				),
				self::AUDIT_LOG
			);
			$cloud = $this->query_from_api( $date_from, $checkpoint );
			if ( is_wp_error( $cloud ) ) {
				$this->log( sprintf( 'Fetch error %s', $cloud->get_error_message() ), self::AUDIT_LOG );

				return $cloud;
			}
			if ( is_array( $cloud ) ) {
				// Silence the error here, as we actually have data.
				Audit_Log::mass_insert( $cloud );
				$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
				// Cache the last time fetch, this will be useful in case of mixed data.
				update_site_option( self::CACHE_LAST_CHECKPOINT, $date_from );
			}
		}

		return $internal;
	}

	/**
	 * Queries audit logs from the API.
	 *
	 * @param  int $date_from  Start date for the query.
	 * @param  int $date_to  End date for the query.
	 *
	 * @return array|WP_Error Returns an array of logs or WP_Error on failure.
	 * @throws Exception Throws exception on failure.
	 */
	public function query_from_api( $date_from, $date_to ) {
		$date_format = 'Y-m-d H:i:s';
		$date_to     = wp_date( $date_format, $date_to );
		$date_from   = wp_date( $date_format, $date_from );
		$args        = array(
			'site_url'  => network_site_url(),
			'order_by'  => 'timestamp',
			'order'     => 'desc',
			'nopaging'  => true,
			'timezone'  => get_option( 'gmt_offset' ),
			'date_from' => $date_from,
			'date_to'   => $date_to,
		);

		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$data = $this->make_wpmu_request(
			WPMUDEV::API_AUDIT,
			$args,
			array( 'method' => 'GET' ),
			true
		);

		if ( is_wp_error( $data ) ) {
			$this->log( sprintf( 'Fetch error %s', $data->get_error_message() ), self::AUDIT_LOG );

			return $data;
		}

		if ( 'success' !== $data['status'] ) {
			return new WP_Error(
				Error_Code::API_ERROR,
				esc_html__( 'Something wrong happen, please try again!', 'wpdef' )
			);
		}

		return $data['data'];
	}

	/**
	 * Flushes logs that need to be synced with the cloud.
	 */
	public function flush() {
		$logs = Audit_Log::get_logs_need_flush();
		// Build the data.
		$data = array();
		foreach ( $logs as $log ) {
			$item = $log->export();
			unset( $item['synced'] );
			unset( $item['safe'] );
			unset( $item['id'] );
			$item['msg'] = addslashes( $item['msg'] );
			$data[]      = $item;
		}

		if ( count( $data ) ) {
			$ret = $this->curl_to_api( $data );
			if ( ! is_wp_error( $ret ) ) {
				foreach ( $logs as $log ) {
					$log->synced = 1;
					$log->save();
				}
			}
		}
	}

	/**
	 * Cleans up old logs based on storage settings.
	 *
	 * @throws Exception When the $duration cannot be parsed as an interval.
	 */
	public function audit_clean_up_logs() {
		$audit_settings = wd_di()->get( Audit_Logging::class );
		$interval       = $this->calculate_date_interval( $audit_settings->storage_days );
		$date_from      = ( new DateTime() )->setTimezone( wp_timezone() )
											->sub( new DateInterval( 'P1Y' ) )
											->setTime( 0, 0, 0 );
		$date_to        = ( new DateTime() )->setTimezone( wp_timezone() )
											->sub( new DateInterval( $interval ) );

		if ( $date_from < $date_to ) {
			// Count the logs that should be deleted.
			$logs_count = Audit_Log::count( $date_from->getTimestamp(), $date_to->getTimestamp() );
			if ( $logs_count > 0 ) {
				Audit_Log::delete_old_logs(
					$date_from->getTimestamp(),
					$date_to->getTimestamp(),
					// Since v5.0.0.
					(int) apply_filters( 'wpdef_audit_limit_deleted_logs', 50 )
				);
			}
		}
	}

	/**
	 * Sends data to the API using cURL.
	 *
	 * @param  array $data  Data to be sent to the API.
	 *
	 * @return mixed Returns the response from the API.
	 */
	public function curl_to_api( $data ) {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$ret = $this->make_wpmu_request(
			WPMUDEV::API_AUDIT_ADD,
			$data,
			array(
				'method'  => 'POST',
				'timeout' => 3,
				'headers' => array(
					'apikey' => $this->get_apikey(),
				),
			)
		);

		return $ret;
	}

	/**
	 * Sends data to the API using a socket connection.
	 *
	 * @param  array $data  Data to be sent to the API.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function socket_to_api( $data ) {
		$sockets = Array_Cache::get( 'sockets', 'audit', array() );
		// We will need to wait a bit.
		if ( 0 === ( is_array( $sockets ) || $sockets instanceof Countable ? count( $sockets ) : 0 ) ) {
			// Fall back.
			return false;
		}
		$this->log(
			sprintf( 'Flush %s to cloud', is_array( $data ) || $data instanceof Countable ? count( $data ) : 0 ),
			self::AUDIT_LOG
		);
		$start_time = microtime( true );
		$sks        = $sockets;
		$r          = null;
		$e          = null;
		if ( ( false === stream_select( $r, $sks, $e, 1 ) ) ) {
			// This case error happen.
			return false;
		}

		$fp = array_shift( $sockets );

		$uri  = '/logs/add_multiple';
		$vars = http_build_query( $data );
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		// We're sending data to the server, so we're not manipulating files. Ignore the error.
		// @codingStandardsIgnoreStart
		fwrite( $fp, 'POST ' . $uri . "  HTTP/1.1\r\n" );
		fwrite( $fp, 'Host: ' . $this->strip_protocol( $this->get_endpoint() ) . "\r\n" );
		fwrite( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
		fwrite( $fp, 'Content-Length: ' . strlen( $vars ) . "\r\n" );
		fwrite( $fp, 'apikey: ' . $this->get_apikey() . "\r\n" );
		fwrite( $fp, "Connection: close\r\n" );
		fwrite( $fp, "\r\n" );
		fwrite( $fp, $vars );
		stream_set_timeout( $fp, 5 );
		$res = '';
		while ( ! feof( $fp ) ) {
			$res .= fgets( $fp, 1024 );
			// Check if the transfer has taken too long.
			$end_time = microtime( true );
			if ( $end_time - $start_time > 3 ) {
				fclose( $fp );
				break;
			}
		}
		// @codingStandardsIgnoreEnd
		return true;
	}

	/**
	 * Open a socket to API for faster transmit.
	 */
	public function open_socket() {
		$sockets  = Array_Cache::get( 'sockets', 'audit', array() );
		$endpoint = $this->strip_protocol( $this->get_endpoint() );
		if ( empty( $sockets ) ) {
			$fp = stream_socket_client(
				'ssl://' . $endpoint . ':443',
				$errno,
				$errstr,
				5
			);
			if ( is_resource( $fp ) ) {
				Array_Cache::set( 'sockets', array( $fp ), 'audit' );
			}
		}
	}

	/**
	 * Strips the protocol from a URL.
	 *
	 * @param  string $url  URL to process.
	 *
	 * @return string Returns the URL without the protocol.
	 */
	private function strip_protocol( $url ) {
		$parts = wp_parse_url( $url );
		$host  = $parts['host'] . ( $parts['path'] ?? null );

		return rtrim( $host, '/' );
	}

	/**
	 * Returns the API endpoint.
	 *
	 * @return string Returns the API endpoint URL.
	 */
	private function get_endpoint(): string {
		return defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
			? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' )
			: 'https://audit.wpmudev.org/';
	}

	/**
	 * Queue all the events listeners, so we can listen and build log base on user behaviors.
	 * Never catch if it runs from WP CLI or CRON.
	 */
	public function enqueue_event_listener() {
		if ( ! wp_doing_cron() && ! defender_is_wp_cli() ) {
			$events_class = array(
				new Component\Audit\Comment_Audit(),
				new Component\Audit\Core_Audit(),
				new Component\Audit\Media_Audit(),
				new Component\Audit\Post_Audit(),
				new Component\Audit\Users_Audit(),
				new Component\Audit\Options_Audit(),
				new Component\Audit\Menu_Audit(),
			);

			foreach ( $events_class as $class ) {
				// since 2.4.7.
				$hooks = apply_filters( 'wp_defender_audit_hooks', $class->get_hooks() );
				foreach ( $hooks as $key => $hook ) {
					$func = function () use ( $key, $hook, $class ) {
						global $wp_filter;
						if ( isset( $wp_filter['gettext'] ) ) {
							$gettext_callbacks = $wp_filter['gettext']->callbacks;

							// Disable all gettext filters.
							$wp_filter['gettext']->callbacks = array();
						}

						// This is arguments of the hook.
						$args = func_get_args();
						// This is hook data, defined in each event class.
						$class->build_log_data( $key, $args, $hook );

						// Add all filters back for gettext.
						if ( isset( $wp_filter['gettext'], $gettext_callbacks ) ) {
							$wp_filter['gettext']->callbacks = $gettext_callbacks;
						}
					};
					add_action(
						$key,
						$func,
						11,
						is_array( $hook['args'] ) || $hook['args'] instanceof Countable ? count( $hook['args'] ) : 0
					);
				}
			}
		}
	}

	/**
	 * Logs audit events.
	 */
	public function log_audit_events() {
		$events = Array_Cache::get( 'logs', 'audit', array() );

		if ( ! ( is_array( $events ) || $events instanceof Countable ? count( $events ) : 0 )
			|| ! class_exists( Audit_Log::class )
		) {
			return;
		}
		$model = new Audit_Log();

		if ( ( is_array( $events ) || $events instanceof Countable ? count( $events ) : 0 ) > 1 ) {
			if ( $model->has_method( 'mass_insert' ) ) {
				$model->mass_insert( $events );

				return;
			}
		}

		foreach ( $events as $event ) {
			$model->import( $event );
			$model->synced = 0;
			$model->save();
		}
	}
}