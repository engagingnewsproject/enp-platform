<?php

namespace WP_Defender\Model;

use WP_Defender\DB;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Traits\Formats;
use WP_Defender\Component\Table_Lockout;

class Lockout_Log extends DB {
	use Formats;

	const AUTH_FAIL  = 'auth_fail', AUTH_LOCK = 'auth_lock';
	const ERROR_404  = '404_error', LOCKOUT_404 = '404_lockout', ERROR_404_IGNORE = '404_error_ignore';
	const LOCKOUT_UA = 'ua_lockout';

	protected $table = 'defender_lockout_log';

	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * @var string
	 * @defender_property
	 */
	public $log;
	/**
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * @var int
	 * @defender_property
	 */
	public $date;
	/**
	 * @var string
	 * @defender_property
	 */
	public $user_agent;
	/**
	 * @var string
	 * @defender_property
	 */
	public $type;
	/**
	 * @var int
	 * @defender_property
	 */
	public $blog_id;
	/**
	 * @var string
	 * @defender_property
	 */
	public $tried;

	/**
	 * Pulling the logs as data, use in Logs tab
	 * $filters will have those params
	 *  -date_from
	 *  -date_to
	 * == Defaults is 7 days and always require
	 *  -type: optional
	 *  -ip: optional.
	 *
	 * @param array $filters
	 * @param int $paged
	 * @param string $order_by
	 * @param string $order
	 * @param int $page_size
	 *
	 * @return Lockout_Log[]
	 */
	public static function query_logs(
		$filters = array(),
		$paged = 1,
		$order_by = 'id',
		$order = 'desc',
		$page_size = 50
	) {
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

		if ( isset( $filters['ip'] ) && ! empty( $filters['ip'] ) ) {
			$orm->where( 'ip', 'like', '%' . $filters['ip'] . '%' );
		}
		if ( isset( $filters['type'] ) && ! empty( $filters['type'] ) ) {
			$orm->where( 'type', $filters['type'] );
		}

		if ( ! empty( $filters['ban_status'] ) ) {
			$ban_status_where = self::ban_status_where( $filters['ban_status'] );

			if ( 3 === count( $ban_status_where ) ) {
				$orm->where( ... $ban_status_where );
			}
		}

		if ( ! empty( $order_by ) && ! empty( $order ) ) {
			$orm->order_by( $order_by, $order );
		}
		if ( false !== $page_size ) {
			$offset = ( $paged - 1 ) * $page_size;
			$orm->limit( "$offset,$page_size" );
		}

		return $orm->get();
	}

	/**
	 * This similar to @query_logs, but we count the total row.
	 *
	 * @param $date_from
	 * @param $date_to
	 * @param string $type
	 * @param string $ip
	 * @param array  $filters Array consists key value pair to pass in SQL where condition.
	 *
	 * @return string|null
	 */
	public static function count( $date_from, $date_to, $type = '', $ip = '', $filters = array() ) {
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

		if ( ! empty( $type ) ) {
			if ( is_array( $type ) ) {
				$orm->where( 'type', 'in', $type );
			} else {
				$orm->where( 'type', $type );
			}
		}

		if ( ! empty( $ip ) ) {
			$orm->where( 'ip', 'like', "%$ip%" );
		}

		if ( ! empty( $filters['ban_status'] ) ) {
			$ban_status_where = self::ban_status_where( $filters['ban_status'] );

			if ( 3 === count( $ban_status_where ) ) {
				$orm->where( ... $ban_status_where );
			}
		}

		return $orm->count();
	}

