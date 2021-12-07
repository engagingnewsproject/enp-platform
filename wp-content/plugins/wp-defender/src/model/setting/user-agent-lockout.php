<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class User_Agent_Lockout extends Setting {
	/**
	 * Option name.
	 * @var string
	 */
	public $table = 'wd_user_agent_settings';

	/**
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;
	/**
	 * @var string
	 * @defender_property
	 */
	public $blacklist = '';
	/**
	 * @var string
	 * @defender_property
	 */
	public $whitelist = '';
	/**
	 * @var string
	 * @defender_property
	 */
	public $message = '';
	/**
	 * @var bool
	 * @defender_property
	 */
	public $empty_headers = false;

	protected $rules = array(
		array( array( 'enabled', 'empty_headers' ), 'boolean' ),
	);

	/**
	 * @return array
	*/
	public function get_default_values() {
		// Allowled User Agents.
		$whitelist  = "a6-indexer\nadsbot-google\naolbuild\napis-google\nbaidu\nbingbot\nbingpreview";
		$whitelist .= "\nbutterfly\ncloudflare\nchrome\nduckduckbot\nembedly\nfacebookexternalhit\nfacebot\ngoogle page speed";
		$whitelist .= "\ngooglebot\nia_archiver\nlinkedinbot\nmediapartners-google\nmsnbot\nnetcraftsurvey";
		$whitelist .= "\noutbrain\npinterest\nquora\nslackbot\nslurp\ntweetmemebot\ntwitterbot\nuptimerobot";
		$whitelist .= "\nurlresolver\nvkshare\nw3c_validator\nwordpress\nwp rocket\nyandex";

		return array(
			'message'   => __( 'You have been blocked from accessing this website.', 'wpdef' ),
			'whitelist' => $whitelist,
			// Blocked User Agents.
			'blacklist' => "MJ12Bot\nAhrefsBot\nSEMrushBot\nDotBot",
		);
	}

	protected function before_load() {
		$default_values  = $this->get_default_values();
		$this->message   = $default_values['message'];
		$this->whitelist = $default_values['whitelist'];
		$this->blacklist = $default_values['blacklist'];
	}

	/**
	 * @return bool
	 */
	public function is_active() {

		return apply_filters(
			'wd_user_agents_enable',
			$this->enabled
		);
	}

	/**
	 * Get list of blocklisted or allowlisted data.
	 *
	 * @param string $type blocklist|allowlist
	 * @param bool   $lower
	 *
	 * @return array
	 */
	public function get_lockout_list( $type = 'blocklist', $lower = true ) {
		$data = ( 'blocklist' === $type ) ? $this->blacklist : $this->whitelist;
		$arr  = is_array( $data ) ? $data : array_filter( explode( PHP_EOL, $data ) );
		$arr  = array_map( 'trim', $arr );
		if ( $lower ) {
			$arr  = array_map( 'strtolower', $arr );
		}

		return $arr;
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
			'enabled'       => __( 'User Agent Banning', 'wpdef' ),
			'message'       => __( 'Message', 'wpdef' ),
			'blacklist'     => __( 'Blocklist', 'wpdef' ),
			'whitelist'     => __( 'Allowlist', 'wpdef' ),
			'empty_headers' => __( 'Empty Headers', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * Get the access status of this UA.
	 *
	 * @param string $ua
	 *
	 * @return array
	 */
	public function get_access_status( $ua ) {
		$blocklist = $this->get_lockout_list( 'blocklist' );
		$allowlist = $this->get_lockout_list( 'allowlist' );
		if ( ! in_array( $ua, $blocklist, true ) && ! in_array( $ua, $allowlist, true ) ) {

			return array( 'na' );
		}

		$result = array();
		if ( in_array( $ua, $this->get_lockout_list( 'blocklist' ), true ) ) {
			$result[] = 'banned';
		}
		if ( in_array( $ua, $this->get_lockout_list( 'allowlist' ), true ) ) {
			$result[] = 'allowlist';
		}

		return $result;
	}

	/**
	 * @param string $ua
	 * @param string $list blocklist|allowlist
	 *
	 * @return bool
	 */
	public function is_ua_in_list( $ua, $list ) {
		$arr = $this->get_lockout_list( $list );

		return in_array( $ua, $arr, true );
	}

	/**
	 * Remove User Agent from a list.
	 *
	 * @param string $ua
	 * @param string $list blocklist|allowlist
	 *
	 * @return void
	 */
	public function remove_from_list( $ua, $list ) {
		$arr      = $this->get_lockout_list( $list );
		// Array can contain uppercase.
		$orig_arr = $this->get_lockout_list( $list, false );
		$key      = array_search( $ua, $arr, true );
		if ( false !== $key ) {
			unset( $orig_arr[ $key ] );
			if ( 'blocklist' === $list ) {
				$this->blacklist = implode( PHP_EOL, $orig_arr );
			} elseif ( 'allowlist' === $list ) {
				$this->whitelist = implode( PHP_EOL, $orig_arr );
			}

			$this->save();
		}
	}

	/**
	 * Add an UA to the list.
	 *
	 * @param string $ua
	 * @param string $list blocklist|allowlist
	 *
	 * @return void
	 */
	public function add_to_list( $ua, $list ) {
		$arr = $this->get_lockout_list( $list, false );
		$arr[] = trim( $ua );
		$arr   = array_unique( $arr );
		if ( 'blocklist' === $list ) {
			$this->blacklist = implode( PHP_EOL, $arr );
		} elseif ( 'allowlist' === $list ) {
			$this->whitelist = implode( PHP_EOL, $arr );
		}

		$this->save();
	}
}