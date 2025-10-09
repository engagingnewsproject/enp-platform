<?php
/**
 * Handle user agent lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;
use WP_Defender\Component\User_Agent as User_Agent_Service;

/**
 * Model for user agent lockout settings.
 *
 * Since with v5.4.0 blocklisted UA has groups:
 * 'Blocklist Presets' is associated with the properties $blocklist_presets & $blocklist_preset_values,
 * 'Scripts Presets' is associated with the properties $script_presets & $script_preset_values,
 * 'Custom User Agents' is associated with the property $blacklist.
 */
class User_Agent_Lockout extends Setting {
	const BOT_LOCKOUT_TYPE_ALLOWED          = array( 'temporary', 'permanent' );
	const BOT_LOCKOUT_DURATION_UNIT_ALLOWED = array( 'seconds', 'minutes', 'hours' );

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_user_agent_settings';

	/**
	 * Is module enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * Blacklist User Agents.
	 *
	 * @var string
	 * @defender_property
	 */
	public $blacklist = '';

	/**
	 * Whitelist User Agents.
	 *
	 * @var string
	 * @defender_property
	 */
	public $whitelist = '';

	/**
	 * Message to display for user agent lockout.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $message = '';

	/**
	 * Is empty headers allowed?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $empty_headers = false;

	/**
	 * Is blocklist presets enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $blocklist_presets = true;

	/**
	 * The list of Blocklist presets.
	 *
	 * @var array
	 * @defender_property
	 */
	public $blocklist_preset_values = array();

	/**
	 * Is script presets enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $script_presets = false;

	/**
	 * The list of Script presets.
	 *
	 * @var array
	 * @defender_property
	 */
	public $script_preset_values = array();

	/**
	 * Is malicious bot enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $malicious_bot_enabled = false;

	/**
	 * How the lock is going to be, if we choose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[temporary,permanent]
	 */
	public $malicious_bot_lockout_type = 'temporary';

	/**
	 * Duration for the lockout.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $malicious_bot_lockout_duration = 300;

	/**
	 * Duration unit.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[seconds,minutes,hours]
	 */
	public $malicious_bot_lockout_duration_unit = 'seconds';

	/**
	 * The message to show on frontend when a malicious bot is triggered.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $malicious_bot_message = '';

	/**
	 * Is fake bots enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $fake_bots_enabled = false;

	/**
	 * How the lock is going to be, if we choose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[temporary,permanent]
	 */
	public $fake_bots_lockout_type = 'temporary';

	/**
	 * Duration for the lockout.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $fake_bots_lockout_duration = 300;

	/**
	 * Duration unit.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[seconds,minutes,hours]
	 */
	public $fake_bots_lockout_duration_unit = 'seconds';

	/**
	 * The message to show on frontend when a fake bot is triggered.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $fake_bots_message = '';

	/**
	 * Old properties of the Bot Trap option for backward compatibility. We can remove them in future versions.
	 * Is bot trap enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $bot_trap_enabled = false;

	/**
	 * How the lock is going to be, if we choose permanent, then their IP will be blacklisted.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[temporary,permanent]
	 */
	public $bot_trap_lockout_type = 'temporary';

	/**
	 * Duration for the lockout.
	 *
	 * @var int
	 * @defender_property
	 * @rule required|integer
	 */
	public $bot_trap_lockout_duration = 300;

	/**
	 * Duration unit.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[seconds,minutes,hours]
	 */
	public $bot_trap_lockout_duration_unit = 'seconds';

