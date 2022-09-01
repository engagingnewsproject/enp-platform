<?php
declare( strict_types=1 );

namespace WP_Defender\Model\Setting;

/**
 * @package WP_Defender\Model\Setting
 */
class Notfound_Lockout extends \Calotes\Model\Setting {
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
	 */
	public $attempt = 20;

	/**
	 * The time window we use for counting.
	 *
	 * @var int
	 * @defender_property
	 */
	public $timeframe = 300;

	/**
	 * How long we block them.
	 *
	 * @var int
	 * @defender_property
	 */
	public $duration = 300;

	/**
	 * Duration unit.
	 *
	 * @var string
	 * @defender_property
	 */
	public $duration_unit = 'seconds';

	/**
	 * How the lock gonna be, if we chose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 */
	public $lockout_type = 'timeframe';

	/**
	 * Data allowed:
	 *  - URL, as relative form /this-should-be-block, with or without dash would be fine
	 *  - filetype extension, with dot before .sql|.exe
	 *  - regex pattern, like \/.+\.html
	 *
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
	protected $rules = [
		[ [ 'enable', 'detect_logged' ], 'boolean' ],
		[ [ 'attempt', 'timeframe', 'duration' ], 'integer' ],
		[ [ 'lockout_type' ], 'in', [ 'timeframe', 'permanent' ] ],
		[ [ 'duration_unit' ], 'in', [ 'seconds', 'minutes', 'hours' ] ],
	];

	/**
	 * @return array
	 */
	public function get_default_values(): array {
		return [
			'message' => __( "You have been locked out due to too many attempts to access a file that doesn't exist.", 'wpdef' ),
			'whitelist' => ".css\n.js\n.map",
		];
	}

	protected function before_load(): void {
		$default_values = $this->get_default_values();
		$this->lockout_message = $default_values['message'];
		$this->whitelist = $default_values['whitelist'];
	}

	/**
	 * Get list of blocklisted or allowlisted data.
	 *
	 * @param string $type blocklist|allowlist
	 *
	 * @return array
	 */
	public function get_lockout_list( $type = 'blocklist' ): array {
		$data = ( 'blocklist' === $type ) ? $this->blacklist : $this->whitelist;
		$arr = is_array( $data ) ? $data : array_filter( explode( PHP_EOL, $data ) );
		$arr = array_map( 'trim', $arr );
		$arr = array_map( 'strtolower', $arr );

		return $arr;
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return [
			// New key 'enabled'.
			'detect_404' => __( '404 Detection', 'wpdef' ),
			// New key 'attempt'.
			'detect_404_threshold' => __( '404 Detection - Threshold', 'wpdef' ),
			// New key 'timeframe'.
			'detect_404_timeframe' => __( '404 Protection - Timeframe', 'wpdef' ),
			// New key 'lockout_type'.
			'detect_404_lockout_ban' => __( '404 Protection - Duration Type', 'wpdef' ),
			// New key 'duration'.
			'detect_404_lockout_duration' => __( '404 Detection - Duration', 'wpdef' ),
			// New key 'duration_unit'.
			'detect_404_lockout_duration_unit' => __( '404 Protection - Duration units', 'wpdef' ),
			// New key 'lockout_message'.
			'detect_404_lockout_message' => __( '404 Detection - Lockout Message', 'wpdef' ),
			// New key 'blacklist'.
			'detect_404_blacklist' => __( '404 Detection - Files & Folders Blocklist', 'wpdef' ),
			// New key 'whitelist'.
			'detect_404_whitelist' => __( '404 Detection - Files & Folders Allowlist', 'wpdef' ),
			// New key 'detect_logged'.
			'detect_404_logged' => __( '404 Detection - Monitor logged in users', 'wpdef' ),
		];
	}
}
