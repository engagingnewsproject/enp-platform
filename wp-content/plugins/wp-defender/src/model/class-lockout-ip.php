<?php
/**
 * Handles interactions with the database table for lockout IPs.
 *
 * @package WP_Defender\Model
 */

namespace WP_Defender\Model;

use DateTime;
use WP_Defender\DB;
use Calotes\Base\Model;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Setting\Blacklist_Lockout;

/**
 * Model for the lockout IP table.
 */
class Lockout_Ip extends DB {

	public const STATUS_BLOCKED = 'blocked', STATUS_NORMAL = 'normal';

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table = 'defender_lockout';

	/**
	 * Primary key column.
	 *
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Table column for IP address.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * Table column for status.
	 *
	 * @var string
	 * @defender_property
	 */
	public $status;
	/**
	 * Table column for lockout message.
	 *
	 * @var string
	 * @defender_property
	 */
	public $lockout_message;
	/**
	 * Table column for release time.
	 *
	 * @var int
	 * @defender_property
	 */
	public $release_time;
	/**
	 * Table column for lock time.
	 *
	 * @since 3.7.0 Used as timestamp for Login/404 lockouts.
	 * @since 5.4.0 Used as timestamp for Malicious Bot lockouts too.
	 * @since 5.6.0 Used as timestamp for Fake Bot lockouts too.
	 *
	 * @var int
	 * @defender_property
	 */
	public $lock_time;
	/**
	 * Todo: need to use this column less. The lock_time column is used for Login/404/Malicious Bot lockouts/Fake Bot lockouts.
	 *
	 * @var int
	 * @defender_property
	 */
	public $lock_time_404;
	/**
	 * Table column for attempt.
	 *
	 * @var int
	 * @defender_property
	 */
	public $attempt;
	/**
	 * Table column for 404 attempt.
	 *
	 * @var int
	 * @defender_property
	 */
	public $attempt_404;
	/**
	 * Table column for meta data.
	 * TODO: maybe add a new column for type of login-/404-lockouts and remove lock_time_404 & attempt_404 columns?
	 *
	 * @var array
	 * @defender_property
	 */
	public $meta = array();

	/**
	 * Get the record by IP, if it not appears, then create one.
	 *
	 * @param  string      $ip  The IP address to search for.
	 * @param  null|string $status  The status of the record. If 'unban', it will be converted to STATUS_BLOCKED.
	 *                                If null, it will not filter by status.
	 * @param  boolean     $all  Whether to return all records that match the IP address.
	 *                                        If true, it will return an array of records.
	 *                                        If false, it will return the first record that matches the IP address.
	 *
	 * @return object|null|array    The record that matches the IP address.
	 *                              If $all is true, it will return an array of records.
	 *                              If no record is found, it will return null.
	 */
	public static function get( $ip, $status = null, $all = false ) {
		$model = Array_Cache::get( $ip, 'ip_lockout' );
		if ( is_object( $model ) ) {
			return $model;
		}
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class )
						->where( 'ip', $ip );
		if ( null !== $status ) {
			$status = 'unban' === $status ? self::STATUS_BLOCKED : self::STATUS_NORMAL;
			$builder->where( 'status', $status );
		}

		if ( true === $all ) {
			return $builder->get();
		}

		$model = $builder->first();

		if ( ! is_object( $model ) ) {
			$model                  = new Lockout_Ip();
			$model->ip              = $ip;
			$model->attempt         = 0;
			$model->status          = self::STATUS_NORMAL;
			$model->lockout_message = '';
			$model->release_time    = 0;
			// @since 3.7.0 The lock_time column is used for both lockouts.
			$model->lock_time     = time();
			$model->lock_time_404 = 0;
			$model->attempt_404   = 0;
			$orm->save( $model );
		}

		Array_Cache::set( $ip, $model, 'ip_lockout' );