	/**
	 * Rules for validation.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled', 'empty_headers', 'blocklist_presets', 'script_presets' ), 'boolean' ),
		array( array( 'malicious_bot_lockout_type' ), 'in', self::BOT_LOCKOUT_TYPE_ALLOWED ),
		array( array( 'malicious_bot_lockout_duration_unit' ), 'in', self::BOT_LOCKOUT_DURATION_UNIT_ALLOWED ),
		array( array( 'fake_bots_lockout_type' ), 'in', self::BOT_LOCKOUT_TYPE_ALLOWED ),
		array( array( 'fake_bots_lockout_duration_unit' ), 'in', self::BOT_LOCKOUT_DURATION_UNIT_ALLOWED ),
	);

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public function get_default_values(): array {
		// Allowled User Agents.
		$whitelist  = "a6-indexer\nadsbot-google\naolbuild\napis-google\nbaidu\nbingbot\nbingpreview";
		$whitelist .= "\nbutterfly\ncloudflare\nchrome\nduckduckbot\nembedly\nfacebookexternalhit\nfacebot\ngoogle page speed";
		$whitelist .= "\ngooglebot\nia_archiver\nlinkedinbot\nmediapartners-google\nmsnbot\nnetcraftsurvey";
		$whitelist .= "\noutbrain\npinterest\nquora\nslackbot\nslurp\ntweetmemebot\ntwitterbot\nuptimerobot";
		$whitelist .= "\nurlresolver\nvkshare\nw3c_validator\nwordpress\nwp rocket\nyandex";
		$message    = esc_html__( 'You have been blocked from accessing this website.', 'wpdef' );

		return array(
			'message'                 => $message,
			'malicious_bot_message'   => $message,
			'fake_bots_message'       => $message,
			'whitelist'               => $whitelist,
			// Blocked User Agents.
			'blacklist'               => '',
			'blocklist_presets'       => true,
			'blocklist_preset_values' => array( 'mj12bot', 'dotbot' ),
			'script_presets'          => false,
			'script_preset_values'    => array(),
		);
	}

	/**
	 * Initializes the object by setting default values for the message, whitelist, and blacklist properties.
	 * This function retrieves the default values from the get_default_values() method and assigns them to the
	 * corresponding properties of the object.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$default_values                = $this->get_default_values();
		$this->message                 = $default_values['message'];
		$this->whitelist               = $default_values['whitelist'];
		$this->blacklist               = $default_values['blacklist'];
		$this->blocklist_presets       = $default_values['blocklist_presets'];
		$this->blocklist_preset_values = $default_values['blocklist_preset_values'];
		$this->script_presets          = $default_values['script_presets'];
		$this->script_preset_values    = $default_values['script_preset_values'];
		$this->malicious_bot_message   = $default_values['malicious_bot_message'];
		$this->fake_bots_message       = $default_values['fake_bots_message'];
	}

	/**
	 * Checks if the User Agent Lockout feature is active.
	 *
	 * @return bool Returns true if the User Agent Lockout feature is active, false otherwise.
	 */
	public function is_active(): bool {
		return (bool) apply_filters(
			'wd_user_agents_enable',
			$this->enabled
		);
	}

