<?php

namespace WP_Defender\Model;

use Calotes\Helper\Array_Cache;
use WP_Defender\DB;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Setting\Blacklist_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;

class Lockout_Ip extends DB {
	const STATUS_BLOCKED = 'blocked', STATUS_NORMAL = 'normal';

	protected $table = 'defender_lockout';

	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * @var string
	 * @defender_property
	 */
	public $ip;
	/**
	 * @var string
	 * @defender_property
	 */
	public $status;
	/**
	 * @var string
	 * @defender_property
	 */
	public $lockout_message;
	/**
	 * @var int
	 * @defender_property
	 */
	public $release_time;
	/**
	 * @var int
	 * @defender_property
	 */
	public $lock_time;
	/**
	 * @var int
	 * @defender_property
	 */
	public $lock_time_404;
	/**
	 * @var int
	 * @defender_property
	 */
	public $attempt;
	/**
	 * @var int
	 * @defender_property
	 */
	public $attempt_404;
	/**
	 * @var array
	 * @defender_property
	 */
	public $meta = array();

	/**
	 * Get the record by IP, if it not appears, then create one.
	 *
	 * @param string $ip
	 * @param null|string $status
	 * @param boolean $all
	 *
	 * @return $this
	 */
	public static function get( $ip, $status = null, $all = false ) {
		$model = Array_Cache::get( $ip, 'lockout' );
		if ( is_object( $model ) ) {
			return $model;
		}
		$orm     = self::get_orm();
		$builder = $orm->get_repository( Lockout_Ip::class )
				->where( 'ip', $ip );
		if ( null !== $status ) {
			$status = 'unban' === $status ? self::STATUS_BLOCKED : self::STATUS_NORMAL;
			$builder->where( 'status', $status );
		}

		if ( true === $all ) {
			$model = $builder->get();
			return $model;
		}

		$model = $builder->first();

		if ( ! is_object( $model ) ) {
			$model                  = new Lockout_Ip();
			$model->ip              = $ip;
			$model->attempt         = 0;
			$model->status          = self::STATUS_NORMAL;
			$model->lockout_message = '';
			$model->release_time    = '';
			$model->lock_time       = '';
			$model->lock_time_404   = '';
			$model->attempt_404     = '';
			$orm->save( $model );
		}

		Array_Cache::set( $ip, $model, 'lockout' );

		return $model;
	}

	/**
	 * Get bulk IPs.
	 *
	 * @param string          $status
	 * @param string|null     $ips
	 * @param int|string|null $limit
	 *
	 * @return $this
	 */
	public static function get_bulk( $status, $ips = null, $limit = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( Lockout_Ip::class );
		if ( null === $ips ) {
			$builder->where( 'status', $status );
		}
		if ( null !== $ips ) {
			$builder->where( 'ip', 'in', $ips );
		}
		if ( null !== $limit ) {
			$builder->limit( $limit );
		}
		$models = $builder->get();

		return $models;
	}

	/**
	 * Get the access status of this IP.
	 *
	 * @return array
	 */
	public function get_access_status() {
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
	 * Return the access status of this IP, as readable text.
	 * Todo: check values from ban_status(), class Table_Lockout:
	 * 'ban' (STATUS_BAN) --> 'banned'
	 * 'not_ban' (STATUS_NOT_BAN) --> 'na'
	 * 'allowlist' (STATUS_ALLOWLIST) --> 'allowlist'
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function get_access_status_text( $status ) {
		switch ( $status ) {
			case 'banned':
				return __( 'Banned', 'wpdef' );
			case 'allowlist':
				return __( 'In Allowlist', 'wpdef' );
			case 'na':
				return __( 'Not banned or in allowlist' );
			default:
				return '';
		}
	}

	/**
	 * Get locked IPs.
	 * Todo: only columns 'id', 'ip', 'status' from $models.
	 *
	 * @return array
	 */
	public static function query_locked_ip() {
		$orm    = self::get_orm();
		$models = $orm->get_repository( self::class )
			->where( 'status', self::STATUS_BLOCKED )
			->group_by( 'ip' )
			->order_by( 'lock_time', 'desc' )
			->get();

		return $models;
	}

	/**
	 * @return bool
	 */
	public function is_locked() {
		if ( self::STATUS_BLOCKED === $this->status ) {
			$time = new \DateTime( 'now', wp_timezone() );
			if ( $this->release_time < $time->getTimestamp() ) {
				// Unlock it.
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
	public function remaining_release_time() {
		$time = new \DateTime( 'now', wp_timezone() );
		return $this->release_time - $time->getTimestamp();
	}
}