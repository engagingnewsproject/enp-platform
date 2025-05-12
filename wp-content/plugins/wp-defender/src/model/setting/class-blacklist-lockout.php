<?php
/**
 * Handles blacklist and lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;
use WP_Defender\Traits\IP;
use WP_Defender\Integrations\MaxMind_Geolocation;

/**
 * Model for blacklist and lockout settings.
 */
class Blacklist_Lockout extends Setting {

	use IP;

	/**
	 * Option name.
	 *
	 * @var string
	 */
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
	 * @sanitize sanitize_textarea_field
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
	 * MaxMind license key.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_text_field
	 */
	public $maxmind_license_key = '';

	/**
	 * Retrieves the default values for the function.
	 *
	 * @return array An associative array containing the default values.
	 */
	public function get_default_values(): array {
		return array(
			'message' => esc_html__( 'The administrator has blocked your IP from accessing this website.', 'wpdef' ),
		);
	}

	/**
	 * Initializes the object before loading.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$default_values           = $this->get_default_values();
		$whitelist                = $this->get_list( 'allowlist' );
		$whitelist                = array_filter( $whitelist );
		$this->ip_whitelist       = implode( PHP_EOL, $whitelist );
		$this->ip_lockout_message = $default_values['message'];
	}

	/**
	 * Adds an IP address to a list of IP addresses.
	 *
	 * @param string $ip   The IP address to add.
	 * @param string $collection The type of list to add the IP address to. Must be either "blocklist" or "allowlist".
	 *
	 * @return void
	 */
	public function add_to_list( $ip, $collection ) {
		$arr = $this->get_list( $collection );
		if ( $this->validate_ip( $ip ) ) {
			$arr[] = trim( $ip );
			$arr   = array_unique( $arr );
			if ( 'blocklist' === $collection ) {
				$this->ip_blacklist = implode( PHP_EOL, $arr );
			} elseif ( 'allowlist' === $collection ) {
				$this->ip_whitelist = implode( PHP_EOL, $arr );
			}

			$this->save();
		}
	}

	/**
	 * Checks if an IP address is in a given list.
	 *
	 * @param string $ip   The IP address to check.
	 * @param string $collection The type of list to check against. Must be either "blocklist" or "allowlist".
	 *
	 * @return bool Returns true if the IP address is in the list, false otherwise.
	 */
	public function is_ip_in_list( $ip, $collection ): bool {
		$arr = $this->get_list( $collection );
		if ( $this->validate_ip( $ip ) && in_array( $ip, $arr, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove IP from a list.
	 *
	 * @param string $ip   The IP address to check.
	 * @param string $collection The type of list to check against. Must be either "blocklist" or "allowlist".
	 *
	 * @return void
	 */
	public function remove_from_list( $ip, $collection ) {
		$arr = $this->get_list( $collection );
		$key = array_search( $ip, $arr, true );
		if ( false !== $key ) {
			unset( $arr[ $key ] );
			if ( 'blocklist' === $collection ) {
				$this->ip_blacklist = implode( PHP_EOL, $arr );
			} elseif ( 'allowlist' === $collection ) {
				$this->ip_whitelist = implode( PHP_EOL, $arr );
			}

			$this->save();
		}
	}

	/**
	 * We're going to use this for filter the IPs, as we use textarea to submit, so it can contain some un-valid IPs.
	 */
	protected function after_validate(): void {
		$lists  = array(
			'ip_blacklist' => $this->get_list( 'blocklist' ),
			'ip_whitelist' => $this->get_list( 'allowlist' ),
		);
		$errors = array();

		foreach ( $lists as $key => &$collection ) {
			foreach ( $collection as $i => $v ) {
				$messages = $this->display_validation_message( $v );
				if ( ! empty( $messages ) ) {
					unset( $collection[ $i ] );
					$errors = array_merge( $errors, $messages );
				}
			}
			$this->$key = implode( PHP_EOL, array_filter( $collection ) );
		}

		if ( ! empty( $errors ) ) {
			$this->errors[] = esc_html__( 'Invalid IP addresses detected. Please fix the following errors:', 'wpdef' );
			$this->errors   = array_merge( $this->errors, $errors );
		}
	}

	/**
	 * Get list of blocklisted or allowlisted IPs.
	 *
	 * @param  string $type  blocklist|allowlist.
	 *
	 * @return array
	 */
	public function get_list( $type = 'blocklist' ): array {
		// The list should be always strings.
		$collection = ( 'blocklist' === $type ) ? $this->ip_blacklist : $this->ip_whitelist;
		$arr        = preg_split( '/\r\n|\r|\n/', $collection );
		if ( ! is_array( $arr ) ) {
			return array();
		}

		$arr = array_map(
			function ( $value ) {
				return strtolower( trim( $value ) );
			},
			$arr
		);

		return array_filter( $arr );
	}

	/**
	 * Get list of blacklisted countries.
	 *
	 * @return array
	 */
	public function get_country_blacklist(): array {
		return $this->country_blacklist;
	}

	/**
	 * Get list of whitelisted countries.
	 *
	 * @return array
	 */
	public function get_country_whitelist(): array {
		return $this->country_whitelist;
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'ip_blacklist'        => esc_html__( 'IP Banning - IP Addresses Blocklist', 'wpdef' ),
			'ip_whitelist'        => esc_html__( 'IP Banning - IP Addresses Allowlist', 'wpdef' ),
			'country_blacklist'   => esc_html__( 'IP Banning - Country Allowlist', 'wpdef' ),
			'country_whitelist'   => esc_html__( 'IP Banning - Country Blocklist', 'wpdef' ),
			'ip_lockout_message'  => esc_html__( 'IP Banning - Lockout Message', 'wpdef' ),
			'maxmind_license_key' => esc_html__( 'MaxMind license key', 'wpdef' ),
		);
	}

	/**
	 * Executes after loading the object.
	 *
	 * @return void
	 */
	protected function after_load(): void {
		if (
			! empty( $this->geodb_path ) &&
			is_string( $this->geodb_path ) &&
			strlen( $this->geodb_path ) > 0
		) {
			$service_geo = wd_di()->get( MaxMind_Geolocation::class );

			preg_match( '#.*[\\\/](.*[\\\/].*)$#', $this->geodb_path, $matches );

			$this->geodb_path = $service_geo->get_db_base_path() . DIRECTORY_SEPARATOR . $matches[1];
		}
	}

	/**
	 * Returns the module name.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Local Blocklist', 'wpdef' );
	}
}