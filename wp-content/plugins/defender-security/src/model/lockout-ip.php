<?php

namespace WP_Defender\Model;

use Calotes\Helper\Array_Cache;
use WP_Defender\DB;
use WP_Defender\Model\Setting\Blacklist_Lockout;

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
	 * Get the record by IP, if it not appear, then create one
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
		if ( null !== $status) {
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
	 * Get bulk IPs
	 * 
	 * @param $status
	 * @param $ips
	 * @param $limit
	 *
	 * @return $this
	 */
	public static function get_bulk( $status, $ips = null, $limit = null ) {
		$orm   = self::get_orm();
		$builder = $orm->get_repository( Lockout_Ip::class );
		if ( $ips === null ) {
			$builder->where( 'status', $status );
		}
		if ( $ips !== null ) {
			$builder->where( 'ip', 'in', $ips );
		}
		if ( $limit !== null ) {
			$builder->limit( $limit );
		}
		$models = $builder->get();

		return $models;
	}
	/**
	 * Get the access status of this IP
	 *
	 * @return string
	 */
	public function get_access_status() {
		$settings = wd_di()->get( Blacklist_Lockout::class );
		if ( in_array( $this->ip, $settings->get_list( 'blocklist' ) ) ) {
			return 'banned';
		} elseif ( in_array( $this->ip, $settings->get_list( 'allowlist' ) ) ) {
			return 'allowlist';
		}

		return 'na';
	}

	/**
	 * Return the access status of this IP, as readable text
	 *
	 * @return string
	 */
	public function get_access_status_text() {
		$status = $this->get_access_status();
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
	 * Get locked IPs
	 * Todo: only columns 'id', 'ip', 'status' from $models
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
		if ( $this->status === self::STATUS_BLOCKED ) {
			$time = new \DateTime( 'now', wp_timezone() );
			if ( $this->release_time < $time->getTimestamp() ) {
				//unlock it
				$this->attempt = 0;
				$this->meta    = [
					'nf'    => [],
					'login' => []
				];
				$this->status  = self::STATUS_NORMAL;
				$this->save();

				return false;
			} else {
				return true;
			}
		}

		return false;
	}
}
