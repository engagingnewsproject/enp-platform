<?php
/**
 * Handles interactions with the database for quarantined files.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;

/**
 * Model for the unlock out table.
 */
class Unlockout extends DB {

	// The anonymous type can be used for unauthorized users in the future.
	public const TYPE_REGISTERED = 'registered', TYPE_ANONYMOUS = 'anonymous';
	public const STATUS_RESOLVED = 'resolved', STATUS_PENDING = 'pending';

	/**
	 *  Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_unlockout';

	/**
	 * Primary key column.
	 *
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Table column for the IP.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip;

	/**
	 * Table column for the type.
	 *
	 * @var string
	 * @defender_property
	 */
	public $type;

	/**
	 * Table column for the email.
	 *
	 * @var string
	 * @defender_property
	 */
	public $email;

	/**
	 * Table column for the status.
	 *
	 * @var string
	 * @defender_property
	 */
	public $status;

	/**
	 * Table column for the timestamp.
	 *
	 * @var int
	 * @defender_property
	 */
	public $timestamp;

	/**
	 * Creates a new record in the database with the given data.
	 *
	 * @param  string $ip  The IP address to be stored.
	 * @param  string $email  The email address to be stored.
	 *
	 * @return bool True if the record was successfully saved, false otherwise.
	 */
	public function create( $ip, $email ) {
		$this->ip        = $ip;
		$this->type      = self::TYPE_REGISTERED;
		$this->email     = $email;
		$this->status    = self::STATUS_PENDING;
		$this->timestamp = time();

		return $this->save();
	}

	/**
	 * Remove data by given data.
	 *
	 * @param  int $timestamp  The timestamp to compare against.
	 * @param  int $limit  The maximum number of records to delete.
	 *
	 * @return void
	 */
	public static function remove_records( $timestamp, $limit ) {
		$orm = self::get_orm();
		$orm->get_repository( self::class )
			->where( 'timestamp', '<=', $timestamp )
			->order_by( 'id' )
			->limit( $limit )
			->delete_by_limit();
	}

	/**
	 * Remove all records.
	 *
	 * @return void
	 */
	public static function truncate() {
		$orm = self::get_orm();
		$orm->get_repository( self::class )->truncate();
	}

	/**
	 * Retrieves the resolved IP address by the given ID, email, and limit time.
	 *
	 * @param  int    $id  The ID of the record.
	 * @param  string $email  The email associated with the record.
	 * @param  int    $limit_time  The limit time for the record.
	 *
	 * @return string The resolved IP address if it exists and is not expired, otherwise 'expired' or an empty string.
	 */
	public static function get_resolved_ip_by( $id, $email, $limit_time ) {
		$orm = self::get_orm();

		$model = $orm->get_repository( self::class )
					->where( 'id', $id )
					->where( 'email', $email )
					->first();

		if ( ! is_object( $model ) ) {
			return '';
		}

		if ( $model->timestamp > $limit_time ) {
			$model->status = self::STATUS_RESOLVED;
			$orm->save( $model );

			return $model->ip;
		} else {
			return 'expired';
		}
	}
}