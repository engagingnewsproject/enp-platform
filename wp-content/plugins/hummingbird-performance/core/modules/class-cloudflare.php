<?php
/**
 * Cloudflare module.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Traits\Module as ModuleContract;
use Hummingbird\Core\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cloudflare
 */
class Cloudflare extends Module {

	use ModuleContract;

	/**
	 * Module slug name
	 *
	 * @var string
	 */
	protected $slug = 'cloudflare';

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'Cloudflare';

	/**
	 * Initializes Cloudflare module
	 */
	public function init() {
		add_filter( 'wp_hummingbird_is_active_module_cloudflare', array( $this, 'module_status' ) );
	}

	/**
	 * Run module actions.
	 *
	 * @since 3.0.0
	 */
	public function run() {
		if ( $this->is_connected() && $this->is_zone_selected() ) {
			add_action( 'init', array( $this, 'init_apo' ) );

			if ( $this->is_apo_enabled() ) {
				add_action( 'wphb_cache_directory_cleared', array( $this, 'clear_cache' ) );
				add_action( 'wphb_cloudflare_apo_clear_cache', array( $this, 'clear_post_cache' ) );
				add_action( 'switch_theme', array( $this, 'clear_cache' ) );
				add_action( 'customize_save_after', array( $this, 'clear_cache' ) );

				if ( ! Settings::get_setting( 'enabled', 'page_cache' ) ) {
					add_action( 'deleted_post', array( $this, 'clear_post_cache' ) );
					add_action( 'delete_attachment', array( $this, 'clear_post_cache' ) );
					add_action( 'transition_post_status', array( $this, 'post_status_change' ), 10, 3 );
					add_action( 'transition_comment_status', array( $this, 'comment_status_change' ), 10, 3 );
					add_action( 'comment_post', array( $this, 'clear_on_comment_post' ), 10, 3 );
				}
			}
		}
	}

	/**
	 * Detect if site is using Cloudflare
	 *
	 * @param bool $force If set to true it will check again.
	 *
	 * @return bool
	 */
	public function has_cloudflare( $force = false ) {
		if ( filter_input( INPUT_GET, 'wphb-check-cf', FILTER_VALIDATE_BOOLEAN ) ) {
			// If we're checking do not try to check again or it will return a timeout.
			return (bool) Settings::get_setting( 'connected', $this->slug );
		}

		if ( $force ) {
			Settings::update_setting( 'connected', false, $this->slug );
		}

		$options = $this->get_options();

		$is_cloudflare_db = isset( $options['connected'] ) ? $options['connected'] : false;

		// Check once every hour.
		if ( ! $force && isset( $options['last_check'] ) && ( (int) $options['last_check'] + HOUR_IN_SECONDS ) >= time() ) {
			return $is_cloudflare_db;
		}

		$is_cloudflare = false;
		if ( ! is_numeric( $is_cloudflare_db ) || $force ) {
			$url  = add_query_arg( 'wphb-check-cf', 'true', home_url() );
			$head = wp_remote_head(
				$url,
				array(
					'sslverify' => false,
				)
			);

			if ( ! is_wp_error( $head ) ) {
				$headers = wp_remote_retrieve_headers( $head );
				if ( isset( $headers['server'] ) && strpos( $headers['server'], 'cloudflare' ) > -1 ) {
					$is_cloudflare = true;
				}
			}

			// Only write if value changes.
			if ( $is_cloudflare_db !== $is_cloudflare || is_wp_error( $head ) ) {
				Settings::update_setting( 'connected', $is_cloudflare, $this->slug );
			}

			Settings::update_setting( 'last_check', time(), $this->slug );
		}

		$is_cloudflare = (bool) $is_cloudflare;
		return apply_filters( 'wphb_has_cloudflare', $is_cloudflare );
	}

	/**
	 * Check if Cloudflare is connected.
	 *
	 * Connected means that credentials are correct. Does not necessarily mean that a zone is selected.
	 *
	 * @return bool
	 */
	public function is_connected() {
		$options = $this->get_options();

		return $options['enabled'];
	}

