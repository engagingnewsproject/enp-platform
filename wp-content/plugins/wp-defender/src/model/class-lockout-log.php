<?php
/**
 * Handles interactions with the database table for lockout logs.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;
use Calotes\Base\Model;
use WP_Defender\Traits\Formats;
use WP_Defender\Component\User_Agent;
use WP_Defender\Component\Table_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;

/**
 * Model for the lockout log table.
 */
class Lockout_Log extends DB {

	use Formats;

	public const AUTH_FAIL = 'auth_fail';
	public const AUTH_LOCK = 'auth_lock';
	public const IP_UNLOCK = 'ip_unlock';

	public const ERROR_404             = '404_error';
	public const LOCKOUT_404           = '404_lockout';
	public const ERROR_404_IGNORE      = '404_error_ignore';
	public const LOCKOUT_MALICIOUS_BOT = 'malicious_bot';
	public const LOCKOUT_FAKE_BOT      = 'fake_bot';

	public const LOCKOUT_UA = 'ua_lockout';
	// Different IP Lockout types.
	public const LOCKOUT_IP_CUSTOM = 'custom_lockout';

	public const INFINITE_SCROLL_SIZE = 50;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_lockout_log';

	/**
	 * Primary key column.
	 *
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Table column for log.
	 *
	 * @var string
	 * @defender_property
	 */
	public $log;
	/**
	 * Table column for IP address.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * Table column for date.
	 *
	 * @var int
	 * @defender_property
	 */
	public $date;
	/**
	 * Table column for user agent.
	 *
	 * @var string
	 * @defender_property
	 */
	public $user_agent;
	/**
	 * Table column for type.
	 *
	 * @var string
	 * @defender_property
	 */
	public $type;
	/**
	 * Table column for blog id.
	 *
	 * @var int
	 * @defender_property
	 */
	public $blog_id;
	/**
	 * Table column for tried.
	 *
	 * @var string
	 * @defender_property
	 */
	public $tried;
	/**
	 * Table column for country iso code.
	 *
	 * @var string
	 * @defender_property
	 */
	public $country_iso_code;

	/**
	 * Query the logs based on the provided filters and pagination settings.
	 *
	 * @param  array  $filters  An array of filters to apply to the query. Default is an empty array.
	 *                             The following filters are supported:
	 *                             - from: The start date for the date range filter.
	 *                             - to: The end date for the date range filter.
	 *                             - ip: The IP address to filter by.
	 *                             - type: The type of log to filter by.
	 *                             - ban_status: The ban status to filter by.
	 * @param  int    $paged  The page number of the results to retrieve. Default is 1.
	 * @param  string $order_by  The field to order the results by. Default is 'id'.
	 * @param  string $order  The order direction of the results. Default is 'desc'.
	 * @param  int    $page_size  The number of results to retrieve per page. Default is 50.
	 *                                 Set to -1 to retrieve all results.
	 *
	 * @return array An array of Lockout_Log objects representing the queried logs.
	 */
	public static function query_logs(
		$filters = array(),
		$paged = 1,
		$order_by = 'id',
		$order = 'desc',
		$page_size = 50
	): array {
		$orm = self::get_orm();
		$orm->get_repository( self::class )
			->where(
				'date',
				'between',
				array(
					$filters['from'],
					$filters['to'],
				)
			);

		if ( isset( $filters['ip'] ) && '' !== $filters['ip'] ) {
			$orm->where( 'ip', 'like', '%' . $filters['ip'] . '%' );
		}
		if ( isset( $filters['type'] ) && '' !== $filters['type'] ) {
			$orm->where( 'type', $filters['type'] );
		}

		if ( isset( $filters['ban_status'] ) && '' !== $filters['ban_status'] ) {
			self::apply_ban_status_filter( $orm, $filters );
		}

		if ( '' !== $order_by && '' !== $order ) {
			$orm->order_by( $order_by, $order );
		}
		if ( $page_size > 0 ) {
			$offset = ( $paged - 1 ) * $page_size;
			$orm->limit( $page_size, $offset );
		}

		return $orm->get();
	}

