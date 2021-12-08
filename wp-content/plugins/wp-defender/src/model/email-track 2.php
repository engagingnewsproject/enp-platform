<?php

namespace WP_Defender\Model;

use WP_Defender\DB;

class Email_Track extends DB {
	protected $table = 'defender_email_log';

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
	public $source;
	/**
	 * @var string
	 * @defender_property
	 */
	public $to;

	/**
	 * @param $source
	 * @param $email
	 * @param $date_from
	 * @param $date_to
	 *
	 * @return string|null
	 */
	public static function count( $source, $email, $date_from, $date_to ) {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->where( 'source', $source )
		           ->where( 'to', $email )->where( 'timestamp', '>=', $date_from )
		           ->where( 'timestamp', '<=', $date_to )->count();
	}
}