<?php

namespace WP_Defender\Component;

use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;

/**
 * This class will handle the logic lockout when too many fail login attempts
 *
 * Class Login_Lockout
 *
 * @package WP_Defender\Component
 */
class Login_Lockout extends \WP_Defender\Component {
	const SCENARIO_LOGIN_FAIL = 'login_fail', SCENARIO_LOGIN_LOCKOUT = 'login_lockout', SCENARIO_BAN = 'login_ban';

	/**
	 * @var \WP_Defender\Model\Setting\Login_Lockout
	 */
	protected $model;

	public function __construct() {
		$this->model = wd_di()->get( \WP_Defender\Model\Setting\Login_Lockout::class );
	}

	/**
	 * Adding main hooks
	 */
	public function add_hooks() {
		add_action( 'wp_login_failed', array( &$this, 'process_fail_attempt' ) );
		add_filter( 'authenticate', array( &$this, 'show_attempt_left' ), 9999, 3 );
		add_action( 'wp_login', array( &$this, 'clear_login_attempt' ), 10, 2 );
	}

	/**
	 * When an user login successfully, we wll need to clear the info of fail login attempt,
	 * so it wont affect the next time that user login again
	 *
	 * @param $user_login
	 * @param $user
	 */
	public function clear_login_attempt( $user_login, $user ) {
		$ip = $this->get_user_ip();
		// record this
		$model = Lockout_Ip::get( $ip );
		if ( is_object( $model ) ) {
			$model->meta    = array();
			$model->attempt = 0;
			$model->save();
		}
	}

	/**
	 * Show a message to tell user how many attempt they have until get lockout
	 *
	 * @param $user
	 * @param $username
	 * @param $password
	 *
	 * @return mixed
	 */
	public function show_attempt_left( $user, $username, $password ) {
		if ( is_wp_error( $user )
		     && 'POST' == $_SERVER['REQUEST_METHOD']
		     && ! in_array(
				$user->get_error_code(),
				array(
					'empty_username',
					'empty_password',
				)
			)
		) {
			$model = Lockout_Ip::get( $this->get_user_ip() );
			if ( in_array( $username, $this->model->get_blacklisted_username(), true ) ) {
				$msg = __(
					'You have been locked out by the administrator for attempting to login with a banned username',
					'wpdef'
				);
				if ( ! empty( $this->model->lockout_message ) ) {
					$msg = $this->model->lockout_message;
				}
				$user->add(
					'def_login_attempt', $msg
				);

				return $user;
			}
			// this hook is before the @process_fail_attempt, so we will need to add 1 into the attempt count
			$attempt = $model->attempt;
			++ $attempt;
			if ( $attempt < $this->model->attempt ) {
				$user->add(
					'def_login_attempt',
					sprintf( __( '%d login attempts remaining', 'wpdef' ), $this->model->attempt - $attempt )
				);
			} else {
				$user->add( 'def_login_attempt', $this->model->lockout_message );
			}
		}

		return $user;
	}

	/**
	 * From here, we will
	 *  1. Record the attempt
	 *  2. Log it
	 *  3. Do condition check if we should block or not.
	 *
	 * @param string $username
	 */
	public function process_fail_attempt( $username ) {
		if ( empty ( $username ) ) {
			return;
		}
		$ip = $this->get_user_ip();
		// record this
		$model = Lockout_Ip::get( $ip );
		$model = $this->record_fail_attempt( $ip, $model );
		$this->log_event( $ip, $username, self::SCENARIO_LOGIN_FAIL );
		// now check, if it is in a banned username
		$ls = $this->model;
		// this is too couple
		if ( in_array( $username, $ls->get_blacklisted_username(), true ) ) {
			$model->lockout_message = '';
			$model->status          = Lockout_Ip::STATUS_BLOCKED;
			$model->save();
			$this->log_event( $ip, $username, self::SCENARIO_BAN );

			do_action( 'wd_login_lockout', $model, self::SCENARIO_BAN );
			do_action( 'wd_blacklist_this_ip', $ip );

			return;
		}
		// so if we can lock
		$window = strtotime( '-' . $ls->timeframe . 'seconds' );
		if ( ! is_array( $model->meta['login'] ) ) {
			$model->meta['login'] = array();
		}
		// we will get the latest till oldest, limit by attempt
		$checks = array_slice( $model->meta['login'], $ls->attempt * - 1 );

		if ( count( $checks ) < $ls->attempt ) {
			// do nothing
			return;
		}
		// if the last time is larger
		$check = min( $checks );
		if ( $check >= $window ) {
			// lockable
			$model->status    = Lockout_Ip::STATUS_BLOCKED;
			$model->lock_time = time();
			if ( 'permanent' === $ls->lockout_type ) {
				// add to black list
				$model->save();
				do_action( 'wd_blacklist_this_ip', $ip );
			} else {
				$model->lockout_message = $ls->lockout_message;
				$model->release_time    = strtotime( '+' . $ls->duration . ' ' . $ls->duration_unit );
				$model->save();
			}
			// also, need to create a log
			$this->log_event( $ip, $username, self::SCENARIO_LOGIN_LOCKOUT );
			do_action( 'wd_login_lockout', $model, self::SCENARIO_LOGIN_LOCKOUT );
		}
	}

	/**
	 * Store the fail attempt of current IP
	 *
	 * @param $ip
	 * @param Lockout_Ip $model
	 *
	 * @return Lockout_Ip
	 */
	protected function record_fail_attempt( $ip, $model ) {
		$model->attempt += 1;
		$model->ip      = $ip;
		// cache the time here, so it consume less memory than query the logs
		$model->meta['login'][] = time();
		$model->save();

		return $model;
	}

	/**
	 * Log the current event
	 * We have 3 type of event
	 *  1. Fail attempt
	 *  2. Too many fail, get lock
	 *  3. Login with banned username
	 *
	 * @param $ip
	 * @param $username
	 * @param $scenario
	 */
	public function log_event( $ip, $username, $scenario ) {
		$model             = new Lockout_Log();
		$model->ip         = $ip;
		$model->user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$model->date       = time();
		$model->tried      = $username;
		$model->blog_id    = get_current_blog_id();
		switch ( $scenario ) {
			case self::SCENARIO_LOGIN_FAIL:
				$model->type = Lockout_Log::AUTH_FAIL;
				$model->log  = sprintf( esc_html__( 'Failed login attempt with username %s', 'wpdef' ), $username );
				break;
			case self::SCENARIO_BAN:
				$model->type = Lockout_Log::AUTH_LOCK;
				$model->log  = sprintf(
					esc_html__( 'Failed login attempt with a ban username %s', 'wpdef' ),
					$username
				);
				break;
			case self::SCENARIO_LOGIN_LOCKOUT:
			default:
				$model->type = Lockout_Log::AUTH_LOCK;
				$model->log  = __( 'Lockout occurred: Too many failed login attempts', 'wpdef' );
				break;
		}
		$model->save();
		if ( $model->type === Lockout_Log::AUTH_LOCK ) {
			do_action( 'defender_notify', 'firewall-notification', $model );
		}
	}
}