	/**
	 * Count the number of records in the database table based on the provided filters.
	 *
	 * @param  mixed  $date_from  The start date for the date range filter.
	 * @param  mixed  $date_to  The end date for the date range filter.
	 * @param  mixed  $type  The type of log to filter by.
	 * @param  string $ip  The IP address to filter by. Default is an empty string.
	 * @param  array  $filters  An array of additional filters to apply to the query. Default is an empty array.
	 *                          The following filters are supported:
	 *                          - ban_status: The ban status to filter by.
	 *
	 * @return string|null The number of records matching the provided filters, or null if an error occurred.
	 */
	public static function count( $date_from, $date_to, $type, $ip = '', $filters = array() ): ?string {
		$orm = self::get_orm();
		$orm->get_repository( self::class )
			->where(
				'date',
				'between',
				array(
					$date_from,
					$date_to,
				)
			);

		if ( is_array( $type ) && array() !== $type ) {
			$orm->where( 'type', 'in', $type );
		} elseif ( is_string( $type ) && '' !== trim( $type ) ) {
			$orm->where( 'type', $type );
		}

		if ( is_string( $ip ) && '' !== trim( $ip ) ) {
			$orm->where( 'ip', 'like', "%$ip%" );
		}

		if ( isset( $filters['ban_status'] ) && '' !== trim( $filters['ban_status'] ) ) {
			$ban_status_where = self::ban_status_where( $filters['ban_status'] );

			if ( 3 === count( $ban_status_where ) ) {
				$orm->where( ...$ban_status_where );
			}
		}

		return $orm->count();
	}

	/**
	 * Count login lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_login_lockout_last_7_days(): ?string {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::AUTH_LOCK );
	}

	/**
	 * Count 404 lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_404_lockout_last_7_days(): ?string {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::LOCKOUT_404 );
	}

	/**
	 * Count UA lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_ua_lockout_last_7_days(): ?string {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::LOCKOUT_UA );
	}

	/**
	 * A shortcut for quickly count lockout in last 24 hours.
	 *
	 * @return string|null
	 */
	public static function count_lockout_in_24_hours(): ?string {
		$start = strtotime( '-24 hours' );
		$end   = time();

		return self::count(
			$start,
			$end,
			array(
				self::AUTH_LOCK,
				self::LOCKOUT_404,
				self::LOCKOUT_UA,
			)
		);
	}

	/**
	 * A shortcut for quickly count lockout in last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_lockout_in_7_days(): ?string {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count(
			$start,
			$end,
			array(
				self::AUTH_LOCK,
				self::LOCKOUT_404,
				self::LOCKOUT_UA,
			)
		);
	}

	/**
	 * A shortcut for count lockout in 30 days.
	 *
	 * @return string|null
	 */
	public static function count_lockout_in_30_days(): ?string {
		$start = strtotime( '-30 days' );
		$end   = time();

		return self::count(
			$start,
			$end,
			array(
				self::AUTH_LOCK,
				self::LOCKOUT_404,
				self::LOCKOUT_UA,
				// LOCKOUT_IP_CUSTOM is not taken into account.
			)
		);
	}

	/**
	 * Retrieves the date of the last lockout that occurred within the last 30 days.
	 *
	 * @param  bool $for_hub  (optional) Whether to format the date for the persistent hub. Default is false.
	 *
	 * @return string The formatted date of the last lockout, or 'n/a' if no lockouts were found.
	 */
	public static function get_last_lockout_date( $for_hub = false ) {
		$data = self::query_logs(
			array(
				'from' => strtotime( '-30 days' ),
				'to'   => time(),
			),
			1,
			'id',
			'desc',
			1
		);
		$last = array_shift( $data );
		if ( ! is_object( $last ) ) {
			return 'n/a';
		}

		return $for_hub
			? $last->persistent_hub_datetime_format( $last->date )
			: $last->format_date_time( $last->date );
	}

