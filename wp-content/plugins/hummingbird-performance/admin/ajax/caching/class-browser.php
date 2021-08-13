<?php
/**
 * Browser Caching AJAX actions.
 *
 * @since 2.7.2
 * @package Hummingbird\Admin\Ajax\Caching
 */

namespace Hummingbird\Admin\Ajax\Caching;

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Browser.
 */
class Browser {

	/**
	 * Browser constructor.
	 */
	public function __construct() {
		$endpoints = array(
			'browser_caching_status',
		);

		foreach ( $endpoints as $endpoint ) {
			add_action( "wp_ajax_wphb_react_{$endpoint}", array( $this, $endpoint ) );
		}
	}

	/**
	 * Fetch/refresh browser caching status.
	 *
	 * @since 2.7.2
	 */
	public function browser_caching_status() {
		check_ajax_referer( 'wphb-fetch' );

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$params = json_decode( html_entity_decode( $params ), true );

		$force  = 'refresh' === $params;
		$status = Utils::get_module( 'caching' )->get_analysis_data( $force, $force );

		$cloudflare = Utils::get_module( 'cloudflare' )->is_connected() && Utils::get_module( 'cloudflare' )->is_zone_selected();
		if ( $cloudflare ) {
			// Fill the report with values from Cloudflare.
			$status = array_fill_keys( array_keys( $status ), Utils::get_module( 'cloudflare' )->get_caching_expiration( true ) );
		}

		wp_send_json_success(
			array(
				'status'           => $status,
				'human'            => array_map( array( 'Hummingbird\\Core\\Utils', 'human_read_time_diff' ), $status ),
				'usingCloudflare'  => Utils::get_module( 'cloudflare' )->has_cloudflare(),
				'cloudflareAuthed' => Utils::get_module( 'cloudflare' )->is_connected(),
				'cloudflareSetUp'  => $cloudflare,
				'cloudflareNotice' => get_site_option( 'wphb-cloudflare-dash-notice' ),
			)
		);
	}

}
