<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;
use WP_Defender\Traits\IP;

/**
 * Class Blacklist_Lockout
 *
 * @package WP_Defender\Model\Setting
 */
class Blacklist_Lockout extends Setting {
	use IP;

	protected $table = 'wd_blacklist_lockout_settings';
	/**
	 * Store a list of IPs blocked from the site, the priority of this list is lower than whitelist.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip_blacklist = '';
	/**
	 * Top priority, if an IP in this list, mean we never check any on them.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip_whitelist = '';
	/**
	 * The message to show on frontend when a blocklisted IP access the site, recommend to use something generic,
	 * so we don't expose our intention.
	 *
	 * @var string
	 * @defender_property
	 */
	public $ip_lockout_message = '';

	/**
	 * This should be use if you don't want an IP from some country to access your site, the error message will refer to
	 * $ip_lockout_message.
	 *
	 * @var array
	 * @defender_property
	 */
	public $country_blacklist = array();

	/**
	 * This uses when you want to block all and allow some countries, it will have less priority than the IP
	 * white/black above.
	 *
	 * @var array
	 * @defender_property
	 */
	public $country_whitelist = array();

	/**
	 * Path to downloaded GeoDB.
	 * Important: This var doesn't support Union Types. So just 'string'.
	 *
	 * @var string
	 * @defender_property
	 */
	public $geodb_path = null;

	/**
	 * @return array
	 */
	public function get_default_values() {

		return array(
			'message' => __( 'The administrator has blocked your IP from accessing this website.', 'wpdef' ),
		);
	}

	protected function before_load() {
		$default_values           = $this->get_default_values();
		$whitelist                = $this->get_list( 'allowlist' );
		$whitelist                = array_filter( $whitelist );
		$this->ip_whitelist       = implode( PHP_EOL, $whitelist );
		$this->ip_lockout_message = $default_values['message'];
	}

	/**
	 * Add an IP to the list, this should be the **ONLY** way to add an IP to a list.
	 *
	 * @param string $ip
	 * @param string $list blocklist|allowlist
	 *
	 * @return void
	 */
	public function add_to_list( $ip, $list ) {
		$arr = $this->get_list( $list );
		if ( $this->validate_ip( $ip ) ) {
			$arr[] = trim( $ip );
			$arr   = array_unique( $arr );
			if ( 'blocklist' === $list ) {
				$this->ip_blacklist = implode( PHP_EOL, $arr );
			} elseif ( 'allowlist' === $list ) {
				$this->ip_whitelist = implode( PHP_EOL, $arr );
			}

			$this->save();
		}
	}

	/**
	 * @param string $ip
	 * @param string $list
	 *
	 * @return bool
	 */
	public function is_ip_in_list( $ip, $list ) {
		$arr = $this->get_list( $list );
		if ( $this->validate_ip( $ip ) && in_array( $ip, $arr ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove IP from a list.
	 *
	 * @param string $ip
	 * @param string $list blocklist|allowlist
	 *
	 * @return void
	 */
	public function remove_from_list( $ip, $list ) {
		$arr = $this->get_list( $list );
		$key = array_search( $ip, $arr, true );
		if ( false !== $key ) {
			unset( $arr[ $key ] );
			if ( 'blocklist' === $list ) {
				$this->ip_blacklist = implode( PHP_EOL, $arr );
			} elseif ( 'allowlist' === $list ) {
				$this->ip_whitelist = implode( PHP_EOL, $arr );
			}

			$this->save();
		}
	}

	/**
	 * Check downloaded GeoDB.
	 *
	 * @return bool
	 */
	public function is_geodb_downloaded() {
		if ( is_null( $this->geodb_path ) || ! is_file( $this->geodb_path ) ) {
			return false;
		}

		// Check if the file is on the site. The file can exist on the same server but for different sites.
		// For example, after config importing.
		$path_parts = pathinfo( $this->geodb_path );
		if ( preg_match( '/(\/wp-content\/.+)/', $path_parts['dirname'], $matches ) ) {
			$rel_path = $matches[1];
			$rel_path = ltrim( $rel_path, '/' );
			$abs_path = ABSPATH . $rel_path;
			if ( ! is_dir( $abs_path ) ) {
				wp_mkdir_p( $abs_path );
			}

			$rel_path = $abs_path . DIRECTORY_SEPARATOR . $path_parts['basename'];
			if ( file_exists( $rel_path ) ) {

				return true;
			} elseif ( ! empty( $this->geodb_path ) && file_exists( $this->geodb_path ) ) {
				// The case if ABSPATH was changed e.g. in wp-config.php.
				return true;
			}

			if ( move_uploaded_file( $this->geodb_path, $rel_path ) ) {
				$this->geodb_path = $rel_path;
				$this->save();
			} else {

				return false;
			}
		}

		return true;
	}

	/**
	 * We're going to use this for filter the IPs, as we use textarea to submit, so it can contain some un-valid IPs.
	 */
	public function after_validate() {
		$lists  = array(
			'ip_blacklist' => $this->get_list( 'blocklist' ),
			'ip_whitelist' => $this->get_list( 'allowlist' )
		);
		$errors = array();

		foreach ( $lists as $key => &$list ) {
			foreach ( $list as $i => $v ) {
				$messages = $this->display_validation_message( $v );
				if ( ! empty( $messages ) ) {
					unset( $list[ $i ] );
					$errors = array_merge( $errors, $messages );
				}
			}
			$this->$key = implode( PHP_EOL, array_filter( $list ) );
		}

		if ( ! empty( $errors ) ) {
			$this->errors[] = __( 'Invalid IP addresses detected. Please fix the following errors:', 'wpdef' );
			$this->errors   = array_merge( $this->errors, $errors );

			return false;
		}
	}

	/**
	 * Get list of blocklisted or allowlisted IPs.
	 *
	 * @param string $type blocklist|allowlist
	 *
	 * @return array
	 */
	public function get_list( $type = 'blocklist' ) {
		// The list should be always strings.
		$list = ( 'blocklist' === $type ) ? $this->ip_blacklist : $this->ip_whitelist;
		$arr  = array_filter( explode( PHP_EOL, $list ) );
		$arr  = array_map( 'trim', $arr );
		$arr  = array_map( 'strtolower', $arr );

		return $arr;
	}

	/**
	 * Get list of blacklisted countries.
	 *
	 * @return array
	 */
	public function get_country_blacklist() {
		return $this->country_blacklist;
	}

	/**
	 * Get list of whitelisted countries.
	 *
	 * @return array
	 */
	public function get_country_whitelist() {
		return $this->country_whitelist;
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
			'ip_blacklist'       => __( 'IP Banning - IP Addresses Blocklist', 'wpdef' ),
			'ip_whitelist'       => __( 'IP Banning - IP Addresses Allowlist', 'wpdef' ),
			'country_blacklist'  => __( 'IP Banning - Country Allowlist', 'wpdef' ),
			'country_whitelist'  => __( 'IP Banning - Country Blocklist', 'wpdef' ),
			'ip_lockout_message' => __( 'IP Banning - Lockout Message', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