	/**
	 * Get list of blocklisted or allowlisted data.
	 *
	 * @param  string $type  blocklist|allowlist.
	 * @param  bool   $lower  Whether to convert the list to lowercase.
	 *
	 * @return array
	 */
	public function get_lockout_list( $type = 'blocklist', $lower = true ): array {
		$data = 'blocklist' === $type ? $this->blacklist : $this->whitelist;
		$arr  = array_filter( preg_split( "/\r\n|\n|\r/", $data ), 'boolval' );
		$arr  = array_map( 'trim', $arr );
		if ( $lower ) {
			$arr = array_map( 'strtolower', $arr );
		}

		return $arr;
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled'       => self::get_module_name(),
			'message'       => esc_html__( 'Message', 'wpdef' ),
			'blacklist'     => esc_html__( 'Blocklist', 'wpdef' ),
			'whitelist'     => esc_html__( 'Allowlist', 'wpdef' ),
			'empty_headers' => esc_html__( 'Empty Headers', 'wpdef' ),
		);
	}

	/**
	 * Get the access status of this UA.
	 *
	 * @param  string $ua  User Agent.
	 *
	 * @return array
	 */
	public function get_access_status( $ua ): array {
		$blocklist = str_replace( '#', '\#', $this->get_all_selected_blocklist_ua() );
		$allowlist = str_replace( '#', '\#', $this->get_lockout_list( 'allowlist' ) );

		// Escape regex special characters in each item before building the pattern.
		$blocklist_escaped = array_map( 'preg_quote', $blocklist, array_fill( 0, count( $blocklist ), '#' ) );
		$allowlist_escaped = array_map( 'preg_quote', $allowlist, array_fill( 0, count( $allowlist ), '#' ) );

		$blocklist_regex_pattern = '#' . implode( '|', $blocklist_escaped ) . '#i';
		$allowlist_regex_pattern = '#' . implode( '|', $allowlist_escaped ) . '#i';

		$blocklist_match = preg_match( $blocklist_regex_pattern, $ua );
		$allowlist_match = preg_match( $allowlist_regex_pattern, $ua );

		if ( 1 !== $blocklist_match && 1 !== $allowlist_match ) {
			return array( 'na' );
		}

		$result = array();

		// Check blocklist first - if it matches, add 'banned'.
		if ( 1 === $blocklist_match ) {
			$result[] = 'banned';
		}

		// Check allowlist - if it matches, add 'allowlist'.
		if ( 1 === $allowlist_match ) {
			$result[] = 'allowlist';
		}

		return $result;
	}

	/**
	 * Checks if a user agent is present in a given list.
	 *
	 * @param string $ua   The user agent to check.
	 * @param string $collection The type of list to check against ('blocklist' or 'allowlist').
	 *
	 * @return bool Returns true if the user agent is in the list, false otherwise.
	 */
	public function is_ua_in_list( $ua, $collection ): bool {
		if ( 'allowlist' === $collection ) {
			$arr = str_replace( '#', '\#', $this->get_lockout_list( $collection ) );
		} else {
			$arr = str_replace( '#', '\#', $this->get_all_selected_blocklist_ua() );
		}

		// Escape regex special characters in each item before building the pattern.
		$arr_escaped        = array_map( 'preg_quote', $arr, array_fill( 0, count( $arr ), '#' ) );
		$list_regex_pattern = '#' . implode( '|', $arr_escaped ) . '#i';

		return 1 === preg_match( $list_regex_pattern, $ua );
	}

	/**
	 * Remove User Agent from a list.
	 *
	 * @param string $ua The user agent to remove.
	 * @param string $collection blocklist|allowlist.
	 *
	 * @return void
	 */
	public function remove_from_list( $ua, $collection ) {
		if ( 'allowlist' === $collection ) {
			$arr = $this->get_lockout_list( $collection );
			// Array can contain uppercase.
			$orig_arr = str_replace( '#', '\#', $this->get_lockout_list( $collection, false ) );
		} else {
			$arr = $this->get_all_selected_blocklist_ua();
			// Array can contain uppercase.
			$orig_arr = str_replace( '#', '\#', $this->get_all_selected_blocklist_ua( false ) );
		}

		$list_regex_pattern = '#' . implode( '|', $arr ) . '#i';
		$list_match         = preg_match( $list_regex_pattern, $ua );
		if ( false !== $list_match ) {
			if ( 'blocklist' === $collection ) {
				// Check in 'Blocklist Presets'.
				if ( $this->blocklist_presets ) {
					$key = array_search( $ua, $this->blocklist_preset_values, true );
					if ( false !== $key ) {
						unset( $this->blocklist_preset_values[ $key ] );
						$this->blocklist_preset_values = array_values( $this->blocklist_preset_values );
					}
				}
				// Check in 'Script Presets' using Regex.
				if ( $this->script_presets ) {
					if ( false !== strpos( $ua, User_Agent_Service::GO_HTTP_CLIENT_KEY . '/' ) ) {
						$key_script_preset = User_Agent_Service::GO_HTTP_CLIENT_KEY;
					} elseif ( false !== strpos( $ua, User_Agent_Service::PYTHON_REQUESTS_KEY . '/' ) ) {
						$key_script_preset = User_Agent_Service::PYTHON_REQUESTS_KEY;
					} else {
						$key_script_preset = '';
					}

					$key = array_search( $key_script_preset, $this->script_preset_values, true );
					if ( false !== $key ) {
						unset( $this->script_preset_values[ $key ] );
						$this->script_preset_values = array_values( $this->script_preset_values );
					}
				}
				// Check 'Custom User Agents' case.
				$arr_blocklist = $this->get_lockout_list( 'blocklist', false );
				if ( array() !== $arr_blocklist ) {
					$key = array_search( $ua, $arr_blocklist, true );
					if ( false !== $key && isset( $arr_blocklist[ $key ] ) ) {
						unset( $arr_blocklist[ $key ] );
						// Convert back to string.
						$this->blacklist = implode( PHP_EOL, $arr_blocklist );
					}
				}
			} elseif ( 'allowlist' === $collection ) {
				$key = array_search( $ua, $arr, true );
				if ( false !== $key && isset( $arr[ $key ] ) ) {
					unset( $arr[ $key ] );
					// Convert back to string.
					$this->whitelist = implode( PHP_EOL, $arr );
				}
			}

			$this->save();
		}
	}

	/**
	 * Add an UA to the list.
	 *
	 * @param string $ua   User agent name.
	 * @param string $collection blocklist|allowlist.
	 *
	 * @return void
	 */
	public function add_to_list( $ua, $collection ) {
		if ( 'blocklist' === $collection ) {
			if ( User_Agent_Service::is_blocklist_presets( $ua ) ) {
				$this->blocklist_presets         = true;
				$this->blocklist_preset_values[] = $ua;
			} elseif ( User_Agent_Service::is_script_presets( $ua ) ) {
				$this->script_presets         = true;
				$this->script_preset_values[] = $ua;
			} else {
				$this->blacklist = $this->push_ua_to_list( $ua, $collection );
			}
		} elseif ( 'allowlist' === $collection ) {
			$this->whitelist = $this->push_ua_to_list( $ua, $collection );
		}

		$this->save();
	}

	/**
	 * Push the UA to either blocklist (only in 'Custom User Agents') or allowlist
	 *
	 * @param string $ua   User agent name.
	 * @param string $collection List type i.e. blocklist or allowlist.
	 *
	 * @return string List as string format with UA delimited with newline character.
	 */
	private function push_ua_to_list( string $ua, string $collection ): string {
		$arr   = $this->get_lockout_list( $collection, false );
		$arr[] = trim( $ua );
		$arr   = array_unique( $arr );

		return implode( PHP_EOL, $arr );
	}

	/**
	 * Get the module name.
	 *
	 * @return string
	 */
	public static function get_module_name(): string {
		return esc_html__( 'User Agent Banning', 'wpdef' );
	}

	/**
	 * Get the module state.
	 *
	 * @param  bool $flag  Module state.
	 *
	 * @return string
	 */
	public static function get_module_state( $flag ): string {
		return $flag ? esc_html__( 'active', 'wpdef' ) : esc_html__( 'inactive', 'wpdef' );
	}

	/**
	 * Get the list of all selected blocklisted UA values.
	 *
	 * @param bool $lower Whether to convert the list to lowercase.
	 *
	 * @return array
	 */
	public function get_all_selected_blocklist_ua( $lower = true ): array {
		$blocklist_custom  = $this->get_lockout_list( 'blocklist', $lower );
		$blocklist_presets = $this->blocklist_presets ? $this->blocklist_preset_values : array();
		$script_presets    = $this->script_presets ? $this->script_preset_values : array();

		return array_merge( $blocklist_custom, $blocklist_presets, $script_presets );
	}

	/**
	 * Return the module slug.
	 *
	 * @return string
	 */
	public static function get_module_slug(): string {
		return 'ua-lockout';
	}
}