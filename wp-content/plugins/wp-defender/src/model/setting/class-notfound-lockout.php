<?php
/**
 * Handles not found lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for not found lockout settings.
 */
class Notfound_Lockout extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_notfound_lockout_settings';

	/**
	 * Activate this module.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * How many 404 error happen before we lock out the IP.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $attempt = 20;

	/**
	 * The time window we use for counting.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $timeframe = 300;

	/**
	 * How long we block them.
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
	 * How the lock going to be, if we chose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[timeframe,permanent]
	 */
	public $lockout_type = 'timeframe';

	/**
	 * Data allowed:
	 *  - URL, as relative form /this-should-be-block, with or without dash would be fine
	 *  - filetype extension, with dot before .sql|.exe
	 *  - regex pattern, like \/.+\.html
	 *  This will lockout any IP if an attempt is triggered when visit the URL that in the list.
	 *
	 * @var string
	 * @defender_property
	 */
	public $blacklist = '';

	/**
	 * Refer to $blacklist, but we will ignore instead of blocking.
	 *
	 * @var string
	 * @defender_property
	 */
	public $whitelist = '';

	/**
	 * A message to display on frontend when an IP is locked out.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $lockout_message = '';

	/**
	 * Set to true for enabling the 404 tracking on logged-in user.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $detect_logged = false;

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enable', 'detect_logged' ), 'boolean' ),
		array( array( 'attempt', 'timeframe', 'duration' ), 'integer' ),
		array( array( 'lockout_type' ), 'in', array( 'timeframe', 'permanent' ) ),
		array( array( 'duration_unit' ), 'in', array( 'seconds', 'minutes', 'hours' ) ),
	);

	/**
	 * Retrieves the default values for the function.
	 *
	 * @return array The default values.
	 */
	public function get_default_values(): array {
		return array(
			'message'   => esc_html__(
				'You have been locked out due to too many attempts to access a file that doesn`t exist.',
				'wpdef'
			),
			'whitelist' => ".css\n.js\n.map",
		);
	}

	/**
	 * Initializes the object before loading by setting the lockout message and whitelist properties
	 * to their default values.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$default_values        = $this->get_default_values();
		$this->lockout_message = $default_values['message'];
		$this->whitelist       = $default_values['whitelist'];
	}

	/**
	 * Get list of blocklisted or allowlisted data.
	 *
	 * @param  string $type  blocklist|allowlist.
	 *
	 * @return array
	 */
	public function get_lockout_list( $type = 'blocklist' ): array {
		$data = ( 'blocklist' === $type ) ? $this->blacklist : $this->whitelist;
		$arr  = is_array( $data ) ? $data : array_filter( explode( PHP_EOL, $data ) );
		$arr  = array_map( 'trim', $arr );
		$arr  = array_map( 'strtolower', $arr );

		return $arr;
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			// New key 'enabled'.
			'detect_404'                       => self::get_module_name(),
			// New key 'attempt'.
			'detect_404_threshold'             => esc_html__( '404 Detection - Threshold', 'wpdef' ),
			// New key 'timeframe'.
			'detect_404_timeframe'             => esc_html__( '404 Protection - Timeframe', 'wpdef' ),
			// New key 'lockout_type'.
			'detect_404_lockout_ban'           => esc_html__( '404 Protection - Duration Type', 'wpdef' ),
			// New key 'duration'.
			'detect_404_lockout_duration'      => esc_html__( '404 Detection - Duration', 'wpdef' ),
			// New key 'duration_unit'.
			'detect_404_lockout_duration_unit' => esc_html__( '404 Protection - Duration units', 'wpdef' ),
			// New key 'lockout_message'.
			'detect_404_lockout_message'       => esc_html__( '404 Detection - Lockout Message', 'wpdef' ),
			// New key 'blacklist'.
			'detect_404_blacklist'             => esc_html__( '404 Detection - Files and Folders Blocklist', 'wpdef' ),
			// New key 'whitelist'.
			'detect_404_whitelist'             => esc_html__( '404 Detection - Files and Folders Allowlist', 'wpdef' ),
			// New key 'detect_logged'.
			'detect_404_logged'                => esc_html__( '404 Detection - Monitor logged in users', 'wpdef' ),
		);
	}

	/**
	 * Returns the module name for 404 Detection.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( '404 Detection', 'wpdef' );
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