	/**
	 * Check if zone is selected.
	 *
	 * @return bool
	 */
	public function is_zone_selected() {
		$options = $this->get_options();

		return ! empty( $options['zone'] );
	}

	/**
	 * Check if APO is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_apo_enabled() {
		$options = $this->get_options();

		return isset( $options['apo']['enabled'] ) ? $options['apo']['enabled'] : false;
	}

	/**
	 * Get Cloudflare plan.
	 *
	 * @return mixed
	 */
	public function get_plan() {
		$options = $this->get_options();

		return $options['plan'];
	}

	/**
	 * Tries to set the same caching rules in CF.
	 */
	private function set_caching_rules() {
		if ( ! $this->is_connected() || ! $this->is_zone_selected() ) {
			return;
		}

		$this->clear_caching_page_rules();
		$this->clear_caching_page_rules();

		$expirations = $this->get_filetypes_expirations();

		foreach ( $expirations as $filetype => $expiration ) {
			$this->add_caching_page_rule( $filetype );
		}
	}

	/**
	 * Clear Cloudflare caching page rules.
	 */
	private function clear_caching_page_rules() {
		$rules = $this->get_registered_caching_page_rules();

		foreach ( $rules as $filetype => $id ) {
			$this->delete_caching_page_rule( $filetype );
		}
	}

	/**
	 * Delete Cloudflare caching page rule.
	 *
	 * @param string $filetype  File type.
	 */
	private function delete_caching_page_rule( $filetype ) {
		$id = $this->get_registered_caching_page_rule_id( $filetype );
		$this->unregister_caching_page_rule( $filetype );

		if ( ! $this->is_connected() || ! $this->is_zone_selected() ) {
			return;
		}

		$options = $this->get_options();

		Utils::get_api()->cloudflare->delete_page_rule( $id, $options['zone'] );
	}

	/**
	 * Update Cloudflare caching page rule.
	 *
	 * @param string $filetype  File type.
	 *
	 * @return bool
	 */
	private function update_caching_page_rule( $filetype ) {
		// Check if the rule exists already.
		$id = $this->get_registered_caching_page_rule_id( $filetype );

		if ( $id ) {
			// Delete the rule and add it a new one.
			$this->delete_caching_page_rule( $filetype );
		}

		return $this->add_caching_page_rule( $filetype );
	}

	/**
	 * Add Cloudflare caching page rule.
	 *
	 * @param string $filetype  File type.
	 *
	 * @return bool
	 */
	private function add_caching_page_rule( $filetype ) {
		// If exists, delete it.
		$this->delete_caching_page_rule( $filetype );

		if ( ! $this->is_connected() || ! $this->is_zone_selected() ) {
			return false;
		}

		$expirations = $this->get_filetypes_expirations();

		if ( ! isset( $expirations[ $filetype ] ) ) {
			return false;
		}

		if ( ! $expirations[ $filetype ] ) {
			return false;
		}

		$targets = self::page_rule_targets( $filetype );
		$actions = self::page_rule_actions( $expirations[ $filetype ] );

		$options = $this->get_options();

		$result = Utils::get_api()->cloudflare->add_page_rule( $targets, $actions, $options['zone'] );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$this->register_caching_page_rule( $result->result->id, $filetype );
		return $result->result->id;

	}

	/**
	 * Get expiration values.
	 *
	 * @return array
	 */
	private function get_filetypes_expirations() {
		$options = $this->get_options();

		$expirations  = array();
		$_expirations = array(
			'css'  => $options['expiry_css'],
			'js'   => $options['expiry_javascript'],
			'jpg'  => $options['expiry_images'],
			'png'  => $options['expiry_images'],
			'jpeg' => $options['expiry_images'],
			'gif'  => $options['expiry_images'],
			'mp3'  => $options['expiry_media'],
			'mp4'  => $options['expiry_media'],
			'ico'  => $options['expiry_media'],
		);

		foreach ( $_expirations as $filetype => $time ) {
			if ( ! $time ) {
				$expirations[ $filetype ] = false;
				continue;
			}

			$time = explode( '/', $time );
			if ( 2 !== count( $time ) ) {
				$expirations[ $filetype ] = false;
				continue;
			}

			$time = absint( ltrim( $time[1], 'A' ) );

			if ( ! $time ) {
				$expirations[ $filetype ] = false;
				continue;
			}

			$expirations[ $filetype ] = $time;
		}

		return $expirations;
	}

