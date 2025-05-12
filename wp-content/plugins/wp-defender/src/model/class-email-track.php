<?php
/**
 * Handles interactions with the database table for email tracking.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use WP_Defender\DB;

/**
 * Model for the email tracking table.
 */
class Email_Track extends DB {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_email_log';

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
	 * Table column for source.
	 *
	 * @var string
	 * @defender_property
	 */
	public $source;
	/**
	 * Table column for to.
	 *
	 * @var string
	 * @defender_property
	 */
	public $to;

	/**
	 * Count the number of records in the database table based on the provided filters.
	 *
	 * @param  mixed $source  The source to filter by.
	 * @param  mixed $email  The email to filter by.
	 * @param  mixed $date_from  The start date for the date range filter.
	 * @param  mixed $date_to  The end date for the date range filter.
	 *
	 * @return int|null The number of records matching the provided filters, or null if an error occurred.
	 */
	public static function count( $source, $email, $date_from, $date_to ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
					->where( 'source', $source )
					->where( 'to', $email )
					->where( 'timestamp', '>=', $date_from )
					->where( 'timestamp', '<=', $date_to )
					->count();
	}
}