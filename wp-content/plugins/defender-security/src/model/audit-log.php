<?php

namespace WP_Defender\Model;

use WP_Defender\DB;

class Audit_Log extends DB {
	const EVENT_TYPE_USER = 'user', EVENT_TYPE_SYSTEM = 'system', EVENT_TYPE_COMMENT = 'comment',
		EVENT_TYPE_MEDIA = 'media', EVENT_TYPE_SETTINGS = 'settings', EVENT_TYPE_CONTENT = 'content',
		EVENT_TYPE_MENU = 'menu';

	protected $table = 'defender_audit_log';

	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * @var int
	 * @defender_property
	 */
	public $timestamp;
	/**
	 * @var string
	 * @defender_property
	 */
	public $event_type;
	/**
	 * @var string
	 * @defender_property
	 */
	public $action_type;
	/**
	 * @var string
	 * @defender_property
	 */
	public $site_url;
	/**
	 * @var int
	 * @defender_property
	 */
	public $user_id;
	/**
	 * @var string
	 * @defender_property
	 */
	public $context;
	/**
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * @var string
	 * @defender_property
	 */
	public $msg;
	/**
	 * @var int
	 * @defender_property
	 */
	public $blog_id;
	/**
	 * @var bool
	 * @defender_property
	 */
	public $synced;
	/**
	 * @var int
	 * @defender_property
	 */
	public $ttl;

	public $safe = array(
		'id',
		'timestamp',
		'event_type',
		'action_type',
		'site_url',
		'user_id',
		'context',
		'ip',
		'msg',
		'blog_id',
		'synced',
		'ttl',
	);

	/**
	 * Truncate the table, as this is mostly use for cache, when we fetch new data, old data should be removed for consistent sync with API side.
	 */
	public static function truncate() {
		$orm = self::get_orm();
		$orm->get_repository( self::class )->truncate();
	}

	/**
	 * Get the very last item, mostly use for testing.
	 *
	 * @return self|null
	 */
	public static function get_last() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
			->where( 'synced', 'in', array( 0, 1 ) )
			->order_by( 'id', 'desc' )
			->first();
	}

	/**
	 * Sometimes we need the pre of last, for testing.
	 * @return self
	 */
	public static function get_pre_last() {
		$orm   = self::get_orm();
		$model = $orm->get_repository( self::class )
			->where( 'synced', 'in', array( 0, 1 ) )
			->order_by( 'id', 'desc' )
			->limit( '0,2' )
			->get();

		return array_pop( $model );
	}

	/**
	 * @return self[]
	 */
	public static function get_logs_need_flush() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->where( 'synced', 0 )->limit( 50 )->get();
	}

	/**
	 * Query logs from internal cache.
	 *
	 * @param $date_from      The start date we want to query, in timestamp format.
	 * @param $date_to        The date end for the query, in timestamp format.
	 * @param array $events   Type of the event, eg:comment, user, system...
	 * @param string $user_id Who trigger this event, if it 0, will be guest.
	 * @param string $ip      Ip of who trigger this.
	 * @param $paged          Current page.
	 *
	 * @return array
	 */
	public static function query( $date_from, $date_to, $events = array(), $user_id = '', $ip = '', $paged = 1 ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );
		$builder->where( 'timestamp', '>=', $date_from )
			->where( 'timestamp', '<=', $date_to );

		if ( is_array( $events )
			&& count( $events )
			&& count( array_diff( $events, self::allowed_events() ) ) === 0 ) {
			$builder->where( 'event_type', 'in', $events );
		}

		if ( ! empty( $user_id ) ) {
			$builder->where( 'user_id', $user_id );
		}

		if ( ! empty( $ip ) ) {
			$builder->where( 'ip', 'like', "%$ip%" );
		}
		$builder->order_by( 'timestamp', 'desc' );

		if ( false !== $paged ) {
			// If paged == false, then it will be no paging.
			$per_page = 20;
			$offset   = ( ( $paged - 1 ) * $per_page ) . ',' . $per_page;
			$builder->limit( $offset );
		}

		return $builder->get();
	}

	/**
	 * Clean up the old logs depending on the storage settings.
	 *
	 * @param $date_from      The start date we want to query, in timestamp format.
	 * @param $date_to        The date end for the query, in timestamp format.
	 * @param int|null $limit Limit.
	 *
	 * @return void
	 */
	public static function delete_old_logs( $date_from, $date_to, $limit = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );
		$builder->where( 'timestamp', '>=', $date_from )
				->where( 'timestamp', '<=', $date_to );

		if ( empty( $limit ) ) {
			$builder->delete_all();
		} else {
			$builder->order_by( 'id' )
					->limit( $limit )
					->delete_by_limit();
		}
	}

	/**
	 * This similar to @query, but we count the total row.
	 *
	 * @param $date_from
	 * @param $date_to
	 * @param array $events
	 * @param string $user_id
	 * @param string $ip
	 */
	public static function count( $date_from, $date_to, $events = array(), $user_id = '', $ip = '' ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );
		$builder->where( 'timestamp', '>=', $date_from )
				->where( 'timestamp', '<=', $date_to );
		if ( count( $events ) && count( array_diff( $events, self::allowed_events() ) ) === 0 ) {
			$builder->where( 'event_type', 'in', $events );
		}

		if ( ! empty( $user_id ) ) {
			$builder->where( 'user_id', $user_id );
		}

		if ( ! empty( $ip ) ) {
			$builder->where( 'ip', 'like', "%$ip%" );
		}

		return $builder->count();
	}

	/**
	 * Mass insert logs, usually where we fetched from API.
	 *
	 * @param $data
	 *
	 * @throws \ReflectionException
	 */
	public static function mass_insert( $data ) {
		// Todo: use raw sql for faster.
		foreach ( $data as $datum ) {
			$item = new Audit_Log();
			$item->import( $datum );
			$item->synced = 1;
			$item->save();
		}
	}

	public static function get_by_id( $id ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->find_by_id( $id );
	}

	/**
	 * Get allowed types.
	 *
	 * @return array
	 */
	public static function allowed_events() {

		return array(
			self::EVENT_TYPE_USER,
			self::EVENT_TYPE_SYSTEM,
			self::EVENT_TYPE_COMMENT,
			self::EVENT_TYPE_MEDIA,
			self::EVENT_TYPE_SETTINGS,
			self::EVENT_TYPE_CONTENT,
			self::EVENT_TYPE_MENU,
		);
	}
}
