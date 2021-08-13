<?php
/**
 * Integrations AJAX actions.
 *
 * @since 3.0.0
 * @package Hummingbird\Admin\Ajax\Caching
 */

namespace Hummingbird\Admin\Ajax\Caching;

use Hummingbird\Core\Modules\Cloudflare;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Integrations.
 */
class Integrations {

	/**
	 * Integrations constructor.
	 */
	public function __construct() {
		$endpoints = array(
			'cloudflare_status',
			'cloudflare_apo_status',
			'cloudflare_disconnect',
			'cloudflare_zones',
			'cloudflare_clear_cache',
			'cloudflare_save_zone',
			'cloudflare_toggle_apo',
			'cloudflare_toggle_device_cache',
		);

		foreach ( $endpoints as $endpoint ) {
			add_action( "wp_ajax_wphb_react_{$endpoint}", array( $this, $endpoint ) );
		}
	}

	/**
	 * Get Cloudflare status.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_status() {
		$options     = Utils::get_module( 'cloudflare' )->get_options();
		$expiry      = Utils::get_module( 'cloudflare' )->get_caching_expiration();
		$frequencies = Cloudflare::get_frequencies();

		wp_send_json_success(
			array(
				'cloudflare' => array(
					'accountId' => $options['account_id'],
					'connected' => Utils::get_module( 'cloudflare' )->is_connected(),
					'dnsSet'    => Utils::get_module( 'cloudflare' )->has_cloudflare(),
					'expiry'    => $expiry,
					'human'     => $frequencies[ $expiry ],
					'zone'      => $options['zone'],
					'zoneName'  => $options['zone_name'],
				),
				'apo'        => array(
					'enabled'   => Utils::get_module( 'cloudflare' )->is_apo_enabled(),
					'purchased' => $options['apo_paid'],
					'settings'  => $options['apo'],
				),
			)
		);
	}

	/**
	 * Check Cloudflare APO status.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_apo_status() {
		check_ajax_referer( 'wphb-fetch' );

		Utils::get_module( 'cloudflare' )->get_apo_settings();
		$this->cloudflare_status();

		wp_send_json_success();
	}

	/**
	 * Disconnect Cloudflare.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_disconnect() {
		check_ajax_referer( 'wphb-fetch' );

		Utils::get_module( 'cloudflare' )->disconnect();

		$this->cloudflare_status();
	}

	/**
	 * Check Cloudflare zones.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_zones() {
		check_ajax_referer( 'wphb-fetch' );

		$zones = Utils::get_module( 'cloudflare' )->get_zones_list();

		// This will end processing if zones are an issue.
		Utils::get_module( 'cloudflare' )->validate_zones( $zones );

		$status = Utils::get_module( 'cloudflare' )->process_zones( $zones );

		// Could not match a zone.
		if ( ! $status ) {
			wp_send_json_success( array( 'zones' => $zones ) );
		}

		wp_send_json_success();
	}

	/**
	 * Clear Cloudflare cache.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_clear_cache() {
		check_ajax_referer( 'wphb-fetch' );

		Utils::get_module( 'cloudflare' )->clear_cache();

		wp_send_json_success();
	}

	/**
	 * Save selected Cloudflare zone.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_save_zone() {
		check_ajax_referer( 'wphb-fetch' );

		$zone = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$zone = json_decode( html_entity_decode( $zone ), true );

		$zones = Utils::get_module( 'cloudflare' )->get_zones_list();

		// This will end processing if zones are an issue.
		Utils::get_module( 'cloudflare' )->validate_zones( $zones );

		$options = Utils::get_module( 'cloudflare' )->get_options();

		// Set the module as enabled.
		if ( ! $options['enabled'] ) {
			$options['enabled'] = true;
			Utils::get_module( 'cloudflare' )->update_options( $options );
		}

		Utils::get_module( 'cloudflare' )->process_zones( $zones, $zone );

		$this->cloudflare_status();
	}

	/**
	 * Toggle Cloudflare APO.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_toggle_apo() {
		check_ajax_referer( 'wphb-fetch' );

		$status = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$status = json_decode( html_entity_decode( $status ), true );

		Utils::get_module( 'cloudflare' )->toggle_apo( $status );

		$options = Utils::get_module( 'cloudflare' )->get_options();

		wp_send_json_success(
			array(
				'enabled'   => Utils::get_module( 'cloudflare' )->is_apo_enabled(),
				'purchased' => $options['apo_paid'],
				'settings'  => $options['apo'],
			)
		);
	}

	/**
	 * Toggle Cloudflare APO cache by device type setting.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_toggle_device_cache() {
		check_ajax_referer( 'wphb-fetch' );

		$status = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$status = json_decode( html_entity_decode( $status ), true );

		Utils::get_module( 'cloudflare' )->toggle_cache_by_device( $status );

		$options = Utils::get_module( 'cloudflare' )->get_options();

		wp_send_json_success(
			array(
				'enabled'   => Utils::get_module( 'cloudflare' )->is_apo_enabled(),
				'purchased' => $options['apo_paid'],
				'settings'  => $options['apo'],
			)
		);
	}

}