		return $model;
	}

	/**
	 * Get the first IP.
	 *
	 * @param  string $ip  The IP address to search for.
	 *
	 * @return null|Model
	 */
	public static function is_blocklisted_ip( $ip ): ?Model {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )
					->select( 'ip,status' )
					->where( 'ip', $ip )
					->where( 'status', self::STATUS_BLOCKED )
					->first();
	}

	/**
	 * Maybe unblock IP?
	 *
	 * @param  string $ip  The IP address to search for.
	 *
	 * @return string
	 */
	public static function get_unlocked_ip_by( $ip ) {
		$orm   = self::get_orm();
		$model = $orm->get_repository( self::class )
					->where( 'ip', $ip )
					->first();

		if ( is_object( $model ) ) {
			$model->status = self::STATUS_NORMAL;
			$orm->save( $model );

			return $model->ip;
		}

		return '';
	}

	/**
	 * Retrieves bulk IPs based on the provided status, IPs, and limit.
	 *
	 * @param  string     $status  The status of the IPs to retrieve.
	 * @param  array|null $ips  An array of IPs to retrieve. If null, retrieves all IPs with the given status.
	 * @param  int|null   $limit  The maximum number of IPs to retrieve. If null, retrieves all IPs.
	 *
	 * @return array An array of IP models.
	 */
	public static function get_bulk( string $status, $ips = null, $limit = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( self::class );
		if ( null === $ips ) {
			$builder->where( 'status', $status );
		}
		if ( null !== $ips ) {
			$builder->where( 'ip', 'in', $ips );
		}
		if ( null !== $limit ) {
			$builder->limit( $limit );
		}

		return $builder->get();
	}

	/**
	 * Get the access status of this IP.
	 *
	 * @return array
	 */
	public function get_access_status(): array {
		$settings = wd_di()->get( Blacklist_Lockout::class );
		if (
			! in_array( $this->ip, $settings->get_list( 'blocklist' ), true )
			&& ! in_array( $this->ip, $settings->get_list( 'allowlist' ), true )
		) {
			return array( 'na' );
		}

		$result = array();
		if ( in_array( $this->ip, $settings->get_list( 'blocklist' ), true ) ) {
			$result[] = 'banned';
		}
		if ( in_array( $this->ip, $settings->get_list( 'allowlist' ), true ) ) {
			$result[] = 'allowlist';
		}

		return $result;
	}

	/**
	 * Returns the text representation of the access status based on the given status code.
	 *
	 * @param  string $status  The status code to determine the access status text for.
	 *                     Possible values are: 'banned', 'allowlist', 'na'.
	 *
	 * @return string The text representation of the access status.
	 *                Returns an empty string if the status code is not recognized.
	 */
	public function get_access_status_text( string $status ): string {
		switch ( $status ) {
			case 'banned':
				return esc_html__( 'Banned', 'wpdef' );
			case 'allowlist':
				return esc_html__( 'In Allowlist', 'wpdef' );
			case 'na':
				return esc_html__( 'Not banned or in allowlist', 'wpdef' );
			default:
				return '';
		}
	}

	/**
	 * Get locked IPs.
	 *
	 * @return array
	 */
	public static function query_locked_ip(): array {
		$orm  = self::get_orm();
		$time = new DateTime( 'now', wp_timezone() );

		return $orm->get_repository( self::class )
					->select( 'id,ip,status' )
					->where( 'status', self::STATUS_BLOCKED )
					->where( 'release_time', '>', $time->getTimestamp() )
					->group_by( 'ip' )
					->order_by( 'lock_time', 'desc' )
					->get_results();
	}

	/**
	 * Checks if the current object is locked.
	 *
	 * @return bool Returns false if the object is not locked, true otherwise.
	 */
	public function is_locked(): bool {
		if ( self::STATUS_BLOCKED === $this->status ) {
			$time = new DateTime( 'now', wp_timezone() );
			if ( $this->release_time < $time->getTimestamp() ) {
				// Unlock it and clear the metadata.
				$this->attempt = 0;
				$this->meta    = array(
					'nf'    => array(),
					'login' => array(),
				);
				$this->status  = self::STATUS_NORMAL;
				$this->save();

				return false;
			} else {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return remaining release time.
	 *
	 * @return int Remaining release time.
	 */
	public function remaining_release_time(): int {
		$time = new DateTime( 'now', wp_timezone() );

		return $this->release_time - $time->getTimestamp();
	}

	/**
	 * Remove all records.
	 *
	 * @return bool|int
	 * @since 3.3.0
	 */
	public static function truncate() {
		$orm = self::get_orm();

		return $orm->get_repository( self::class )->truncate();
	}
}