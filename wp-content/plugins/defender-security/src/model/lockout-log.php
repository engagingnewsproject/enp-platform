<?php

namespace WP_Defender\Model;

use Calotes\Helper\Array_Cache;
use WP_Defender\DB;
use WP_Defender\Traits\Formats;

class Lockout_Log extends DB {
	use Formats;

	const AUTH_FAIL = 'auth_fail', AUTH_LOCK = 'auth_lock', ERROR_404 = '404_error', LOCKOUT_404 = '404_lockout', ERROR_404_IGNORE = '404_error_ignore';

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
	 * @var int
	 * @defender_property
	 */
	public $tried;

	/**
	 * Pulling the logs data, use in Logs tab
	 * $filters will have those params
	 *  -date_from
	 *  -date_to
	 * == Defaults is 7 days and always require
	 *  -type: optional
	 *  -ip: optional
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
	 * This similar to @query_logs, but we count the total row
	 *
	 * @param $date_from
	 * @param $date_to
	 * @param string $type
	 * @param string $ip
	 *
	 * @return string|null
	 */
	public static function count( $date_from, $date_to, $type = '', $ip = '' ) {
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

		return $orm->count();
	}

	/**
	 * Count login lockout in the last 7 days
	 * @return string|null
	 */
	public static function count_login_lockout_last_7_days() {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::AUTH_LOCK );
	}

	/**
	 * Count 404 lockout in the last 7 days
	 * @return string|null
	 */
	public static function count_404_lockout_last_7_days() {
		$start = strtotime( '-7 days' );
		$end   = time();

		return self::count( $start, $end, self::LOCKOUT_404 );
	}

	/**
	 * A shortcut for quickly count lockout in last 24 hours
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
			)
		);
	}

	/**
	 * A shortcut for quickly count lockout in last 24 hours
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
			)
		);
	}

	/**
	 * A shortcut for count lockout in 30 days
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
			)
		);
	}

	/**
	 * Get the last time a lockout happen
	 * @return false|string
	 */
	public static function get_last_lockout_date() {
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

		return $last->format_date_time( $last->date );
	}

	/**
	 * Remove all data
	 *
	 * @return bool
	 */
	public static function truncate() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
				->truncate();
	}

	/**
	 * Remove data by time period
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
	 * Get log summary
	 *
	 * @return array
	 */
	public static function get_summary() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
				->where( 'type', 'in', array( self::LOCKOUT_404, self::AUTH_LOCK ) )
				->where( 'date', '>=', strtotime( '-30 days', current_time( 'timestamp' ) ) ) // phpcs:ignore
				->order_by( 'id', 'desc' )
				->get();
	}

	/**
	 * Get data from db and format it for ready to use on frontend
	 *
	 * @param array $filters
	 * @param int $paged
	 * @param string $order_by
	 * @param string $order
	 * @param int $page_size
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
		$logs = self::query_logs( $filters, $paged, $order_by, $order, $page_size );
		$data = array();
		foreach ( $logs as $item ) {
			$ip_model                  = Lockout_Ip::get( $item->ip );
			$log                       = $item->export();
			$log['date']               = $item->format_date_time( $item->date );
			$log['format_date']        = $item->get_date( $item->date );
			$log['tag']                = in_array( $item->type, array( self::LOCKOUT_404, self::ERROR_404 ) )
				? '404'
				: 'login';
			$log['tag_class']          = in_array( $item->type, array( self::AUTH_LOCK, self::ERROR_404 ) )
				? 'bg-badge-red'
				: 'bg-badge-green';
			$log['container_class']    = in_array( $item->type, array( self::AUTH_LOCK, self::ERROR_404 ) )
				? 'sui-error'
				: 'sui-warning';
			$log['access_status']      = $ip_model->get_access_status();
			$log['access_status_text'] = $ip_model->get_access_status_text();
			$data[]                    = $log;
		}

		return $data;
	}

	/**
	 * Get the first log by ID
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function find_by_id( $id ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
				->where( 'id', $id )
				->first();
	}

	/**
	 * Delete current log
	 */
	public function delete() {
		$orm = self::get_orm();
		$orm->get_repository( self::class )->delete(
			array(
				'id' => $this->id,
			)
		);
	}
}