	/**
	 * Count login lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_login_lockout_last_7_days() {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::AUTH_LOCK );
	}

	/**
	 * Count 404 lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_404_lockout_last_7_days() {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::LOCKOUT_404 );
	}

	/**
	 * Count UA lockout in the last 7 days.
	 *
	 * @return string|null
	 */
	public static function count_ua_lockout_last_7_days() {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::LOCKOUT_UA );
	}

	/**
	 * A shortcut for quickly count lockout in last 24 hours.
	 *
	 * @return string|null
	 */
	public static function count_lockout_in_24_hours() {
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
	 * A shortcut for quickly count lockout in last 24 hours.
	 *
	 * @return string|null
	 */
	public static function count_lockout_in_7_days() {
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
	public static function count_lockout_in_30_days() {
		$start = strtotime( '-30 days' );
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
	 * Get the last time a lockout happened.
	 *
	 * @param false $for_hub
	 *
	 * @return false|string
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
	 * @return bool
	 */
	public static function truncate() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
				->truncate();
	}

	/**
	 * Remove data by time period.
	 *
	 * @param int $timestamp
	 * @param int $limit
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
	public static function get_summary() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
				->where( 'type', 'in', array( self::LOCKOUT_404, self::AUTH_LOCK, self::LOCKOUT_UA ) )
				->where( 'date', '>=', strtotime( '-30 days', current_time( 'timestamp' ) ) ) // phpcs:ignore
				->order_by( 'id', 'desc' )
				->get();
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	protected static function get_log_tag( $type ) {
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
			case self::LOCKOUT_UA:
			default:
				$tag = 'bots';
				break;
		}

		return $tag;
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	protected static function get_log_tag_class( $type ) {
		switch ( $type ) {
			case self::AUTH_LOCK:
			case self::LOCKOUT_404:
			case self::LOCKOUT_UA:
				$badge_bg = 'bg-badge-red';
				break;
			case self::AUTH_FAIL:
			case self::ERROR_404:
			case self::ERROR_404_IGNORE:
			default:
				$badge_bg = 'bg-badge-green';
				break;
		}

		return $badge_bg;
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	protected static function get_log_container_class( $type ) {
		switch ( $type ) {
			case self::AUTH_LOCK:
			case self::LOCKOUT_404:
			case self::LOCKOUT_UA:
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
	 * Get data from db and format it for ready to use on frontend.
	 *
	 * @param array  $filters
	 * @param int    $paged
	 * @param string $order_by
	 * @param string $order
	 * @param int    $page_size
	 *
	 * @return array
	 */
	public static function get_logs_and_format(
		$filters = array(),
		$paged = 1,
		$order_by = 'id',
		$order = 'desc',
		$page_size = 50
	) {
		$logs     = self::query_logs( $filters, $paged, $order_by, $order, $page_size );
		$data     = array();
		$ua_model = wd_di()->get( User_Agent_Lockout::class );
		foreach ( $logs as $item ) {
			$ip_model               = Lockout_Ip::get( $item->ip );
			$log                    = $item->export();
			$log['date']            = $item->format_date_time( $item->date );
			$log['format_date']     = $item->get_date( $item->date );
			$log['tag']             = self::get_log_tag( $item->type );
			$log['tag_class']       = self::get_log_tag_class( $item->type );
			$log['container_class'] = self::get_log_container_class( $item->type );
			if ( self::LOCKOUT_UA === $item->type ) {
				if ( \WP_Defender\Component\User_Agent::REASON_BAD_POST === $item->tried ) {
					$log['description'] = __( 'Lockout occurred due to attempted access with empty User-Agent and Referer headers. By default, IP addresses that send POST requests with empty User-Agent and Referer headers will be automatically banned. You can disable this option in the User Agent Banning settings, or you can unban the locked out IP address below.', 'wpdef' );
					$log['type_label']  = __( 'Type', 'wpdef' );
					$log['type_value']  = __( 'Empty Headers', 'wpdef' );
					$arr_statuses       = $ip_model->get_access_status();
				} else {
					$log['description'] = sprintf(
					/* translators: %s: log, %d: user_agent */
						__( '%1$s: <strong>%2$s</strong>. This user agent is considered bad bots and may harm your site.', 'wpdef' ),
						$item->log,
						$item->user_agent
					);
					$log['type_label'] = __( 'User Agent name', 'wpdef' );
					$log['type_value'] = $item->user_agent;
					$arr_statuses      = $ua_model->get_access_status( $item->user_agent );
				}
			} else {
				$log['description'] = $item->log;
				$log['type_label']  = __( 'Type', 'wpdef' );
				$log['type_value']  = str_replace( '_', ' ', $item->type );
				$arr_statuses       = $ip_model->get_access_status();
			}
			// There may be several statuses.
			$log['access_status']      = $arr_statuses;
			$log['access_status_text'] = $ip_model->get_access_status_text( $arr_statuses[0] );
			$data[]                    = $log;
		}

		return $data;
	}

	/**
	 * Get the first log by ID.
	 *
	 * @param int $id
	 *
	 * @return null|object
	 */
	public static function find_by_id( $id ) {
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
	 * Prepare user-agent where condition based on the ban status variant.
	 *
	 * @param string $ban_status_type Ban status type.
	 *
	 * @return array Where condition arguments or empty array.
	 */
	private static function ban_status_where( $ban_status_type ) {
		$table_lockout = wd_di()->get( Table_Lockout::class );

		if ( $table_lockout::STATUS_NOT_BAN === $ban_status_type ) {
			$ua_model = wd_di()->get( User_Agent_Lockout::class );

			$blocklist = $ua_model->get_lockout_list( 'blocklist' );
			$allowlist = $ua_model->get_lockout_list( 'allowlist' );

			$all = array_merge( $blocklist, $allowlist );

			return array( 'user_agent', 'not regexp', implode( '|', $all ) );
		} elseif ( $table_lockout::STATUS_BAN === $ban_status_type ) {
			$ua_model = wd_di()->get( User_Agent_Lockout::class );

			$blocklist = $ua_model->get_lockout_list( 'blocklist' );

			return array( 'user_agent', 'regexp', implode( '|', $blocklist ) );
		} elseif ( $table_lockout::STATUS_ALLOWLIST === $ban_status_type ) {
			$ua_model = wd_di()->get( User_Agent_Lockout::class );

			$allowlist = $ua_model->get_lockout_list( 'allowlist' );

			return array( 'user_agent', 'regexp', implode( '|', $allowlist ) );
		}

		return array();
	}
}