	/**
	 * Page rule targets.
	 *
	 * @param string $filetype  File type.
	 *
	 * @return array
	 */
	private static function page_rule_targets( $filetype ) {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			array(
				'target'     => 'url',
				'constraint' => array(
					'operator' => 'matches',
					'value'    => '*' . $host . '*.' . $filetype,
				),
			),
		);
	}

	/**
	 * Page rule actions.
	 *
	 * @param string $time  Time.
	 *
	 * @return array
	 */
	private static function page_rule_actions( $time ) {
		return array(
			array(
				'id'    => 'browser_cache_ttl',
				'value' => $time,
			),
		);
	}

	/**
	 * Register a rule added to CF so they can be listed them later
	 *
	 * @param int    $id        Id.
	 * @param string $filetype  File type.
	 */
	private function register_caching_page_rule( $id, $filetype ) {
		$options = $this->get_options();

		$options['page_rules'][ $filetype ] = $id;

		$this->update_options( $options );
	}

	/**
	 * Register a rule added to CF so they can be listed them later
	 *
	 * @param string $filetype  File type.
	 */
	private function unregister_caching_page_rule( $filetype ) {
		$options = $this->get_options();

		if ( isset( $options['page_rules'][ $filetype ] ) ) {
			unset( $options['page_rules'][ $filetype ] );
			$this->update_options( $options );
		}
	}

	/**
	 * Get the ID of registered rule.
	 *
	 * @param string $filetype  File type.
	 *
	 * @return bool
	 */
	private function get_registered_caching_page_rule_id( $filetype ) {
		$options = $this->get_options();

		return isset( $options['page_rules'][ $filetype ] ) ? $options['page_rules'][ $filetype ] : false;
	}

	/**
	 * Get registered caching rules.
	 *
	 * @return mixed
	 */
	private function get_registered_caching_page_rules() {
		$options = $this->get_options();

		return $options['page_rules'];
	}

	/**
	 * Get a list of Cloudflare zones
	 *
	 * @param int   $page   Current page.
	 * @param array $zones  List of zones.
	 *
	 * @return WP_Error|array
	 */
	public function get_zones_list( $page = 1, $zones = array() ) {
		if ( is_wp_error( $zones ) ) {
			return $zones;
		}

		$result = Utils::get_api()->cloudflare->get_zones_list( $page );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$_zones = $result->result;
		foreach ( (array) $_zones as $zone ) {
			$zones[] = array(
				'account' => $zone->account->id,
				'value'   => $zone->id,
				'label'   => $zone->name,
				'plan'    => $zone->plan->legacy_id,
			);
		}

		if ( $result->result_info->total_pages > $page ) {
			// Get the next page.
			return $this->get_zones_list( ++$page, $zones );
		}

		return $zones;
	}

	/**
	 * See if the zones are valid or not.
	 *
	 * Used in AJAX calls.
	 *
	 * @since 3.0.0
	 *
	 * @param array|WP_Error $zones  List of zones or an error.
	 */
	public function validate_zones( $zones ) {
		if ( ! is_wp_error( $zones ) ) {
			return;
		}

		switch ( $zones->get_error_code() ) {
			case 400: // Cloudflare error: Invalid request headers (probably API key is too short or too long).
			case 403: // Cloudflare error: Unknown X-Auth-Key or X-Auth-Email.
				wp_send_json_error(
					array(
						'code'    => $zones->get_error_code(),
						'message' => $zones->get_error_message(),
					)
				);
				break;
			default:
				$message = sprintf( '<strong>%s</strong> [%s]', $zones->get_error_message(), $zones->get_error_code() );
				wp_send_json_error( array( 'message' => $message ) );
				break;
		}
	}

	/**
	 * Separate common functionality.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $zones   List of zones.
	 * @param string $domain  Force this domain.
	 *
	 * @return bool
	 */
	public function process_zones( $zones, $domain = '' ) {
		$matched_zone = $this->find_matching_zone( $zones, $domain );

		if ( ! $matched_zone ) {
			return false;
		}

		$options = $this->get_options();

		// Zone found. Save settings.
		$options['account_id'] = $matched_zone['account'];
		$options['zone']       = $matched_zone['value'];
		$options['zone_name']  = $matched_zone['label'];
		$options['plan']       = $matched_zone['plan'];

		$this->update_options( $options );
		$this->set_caching_expiration( YEAR_IN_SECONDS ); // Set recommended value.

		$this->get_apo_settings();

		return true;
	}

	/**
	 * Try to auto map current site domain to a valid zone.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $zones   List of zones.
	 * @param string $domain  Domain. In case we want to force.
	 *
	 * @return bool|array
	 */
	public function find_matching_zone( $zones, $domain = '' ) {
		$site_url      = empty( $domain ) ? get_site_url() : $domain;
		$site_url      = rtrim( preg_replace( '/^https?:\/\//', '', $site_url ), '/' );
		$plucked_zones = wp_list_pluck( $zones, 'label' );
		$found         = preg_grep( '/.*' . $site_url . '.*/', $plucked_zones );

		if ( is_array( $found ) && count( $found ) === 1 && isset( $zones[ key( $found ) ]['value'] ) ) {
			return $zones[ key( $found ) ];
		}

		return false;
	}

	/**
	 * Get a list of all page rules in CF
	 *
	 * @return WP_Error|array
	 */
	private function get_page_rules_list() {
		$options = $this->get_options();

		$result = Utils::get_api()->cloudflare->get_page_rules_list( $options['zone'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result->result;
	}

	/**
	 * Set caching expiration.
	 *
	 * @param int $value  Expiration value.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function set_caching_expiration( $value ) {
		$options = $this->get_options();

		$frequencies = self::get_frequencies();
		if ( ! $value || ! array_key_exists( (int) $value, $frequencies ) ) {
			return new WP_Error( 'cf_invalid_value', __( 'Invalid Cloudflare expiration value', 'wphb' ) );
		}

		$options['cache_expiry'] = (int) $value;
		$this->update_options( $options );

		return Utils::get_api()->cloudflare->set_caching_expiration( $options['zone'], (int) $value );
	}

	/**
	 * Get caching expiration.
	 *
	 * @param bool $refresh  Refresh data from API.
	 *
	 * @return array|int|WP_Error
	 */
	public function get_caching_expiration( $refresh = false ) {
		$options = $this->get_options();

		if ( ! $refresh && isset( $options['cache_expiry'] ) ) {
			return $options['cache_expiry'];
		}

		$result = Utils::get_api()->cloudflare->get_caching_expiration( $options['zone'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $refresh ) {
			$options['cache_expiry'] = $result->result->value;
			$this->update_options( $options );
		}

		return $result->result->value;
	}

	/**
	 * Implement abstract parent method for clearing cache.
	 *
	 * @since 1.7.1 Changed name from purge_cache to clear_cache
	 *
	 * @return mixed
	 */
	public function clear_cache() {
		$options = $this->get_options();

		$result = Utils::get_api()->cloudflare->purge_cache( $options['zone'] );

		return is_wp_error( $result ) ? $result : $result->result;
	}

	/**
	 * Check if Cloudflare is disconnected.
	 *
	 * @used-by \Hummingbird\Admin\Pages\Caching::trigger_load_action()
	 */
	public function disconnect() {
		$options = $this->get_options();

		$this->toggle_apo( false );

		$this->clear_caching_page_rules();

		$options['enabled']   = false;
		$options['connected'] = false;
		$options['email']     = '';
		$options['api_key']   = '';
		$options['zone']      = '';
		$options['zone_name'] = '';
		$options['plan']      = '';
		$options['apo']       = '';

		$this->update_options( $options );
	}

	/**
	 * Get module status.
	 *
	 * @param bool $current  Current status.
	 *
	 * @return bool
	 */
	public function module_status( $current ) {
		$options = $this->get_options();
		if ( ! $options['enabled'] && empty( $options['zone'] ) ) {
			return $current;
		}

		return true;
	}

	/**
	 * Get an array of caching frequencies for Cloudflare.
	 *
	 * @return array
	 */
	public static function get_frequencies() {
		return array(
			7200     => __( '2 hours', 'wphb' ),
			10800    => __( '3 hours', 'wphb' ),
			14400    => __( '4 hours', 'wphb' ),
			18000    => __( '5 hours', 'wphb' ),
			28800    => __( '8 hours', 'wphb' ),
			43200    => __( '12 hours', 'wphb' ),
			57600    => __( '16 hours', 'wphb' ),
			72000    => __( '20 hours', 'wphb' ),
			86400    => __( '1 day', 'wphb' ),
			172800   => __( '2 days', 'wphb' ),
			259200   => __( '3 days', 'wphb' ),
			345600   => __( '4 days', 'wphb' ),
			432000   => __( '5 days', 'wphb' ),
			691200   => __( '8 days', 'wphb' ),
			1382400  => __( '16 days', 'wphb' ),
			2073600  => __( '24 days', 'wphb' ),
			2678400  => __( '1 month', 'wphb' ),
			5356800  => __( '2 months', 'wphb' ),
			16070400 => __( '6 months', 'wphb' ),
			31536000 => __( '1 year', 'wphb' ),
		);
	}

	/**
	 * Convert Cloudflare frequency to normal. Used when updating the custom code in browser caching.
	 *
	 * @param  int $frequency  Cloudflare frequency to convert.
	 *
	 * @return string  Caching frequency.
	 */
	public function convert_frequency( $frequency ) {
		$frequencies = array(
			7200     => '2h/A7200',
			10800    => '3h/A10800',
			14400    => '4h/A14400',
			18000    => '5h/A18000',
			28800    => '8h/A28800',
			43200    => '12h/A43200',
			57600    => '16h/A57600',
			72000    => '20h/A72000',
			86400    => '1d/A86400',
			172800   => '2d/A172800',
			259200   => '3d/A259200',
			345600   => '4d/A345600',
			432000   => '5d/A432000',
			691200   => '8d/A691200',
			1382400  => '16d/A1382400',
			2073600  => '24d/A2073600',
			2678400  => '1M/A2678400',
			5356800  => '2M/A5356800',
			16070400 => '6M/A16070400',
			31536000 => '1y/A31536000',
		);

		return $frequencies[ $frequency ];
	}

	/**
	 * Query APO settings.
	 *
	 * @since 3.0.0
	 */
	public function get_apo_settings() {
		$options = $this->get_options();

		Utils::get_api()->cloudflare->set_zone( $options['zone'] );

		// Try to check if APO is purchased and available.
		$entitlements = Utils::get_api()->cloudflare->get_entitlements();

		if ( is_wp_error( $entitlements ) ) {
			// Make sure APO is marked as not purchased.
			if ( isset( $options['apo_paid'] ) && $options['apo_paid'] ) {
				$options['apo_paid'] = false;
				$this->update_options( $options );
			}

			return;
		}

		$features  = wp_list_pluck( $entitlements->result, 'id' );
		$purchased = in_array( 'zone.automatic_platform_optimization', $features, true );

		if ( isset( $options['apo_paid'] ) && $purchased !== $options['apo_paid'] ) {
			$options['apo_paid'] = $purchased;
			$this->update_options( $options );
		}

		$apo = Utils::get_api()->cloudflare->get_apo_settings();

		if ( is_wp_error( $apo ) ) {
			return;
		}

		if ( isset( $apo->result->value ) ) {
			$options['apo'] = (array) $apo->result->value;
			$this->update_options( $options );
		}
	}

	/**
	 * Toggle Cloudflare APO.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $status  New service status.
	 */
	public function toggle_apo( $status ) {
		$options = $this->get_options();

		Utils::get_api()->cloudflare->set_zone( $options['zone'] );

		$hostnames = array();
		if ( $status ) {
			$site = get_site_url();
			preg_match_all( '/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/\n]+)/im', $site, $domain );
			$hostnames = array( $domain[1][0], 'www.' . $domain[1][0] );

			do_action( 'wphb_clear_page_cache' ); // Clear all page cache.
		}

		$apo_settings = array(
			'enabled'   => (bool) $status,
			'cf'        => (bool) $status,
			'wordpress' => (bool) $status,
			'wp_plugin' => (bool) $status,
			'hostnames' => $hostnames,
		);

		// Make sure we set the default cache_by_device_type if it's a first enable.
		if ( ! isset( $options['apo'] ) || ! isset( $options['apo']['cache_by_device_type'] ) ) {
			$apo_settings['cache_by_device_type'] = (bool) $status;
		}

		$apo = Utils::get_api()->cloudflare->set_apo_settings( $apo_settings );

		if ( is_wp_error( $apo ) ) {
			return;
		}

		if ( isset( $apo->result->value ) ) {
			$options['apo'] = (array) $apo->result->value;
			$this->update_options( $options );
		}
	}

	/**
	 * Toggle Cloudflare APO cache by device type setting.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $status  New value.
	 */
	public function toggle_cache_by_device( $status ) {
		$options = $this->get_options();

		Utils::get_api()->cloudflare->set_zone( $options['zone'] );

		$apo_settings = array(
			'cache_by_device_type' => (bool) $status,
		);

		$apo_settings = wp_parse_args( $apo_settings, $options['apo'] );

		$apo = Utils::get_api()->cloudflare->set_apo_settings( $apo_settings );

		if ( is_wp_error( $apo ) ) {
			return;
		}

		if ( isset( $apo->result->value ) ) {
			$options['apo'] = (array) $apo->result->value;
			$this->update_options( $options );
		}
	}

	/**
	 * Clear URLs associated with a specific WordPress post.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id  Post ID.
	 */
	public function clear_post_cache( $post_id ) {
		$options = $this->get_options();

		Utils::get_api()->cloudflare->set_zone( $options['zone'] );

		$urls = $this->get_urls_for_post( $post_id );

		if ( ! $urls ) {
			return;
		}

		// The API accepts max 30 URLs per request, so split the URLs into chunks of 30.
		$chunks = array_chunk( $urls, 30 );
		foreach ( $chunks as $chunk ) {
			$result = Utils::get_api()->cloudflare->purge_urls( $chunk );

			// Exit early.
			if ( is_wp_error( $result ) ) {
				return;
			}
		}
	}

	/**
	 * Initialize Cloudflare APO.
	 *
	 * @since 3.0.0
	 */
	public function init_apo() {
		if ( headers_sent() ) {
			return;
		}

		$header = is_user_logged_in() ? 'no-cache' : 'cache, platform=WordPress';
		header( 'cf-edge-cache: ' . $header );
	}

	/**
	 * Parse post status transitions.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $new_status  New post status.
	 * @param string   $old_status  Old post status.
	 * @param \WP_Post $post        Post object.
	 */
	public function post_status_change( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$this->clear_post_cache( $post->ID );
		}
	}

	/**
	 * Parse comment status transitions.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $new_status  New comment status.
	 * @param string   $old_status  Old comment status.
	 * @param \WP_Post $comment     Comment object.
	 */
	public function comment_status_change( $new_status, $old_status, $comment ) {
		if ( ! isset( $comment->comment_post_ID ) || empty( $comment->comment_post_ID ) ) {
			return;
		}

		if ( $old_status !== $new_status && ( 'approved' === $old_status || 'approved' === $new_status ) ) {
			$this->clear_post_cache( $comment->comment_post_ID );
		}
	}

	/**
	 * Clear cache for a specific page, when a comment is posted.
	 *
	 * @since 3.0.0
	 *
	 * @param int        $comment_id        The comment ID.
	 * @param int|string $comment_approved  1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param array      $commentdata       Comment data.
	 */
	public function clear_on_comment_post( $comment_id, $comment_approved, $commentdata ) {
		// Comment hasn't been approved, so it won't appear on the page just yet - no need to clear the cache.
		if ( 1 !== $comment_approved ) {
			return;
		}

		// Post ID is not set, nothing to clear - return.
		if ( ! isset( $commentdata['comment_post_ID'] ) || 0 === $commentdata['comment_post_ID'] ) {
			return;
		}

		$this->clear_post_cache( $commentdata['comment_post_ID'] );
	}

	/**
	 * Get URL's associated with a specific post.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	private function get_urls_for_post( $post_id ) {
		$urls = array();

		$post_type = get_post_type( $post_id );

		// Purge taxonomies terms and feeds URLs.
		$taxonomies = get_object_taxonomies( $post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$link = get_term_link( $term );
				$feed = get_term_feed_link( $term->term_id, $term->taxonomy );
				if ( ! is_wp_error( $link ) && ! is_wp_error( $feed ) ) {
					array_push( $urls, $link );
					array_push( $urls, $feed );
				}
			}
		}

		// Author URL.
		array_push(
			$urls,
			get_author_posts_url( get_post_field( 'post_author', $post_id ) ),
			get_author_feed_link( get_post_field( 'post_author', $post_id ) )
		);

		// Archives and their feeds.
		if ( false !== get_post_type_archive_link( $post_type ) ) {
			array_push(
				$urls,
				get_post_type_archive_link( $post_type ),
				get_post_type_archive_feed_link( $post_type )
			);
		}

		// Post URL.
		array_push( $urls, get_permalink( $post_id ) );

		// Also clean URL for trashed post.
		if ( 'trash' === get_post_status( $post_id ) ) {
			$trash_post = get_permalink( $post_id );
			$trash_post = str_replace( '__trashed', '', $trash_post );
			array_push( $urls, $trash_post, $trash_post . 'feed/' );
		}

		// Feeds.
		array_push(
			$urls,
			get_bloginfo_rss( 'rdf_url' ),
			get_bloginfo_rss( 'rss_url' ),
			get_bloginfo_rss( 'rss2_url' ),
			get_bloginfo_rss( 'atom_url' ),
			get_bloginfo_rss( 'comments_rss2_url' ),
			get_post_comments_feed_link( $post_id )
		);

		// Home Page and (if used) posts page.
		array_push( $urls, home_url( '/' ) );
		$page_link = get_permalink( get_option( 'page_for_posts' ) );
		if ( is_string( $page_link ) && ! empty( $page_link ) && 'page' === get_option( 'show_on_front' ) ) {
			array_push( $urls, $page_link );
		}

		// Refresh pagination.
		$total_posts_count = wp_count_posts()->publish;
		$posts_per_page    = get_option( 'posts_per_page' );
		// Limit to up to 3 pages.
		$page_number_max = min( 3, ceil( $total_posts_count / $posts_per_page ) );

		foreach ( range( 1, $page_number_max ) as $page_number ) {
			array_push( $urls, home_url( sprintf( '/page/%s/', $page_number ) ) );
		}

		// Attachments.
		if ( 'attachment' === $post_type ) {
			$attachment_urls = array();
			foreach ( get_intermediate_image_sizes() as $size ) {
				$attachment_src    = wp_get_attachment_image_src( $post_id, $size );
				$attachment_urls[] = $attachment_src[0];
			}
			$urls = array_merge(
				$urls,
				array_unique( array_filter( $attachment_urls ) )
			);
		}

		// Purge https and http URLs.
		if ( function_exists( 'force_ssl_admin' ) && force_ssl_admin() ) {
			$urls = array_merge( $urls, str_replace( 'https://', 'http://', $urls ) );
		} elseif ( ! is_ssl() && function_exists( 'force_ssl_content' ) && force_ssl_content() ) {
			$urls = array_merge( $urls, str_replace( 'http://', 'https://', $urls ) );
		}

		return $urls;
	}

}
