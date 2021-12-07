<?php

namespace WP_Defender\Model\Setting;

class Login_Lockout extends \Calotes\Model\Setting {
	protected $table = 'wd_login_lockout_settings';

	/**
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;
	/**
	 * Maximum attempt before get locked.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $attempt = 5;
	/**
	 * The timeframe we record the attempt.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $timeframe = 300;
	/**
	 * How current lockout last.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $duration = 300;
	/**
	 * Duration unit.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[seconds,minutes,hours]
	 */
	public $duration_unit = 'seconds';
	/**
	 * How the lock is going to be, if we choose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[timeframe,permanent]
	 */
	public $lockout_type = 'timeframe';

	/**
	 * The message to output on the lockout screen.
	 *
	 * @var string
	 * @defender_property
	 * @rule required
	 * @sanitize sanitize_textarea_field
	 */
	public $lockout_message = '';

	/**
	 * The blacklist username, if fail will be banned.
	 *
	 * @var string
	 * @defender_property
	 * @rule required
	 * @sanitize sanitize_textarea_field
	 */
	public $username_blacklist = '';

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled' ), 'boolean' ),
		array( array( 'attempt', 'timeframe', 'duration' ), 'integer' ),
		array( array( 'lockout_type' ), 'in', array( 'timeframe', 'permanent' ) ),
		array( array( 'duration_unit' ), 'in', array( 'seconds', 'minutes', 'hours' ) ),
	);

	/**
	 * @return array
	 */
	public function get_default_values() {

		return array(
			'message' => __( 'You have been locked out due to too many invalid login attempts.', 'wpdef' ),
		);
	}

	protected function before_load() {
		$default_values        = $this->get_default_values();
		$this->lockout_message = $default_values['message'];
	}

	/**
	 *  Return the blacklisted username as array.
	 *
	 * @return array
	 */
	public function get_blacklisted_username() {
		// Since 2.4.7
		$usernames = apply_filters( 'wp_defender_banned_usernames', $this->username_blacklist );
		if ( empty( $usernames ) ) {
			return array();
		}
		$usernames = str_replace( PHP_EOL, ' ', $this->username_blacklist );
		$usernames = explode( ' ', $usernames );
		$usernames = array_map( 'trim', $usernames );

		return $usernames;
	}

	/**
	 * Define labels for settings key.
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			// New key: enabled.
			'login_protection'                       => __( 'Login Protection', 'wpdef' ),
			// New key: attempt.
			'login_protection_login_attempt'         => __( 'Login Protection - Threshold', 'wpdef' ),
			// New key: timeframe.
			'login_protection_lockout_timeframe'     => __( 'Login Protection - Timeframe', 'wpdef' ),
			// New key: lockout_type.
			'login_protection_lockout_ban'           => __( 'Login Protection - Duration Type', 'wpdef' ),
			// New key: duration.
			'login_protection_lockout_duration'      => __( 'Login Protection - Duration', 'wpdef' ),
			// New key: duration_unit.
			'login_protection_lockout_duration_unit' => __( 'Login Protection - Duration units', 'wpdef' ),
			// New key: lockout_message.
			'login_protection_lockout_message'       => __( 'Login Protection - Lockout Message', 'wpdef' ),
			'username_blacklist'                     => __( 'Login Protection - Banned Usernames', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}