<?php
/**
 * Handles interactions with the database table for audit logs.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;
use ReflectionException;

/**
 * Model for the  audit log table.
 */
class Audit_Log extends DB {

	public const EVENT_TYPE_USER = 'user', EVENT_TYPE_SYSTEM = 'system', EVENT_TYPE_COMMENT = 'comment',
		EVENT_TYPE_MEDIA         = 'media', EVENT_TYPE_SETTINGS = 'settings', EVENT_TYPE_CONTENT = 'content',
		EVENT_TYPE_MENU          = 'menu';

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_audit_log';

	/**
	 * Primary key column.
	 *
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Table column for timestamp.
	 *
	 * @var int
	 * @defender_property
	 */
	public $timestamp;
	/**
	 * Table column for event type.
	 *
	 * @var string
	 * @defender_property
	 */
	public $event_type;
	/**
	 * Table column for action type.
	 *
	 * @var string
	 * @defender_property
	 */
	public $action_type;
	/**
	 * Table column for site url.
	 *
	 * @var string
	 * @defender_property
	 */
	public $site_url;
	/**
	 * Table column for user id.
	 *
	 * @var int
	 * @defender_property
	 */
	public $user_id;
	/**
	 * Table column for context.
	 *
	 * @var string
	 * @defender_property
	 */
	public $context;
	/**
	 * Table column for ip.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * Table column for message.
	 *
	 * @var string
	 * @defender_property
	 */
	public $msg;
	/**
	 * Table column for blog id.
	 *
	 * @var int
	 * @defender_property
	 */
	public $blog_id;
	/**
	 * Table column for synced flag.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $synced;
	/**
	 * Table column for ttl.
	 *
	 * @var int
	 * @defender_property
	 */
	public $ttl;
	/**
	 * Safe columns.
	 *
	 * @var array
	 */
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
	 * Truncate the table, as this is mostly use for cache, when we fetch new data, old data should be removed for
	 * consistent sync with API side.
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
	 *
	 * @return self|null
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
	 * Get logs that need to be flushed.
	 *
	 * @return self[]
	 */
	public static function get_logs_need_flush() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->where( 'synced', 0 )->limit( 50 )->get();
	}

	/**
	 * Query logs from internal cache.
	 *
	 * @param  int      $date_from  The start date we want to query, in timestamp format.
	 * @param  int      $date_to  The date end for the query, in timestamp format.
	 * @param  array    $events  Type of the event, e.g. comment, user, system.
	 * @param  string   $user_id  Who trigger this event, if it 0, will be guest.
	 * @param  string   $ip  IP of who trigger this.
	 * @param  int|bool $paged  Current page.
	 *
	 * @return array
	 */
	public static function query(
		$date_from,
		$date_to,
		$events = array(),
		$user_id = '',
		$ip = '',
		$paged = 1
	): array {
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
	 * @param  int      $date_from  The start date we want to query, in timestamp format.
	 * @param  int      $date_to  The date end for the query, in timestamp format.
	 * @param  int|null $limit  Limit.
	 *
	 * @return void
	 */
	public static function delete_old_logs( $date_from, $date_to, $limit = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );
		$builder->where( 'timestamp', '>=', $date_from )
				->where( 'timestamp', '<=', $date_to );

		if ( null === $limit ) {
			$builder->delete_all();
		} else {
			$builder->order_by( 'id' )
					->limit( $limit )
					->delete_by_limit();
		}
	}

	/**
	 * Counts the number of records in the database table that match the given criteria.
	 *
	 * @param  int    $date_from  The start date to filter records by.
	 * @param  int    $date_to  The end date to filter records by.
	 * @param  array  $events  (optional) An array of event types to filter records by.
	 * @param  string $user_id  (optional) The user ID to filter records by.
	 * @param  string $ip  (optional) The IP address to filter records by.
	 *
	 * @return int The number of records that match the given criteria.
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
	 * Mass insert logs, usually fetched from API.
	 *
	 * @param  array $data  An array of data to be inserted into the Audit_Log table.
	 *
	 * @return void
	 * @throws ReflectionException If the import method of the Audit_Log class throws a ReflectionException.
	 */
	public static function mass_insert( $data ) {
		// Use raw sql for faster.
		foreach ( $data as $datum ) {
			$item = new Audit_Log();
			$item->import( $datum );
			$item->synced = 1;
			$item->save();
		}
	}

	/**
	 * Retrieves a record from the database by its ID.
	 *
	 * @param  int $id  The ID of the record to retrieve.
	 *
	 * @return mixed The retrieved record, or null if not found.
	 */
	public static function get_by_id( $id ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->find_by_id( $id );
	}

	/**
	 * Get allowed types.
	 *
	 * @return array
	 */
	public static function allowed_events(): array {
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