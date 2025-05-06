<?php
/**
 * Handle user agent lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for user agent lockout settings.
 */
class User_Agent_Lockout extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_user_agent_settings';

	/**
	 * Is module enabled.
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
	 * Rules for validation.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled', 'empty_headers' ), 'boolean' ),
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

		return array(
			'message'   => esc_html__( 'You have been blocked from accessing this website.', 'wpdef' ),
			'whitelist' => $whitelist,
			// Blocked User Agents.
			'blacklist' => "MJ12Bot\nDotBot",
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
		$default_values  = $this->get_default_values();
		$this->message   = $default_values['message'];
		$this->whitelist = $default_values['whitelist'];
		$this->blacklist = $default_values['blacklist'];
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
		$data = ( 'blocklist' === $type ) ? $this->blacklist : $this->whitelist;
		$arr  = is_array( $data ) ? $data : array_filter( preg_split( "/\r\n|\n|\r/", $data ) );
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
		$blocklist = str_replace( '#', '\#', $this->get_lockout_list( 'blocklist' ) );
		$allowlist = str_replace( '#', '\#', $this->get_lockout_list( 'allowlist' ) );

		$blocklist_regex_pattern = '#' . implode( '|', $blocklist ) . '#i';
		$allowlist_regex_pattern = '#' . implode( '|', $allowlist ) . '#i';

		$blocklist_match = preg_match( $blocklist_regex_pattern, $ua );
		$allowlist_match = preg_match( $allowlist_regex_pattern, $ua );

		if ( empty( $blocklist_match ) && empty( $allowlist_match ) ) {

			return array( 'na' );
		}

		$result = array();

		if ( ! empty( $blocklist_match ) && empty( $allowlist_match ) ) {
			$result[] = 'banned';
		}
		if ( ! empty( $allowlist_match ) ) {
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
		$arr = str_replace( '#', '\#', $this->get_lockout_list( $collection ) );

		$list_regex_pattern = '#' . implode( '|', $arr ) . '#i';

		$list_match = preg_match( $list_regex_pattern, $ua );

		return ! empty( $list_match );
	}

	/**
	 * Remove User Agent from a list.
	 *
	 * @param string $ua   The user agent to remove.
	 * @param string $collection blocklist|allowlist.
	 *
	 * @return void
	 */
	public function remove_from_list( $ua, $collection ) {
		$arr = $this->get_lockout_list( $collection );
		// Array can contain uppercase.
		$orig_arr = str_replace( '#', '\#', $this->get_lockout_list( $collection, false ) );

		$list_regex_pattern = '#' . implode( '|', $arr ) . '#i';

		$list_match = preg_match( $list_regex_pattern, $ua );

		if ( false !== $list_match ) {

			// Plain string match. For e.g. r.n regex matches ran & run but we can add/block UA string name if user send the useragent name as run then we include that in allowlist so run won't be blocked but ran will be blocked.
			$key = array_search( $ua, $arr, true );

			if ( false !== $key && isset( $orig_arr[ $key ] ) ) {
				unset( $orig_arr[ $key ] );
				$is_string_match = true;
			} else {
				// If plain string not matched then add the user agent in opposite list if unban is clicked then add that string to allow list, else if ban user agent clicked then add that string to blocklist though allow list take higher priority i.e. in allow list r.n present then adding r.n or run or ran in blocklist won't block the user agent because of priority.
				$is_string_match = false;
			}

			if ( 'blocklist' === $collection ) {
				$this->blacklist = implode( PHP_EOL, $orig_arr );

				if ( false === $is_string_match ) {
					$this->whitelist = $this->push_ua_to_list( $ua, 'allowlist' );
				}
			} elseif ( 'allowlist' === $collection ) {
				$this->whitelist = implode( PHP_EOL, $orig_arr );

				if ( false === $is_string_match ) {
					$this->blacklist = $this->push_ua_to_list( $ua, 'blocklist' );
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
			$this->blacklist = $this->push_ua_to_list( $ua, $collection );
		} elseif ( 'allowlist' === $collection ) {
			$this->whitelist = $this->push_ua_to_list( $ua, $collection );
		}

		$this->save();
	}

	/**
	 * Push the UA to either blocklist or allowlist
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
}