	/**
	 * Remove all data.
	 *
	 * @return bool|int
	 */
	public static function truncate() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
					->truncate();
	}

	/**
	 * Remove logs based on timestamp and limit.
	 *
	 * @param  int $timestamp  The timestamp to filter logs by.
	 * @param  int $limit  The maximum number of logs to delete.
	 *
	 * @return void
	 */
	public static function remove_logs( $timestamp, $limit ) {
		$orm = self::get_orm();
		$orm->get_repository( self::class )
			->where( 'date', '<=', $timestamp )
			->order_by( 'id' )
			->limit( $limit )
			->delete_by_limit();
	}

	/**
	 * Get log summary.
	 *
	 * @return array
	 */
	public static function get_summary(): array {
		// Time.
		$current_time     = time();
		$today_midnight   = strtotime( '-24 hours', $current_time );
		$first_this_week  = strtotime( '-7 days', $current_time );
		$first_this_month = strtotime( '-30 days', $current_time );

		// Prepare columns.
		$select = array(
			'MAX(date) as lockout_last',
			'COUNT(*) as lockout_this_month',
			// 24 hours
			"COUNT(IF(date > {$today_midnight}, 1, NULL)) as lockout_today",
			"COUNT(IF(date > {$today_midnight} AND type = '" . self::LOCKOUT_404 . "', 1, NULL)) as lockout_404_today",
			"COUNT(IF(date > {$today_midnight} AND type = '" . self::AUTH_LOCK . "', 1, NULL)) as lockout_login_today",
			"COUNT(IF(date > {$today_midnight} AND type = '" . self::LOCKOUT_UA . "', 1, NULL)) as lockout_ua_today",
			// 7 days
			"COUNT(IF(date > {$first_this_week} AND type = '" . self::LOCKOUT_404 . "', 1, NULL)) as lockout_404_this_week",
			"COUNT(IF(date > {$first_this_week} AND type = '" . self::AUTH_LOCK . "', 1, NULL)) as lockout_login_this_week",
			"COUNT(IF(date > {$first_this_week} AND type = '" . self::LOCKOUT_UA . "', 1, NULL)) as lockout_ua_this_week",
			// 30 days
			"COUNT(IF(date > {$first_this_month} AND type = '" . self::LOCKOUT_404 . "', 1, NULL)) as lockout_404_this_month",
			"COUNT(IF(date > {$first_this_month} AND type = '" . self::AUTH_LOCK . "', 1, NULL)) as lockout_login_this_month",
			"COUNT(IF(date > {$first_this_month} AND type = '" . self::LOCKOUT_UA . "', 1, NULL)) as lockout_ua_this_month",
		);
		$select = implode( ',', $select );

		$orm    = self::get_orm();
		$result = $orm->get_repository( self::class )
						->select( $select )
						// LOCKOUT_IP_CUSTOM is not taken into account.
						->where( 'type', 'in', array( self::LOCKOUT_404, self::AUTH_LOCK, self::LOCKOUT_UA ) )
						->where( 'date', '>=', strtotime( '-30 days', $current_time ) )
						->get_results();

		return $result[0] ?? array();
	}

	/**
	 * Returns the log tag based on the given type.
	 *
	 * @param  string $type  The type of the log.
	 *
	 * @return string The log tag.
	 */
	protected static function get_log_tag( $type ): string {
		switch ( $type ) {
			case self::LOCKOUT_404:
			case self::ERROR_404:
			case self::ERROR_404_IGNORE:
				$tag = '404';
				break;
			case self::AUTH_FAIL:
			case self::AUTH_LOCK:
				$tag = 'login';
				break;
			case self::LOCKOUT_IP_CUSTOM:
				$tag = 'Custom';
				break;
			case self::IP_UNLOCK:
				$tag = 'Unlock';
				break;
			case self::LOCKOUT_UA:
			case self::LOCKOUT_MALICIOUS_BOT:
			case self::LOCKOUT_FAKE_BOT:
			default:
				$tag = 'bots';
				break;
		}

		return $tag;
	}

	/**
	 * Returns the CSS class for the log container based on the given type.
	 *
	 * @param  string $type  The type of the log.
	 *
	 * @return string The CSS class for the log container.
	 */
	protected static function get_log_container_class( $type ): string {
		switch ( $type ) {
			case self::AUTH_LOCK:
			case self::LOCKOUT_404:
			case self::LOCKOUT_UA:
			case self::LOCKOUT_MALICIOUS_BOT:
			case self::LOCKOUT_FAKE_BOT:
				$class = 'sui-error';
				break;
			case self::AUTH_FAIL:
			case self::ERROR_404:
			case self::ERROR_404_IGNORE:
			default:
				$class = 'sui-warning';
				break;
		}

		return $class;
	}

	/**
	 * Retrieves logs from the database and formats them for display on the frontend.
	 *
	 * @param  array  $filters  An array of filters to apply to the query. Default is an empty array.
	 *                             The following filters are supported:
	 *                             - from: The start date for the date range filter.
	 *                             - to: The end date for the date range filter.
	 *                             - ip: The IP address to filter by.
	 *                             - type: The type of log to filter by.
	 *                             - ban_status: The ban status to filter by.
	 * @param  int    $paged  The page number of the results to retrieve. Default is 1.
	 * @param  string $order_by  The field to order the results by. Default is 'id'.
	 * @param  string $order  The order direction of the results. Default is 'desc'.
	 * @param  int    $page_size  The number of results to retrieve per page. Default is 50.
	 *                                 Set to -1 to retrieve all results.
	 *
	 * @return array An array of formatted log entries.
	 */
	public static function get_logs_and_format(
		$filters = array(),
		$paged = 1,
		$order_by = 'id',
		$order = 'desc',
		$page_size = 50
	): array {
		$logs = self::query_logs( $filters, $paged, $order_by, $order, $page_size );

		return self::format_logs( $logs );
	}

	/**
	 * Get the first log by ID.
	 *
	 * @param  int $id  The ID of the log.
	 *
	 * @return null|Model
	 */
	public static function find_by_id( $id ): ?Model {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
					->where( 'id', $id )
					->first();
	}

	/**
	 * Delete current log.
	 */
	public function delete() {
		$orm = self::get_orm();
		$orm->get_repository( self::class )->delete(
			array(
				'id' => $this->id,
			)
		);
	}

	/**
	 * Apply ban status filtering based on lockout type.
	 *
	 * @param object $orm The ORM query builder.
	 * @param array  $filters The filters array.
	 */
	private static function apply_ban_status_filter( $orm, $filters ) {
		$ban_status = $filters['ban_status'];
		$type       = $filters['type'] ?? 'all';

		// Define lockout type categories.
		$ua_types = array( self::get_ua_lockout_types() );

		if ( 'all' === $type || '' === $type ) {
			// For 'all' type, only show UA types with ban_status filtering.
			$ban_status_where = self::ban_status_where( $ban_status );
			if ( 3 === count( $ban_status_where ) ) {
				$orm->where( 'type', 'in', $ua_types );
				$orm->where( ...$ban_status_where );
			}
		} elseif ( in_array( $type, $ua_types, true ) ) {
			// For UA-specific types, apply UA filtering.
			$ban_status_where = self::ban_status_where( $ban_status );
			if ( 3 === count( $ban_status_where ) ) {
				$orm->where( ...$ban_status_where );
			}
		}
	}

	/**
	 * Prepare user-agent where condition based on the ban status variant.
	 *
	 * @param  string $ban_status_type  Ban status type.
	 *
	 * @return array Where condition arguments or empty array.
	 */
	private static function ban_status_where( $ban_status_type ): array {
		$table_lockout = wd_di()->get( Table_Lockout::class );
		$ua_model      = wd_di()->get( User_Agent_Lockout::class );

		if ( $table_lockout::STATUS_NOT_BAN === $ban_status_type ) {
			$blocklist = $ua_model->get_all_selected_blocklist_ua();
			if ( array() === $blocklist ) {
				return array();
			}
			$escaped = array_map( 'preg_quote', $blocklist, array_fill( 0, count( $blocklist ), '' ) );
			return array( 'user_agent', 'not regexp', '(?i)' . implode( '|', $escaped ) );
		} elseif ( $table_lockout::STATUS_BAN === $ban_status_type ) {
			$blocklist = $ua_model->get_all_selected_blocklist_ua();
			if ( array() === $blocklist ) {
				return array();
			}
			$escaped = array_map( 'preg_quote', $blocklist, array_fill( 0, count( $blocklist ), '' ) );
			return array( 'user_agent', 'regexp', '(?i)' . implode( '|', $escaped ) );
		} elseif ( $table_lockout::STATUS_ALLOWLIST === $ban_status_type ) {
			$allowlist = $ua_model->get_lockout_list( 'allowlist' );
			if ( array() === $allowlist ) {
				return array();
			}
			$escaped = array_map( 'preg_quote', $allowlist, array_fill( 0, count( $allowlist ), '' ) );
			return array( 'user_agent', 'regexp', '(?i)' . implode( '|', $escaped ) );
		}

		return array();
	}

	/**
	 * Format logs for ready to use on frontend.
	 *
	 * @param  array $logs  An array of log entries.
	 *
	 * @return array
	 * @since 3.11.0
	 */
	public static function format_logs( array $logs ): array {
		$data     = array();
		$ua_model = wd_di()->get( User_Agent_Lockout::class );
		foreach ( $logs as $item ) {
			$ip_model = Lockout_Ip::get( $item->ip );

			// Escape object properties received from end user.
			$item->log   = sanitize_textarea_field( $item->log );
			$item->tried = sanitize_textarea_field( $item->tried );

			$arr_ip_statuses = $ip_model->get_access_status();

			$log = $item->export();

			// Escape array keys received from end user.
			$log['log']   = sanitize_textarea_field( $log['log'] );
			$log['tried'] = sanitize_textarea_field( $log['tried'] );

			$log['date']            = $item->format_date_time( $item->date );
			$log['format_date']     = $item->get_date( $item->date );
			$log['tag']             = self::get_log_tag( $item->type );
			$log['container_class'] = self::get_log_container_class( $item->type );
			if ( self::LOCKOUT_UA === $item->type ) {
				if ( User_Agent::REASON_BAD_POST === $item->tried ) {
					$log['description'] = esc_html__(
						'Lockout occurred due to attempted access with empty User-Agent and Referer headers. By default, IP addresses that send POST requests with empty User-Agent and Referer headers will be automatically banned. You can disable this option in the User Agent Banning settings, or you can unban the locked out IP address below.',
						'wpdef'
					);
					$log['type_label']  = esc_html__( 'Type', 'wpdef' );
					$log['type_value']  = esc_html__( 'Empty Headers', 'wpdef' );
					$arr_statuses       = $arr_ip_statuses;
				} else {
					$log['description'] = sprintf(
					/* translators: 1. Log. 2. User agent. */
						esc_html__(
							'%1$s: %2$s. This user agent is considered bad bots and may harm your site.',
							'wpdef'
						),
						sanitize_textarea_field( $item->log ),
						'<strong>' . sanitize_textarea_field( $item->user_agent ) . '</strong>'
					);
					$log['type_label']       = esc_html__( 'User Agent name', 'wpdef' );
					$log['type_value']       = sanitize_textarea_field( $item->user_agent );
					$log['access_status_ip'] = $arr_ip_statuses;
					$arr_statuses            = $ua_model->get_access_status( $item->user_agent );
				}
			} else {
				$log['description'] = sanitize_textarea_field( $item->log );
				$log['type_label']  = esc_html__( 'Type', 'wpdef' );
				$log['type_value']  = str_replace( '_', ' ', $item->type );
				$arr_statuses       = $arr_ip_statuses;

				if ( 'malicious_bot' === $item->type ) {
					$log['access_status_ua'] = $ua_model->get_access_status( $item->user_agent );
				}
			}
			// There may be several statuses.
			$log['access_status'] = $arr_statuses;

			// For UA lockout types, show UA status; for others show IP status.
			if ( in_array( $item->type, array( self::get_ua_lockout_types() ), true ) ) {
				$ua_statuses               = $ua_model->get_access_status( $item->user_agent );
				$log['access_status_text'] = $ip_model->get_access_status_text( $ua_statuses[0] ?? 'na' );
			} else {
				$log['access_status_text'] = $ip_model->get_access_status_text( $arr_statuses[0] );
			}
			$data[] = $log;
		}

		return $data;
	}

	/**
	 * Determine if the IP should be added to the database based on the timeframe.
	 *
	 * @return bool True if the IP should be added to the database, false otherwise.
	 */
	public function has_recent_ip_log(): bool {
		// Ensure IP is set before proceeding.
		if ( ! is_string( $this->ip ) || '' === trim( $this->ip ) ) {
			return false;
		}

		$orm = self::get_orm();
		// Query the latest log for the current IP.
		$latest_log = $orm->get_repository( self::class )
					->select( 'date' )
					->where( 'ip', $this->ip )
					->order_by( 'date', 'desc' )
					->first();

		if ( null !== $latest_log ) {
			// Return true if the log is within the 5-minute timeframe.
			return ( time() - $latest_log->date ) <= 300;
		}

		return false;
	}

	/**
	 * Get UA lockout types.
	 *
	 * @return array
	 */
	public static function get_ua_lockout_types(): array {
		return array(
			self::LOCKOUT_UA,
			self::LOCKOUT_MALICIOUS_BOT,
			self::LOCKOUT_FAKE_BOT,
		);
	}
}