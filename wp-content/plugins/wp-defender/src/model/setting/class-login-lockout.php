<?php
/**
 * Handles the Login Lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for Login Lockout settings.
 */
class Login_Lockout extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_login_lockout_settings';

	/**
	 * Is module enabled?
	 *
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
	 * @rule     required
	 * @sanitize sanitize_textarea_field
	 */
	public $lockout_message = '';

	/**
	 * The blacklist username, if fail will be banned.
	 *
	 * @var string
	 * @defender_property
	 * @rule     required
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
	 * Returns an array containing the default values for the function.
	 *
	 * @return array An array with the default values.
	 */
	public function get_default_values(): array {
		return array(
			'message' => esc_html__( 'You have been locked out due to too many invalid login attempts.', 'wpdef' ),
		);
	}

	/**
	 * Initializes the object before loading.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$default_values        = $this->get_default_values();
		$this->lockout_message = $default_values['message'];
	}

	/**
	 *  Return the blacklisted username as array.
	 *
	 * @return array
	 */
	public function get_blacklisted_username(): array {
		/**
		 * Filter the banned usernames.
		 *
		 * @filter wp_defender_banned_usernames
		 * @since  2.4.7
		 */
		$usernames = apply_filters( 'wp_defender_banned_usernames', $this->username_blacklist );
		if ( empty( $usernames ) ) {
			return array();
		}
		$usernames = str_replace( array( "\r\n", "\r", "\n" ), ' ', $this->username_blacklist );
		$usernames = explode( ' ', $usernames );

		return array_map( 'trim', $usernames );
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			// New key: enabled.
			'login_protection'                       => self::get_module_name(),
			// New key: attempt.
			'login_protection_login_attempt'         => esc_html__( 'Login Protection - Threshold', 'wpdef' ),
			// New key: timeframe.
			'login_protection_lockout_timeframe'     => esc_html__( 'Login Protection - Timeframe', 'wpdef' ),
			// New key: lockout_type.
			'login_protection_lockout_ban'           => esc_html__( 'Login Protection - Duration Type', 'wpdef' ),
			// New key: duration.
			'login_protection_lockout_duration'      => esc_html__( 'Login Protection - Duration', 'wpdef' ),
			// New key: duration_unit.
			'login_protection_lockout_duration_unit' => esc_html__( 'Login Protection - Duration units', 'wpdef' ),
			// New key: lockout_message.
			'login_protection_lockout_message'       => esc_html__( 'Login Protection - Lockout Message', 'wpdef' ),
			'username_blacklist'                     => esc_html__( 'Login Protection - Banned Usernames', 'wpdef' ),
		);
	}

	/**
	 * Returns the module name for Login Protection.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Login Protection', 'wpdef' );
	}

	/**
	 * Returns the module state based on the given flag.
	 *
	 * @param  bool $flag  The flag indicating the module state.
	 *
	 * @return string The module state, either 'active' or 'inactive'.
	 */
	public static function get_module_state( $flag ): string {
		return $flag ? esc_html__( 'active', 'wpdef' ) : esc_html__( 'inactive', 'wpdef' );
	}
}