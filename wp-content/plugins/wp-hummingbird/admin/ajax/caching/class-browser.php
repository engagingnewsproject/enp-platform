<?php
/**
 * Browser Caching AJAX actions.
 *
 * @since 2.7.2
 * @package Hummingbird\Admin\Ajax\Caching
 */

namespace Hummingbird\Admin\Ajax\Caching;

use Hummingbird\Core\Module_Server;
use Hummingbird\Core\Settings;
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
			'update_expiry',
			'clear_cache',
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

		// Current expiry value settings (user selected, not actual server settings).
		$options = Settings::get_settings( 'caching' );
		$expires = array(
			'CSS'        => $options['expiry_css'],
			'JavaScript' => $options['expiry_javascript'],
			'Media'      => $options['expiry_media'],
			'Images'     => $options['expiry_images'],
		);

		wp_send_json_success(
			array(
				'status'           => $status,
				'expires'          => $expires,
				'human'            => array_map( array( 'Hummingbird\\Core\\Utils', 'human_read_time_diff' ), $status ),
				'detectedServer'   => Module_Server::get_server_type(),
				'usingCloudflare'  => Utils::get_module( 'cloudflare' )->has_cloudflare(),
				'cloudflareAuthed' => Utils::get_module( 'cloudflare' )->is_connected(),
				'cloudflareSetUp'  => $cloudflare,
				'cloudflareNotice' => get_site_option( 'wphb-cloudflare-dash-notice' ),
			)
		);
	}

	/**
	 * Update expiry rules.
	 *
	 * @since 3.2.0
	 */
	public function update_expiry() {
		check_ajax_referer( 'wphb-fetch' );

		$data = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$data = json_decode( html_entity_decode( $data ), true );

		$expiry_times = array(
			'expiry_javascript' => isset( $data['expires']['JavaScript'] ) ? sanitize_text_field( $data['expires']['JavaScript'] ) : '1y/A31536000',
			'expiry_css'        => isset( $data['expires']['CSS'] ) ? sanitize_text_field( $data['expires']['CSS'] ) : '1y/A31536000',
			'expiry_media'      => isset( $data['expires']['Media'] ) ? sanitize_text_field( $data['expires']['Media'] ) : '1y/A31536000',
			'expiry_images'     => isset( $data['expires']['Images'] ) ? sanitize_text_field( $data['expires']['Images'] ) : '1y/A31536000',
		);

		$options = Utils::get_module( 'caching' )->get_options();

		// Do not update our database values if we're adjusting for Cloudflare, because the format is different.
		if ( isset( $data['server'] ) && 'cloudflare' === $data['server'] ) {
			$expiry_times = $options;
		} else {
			$options = wp_parse_args( $expiry_times, $options );
			Utils::get_module( 'caching' )->update_options( $options );
		}

		/**
		 * Pass in caching type and value into a custom function.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args {
		 *     Array of selected type and value.
		 *
		 *     @type string $type                   Type of cached data, can be one of following:
		 *                                          `javascript`, `css`, `media` or `images`.
		 *     @type array  $sanitized_expiry_times Set expiry values (for example, 1h/A3600), first part can be:
		 *                                          `[n]h` for [n] hours (for example, 1h, 4h, 11h, etc),
		 *                                          `[n]d` for [n] days (for example, 1d, 4d, 11d, etc),
		 *                                          `[n]M` for [n] months (for example, 1M, 4M, 11M, etc),
		 *                                          `[n]y` for [n] years (for example, 1y, 4y, 11y, etc),
		 *                                          second part is the first part in seconds ( 1 hour = 3600 sec).
		 * }
		 */
		do_action(
			'wphb_caching_set_expiration',
			array(
				'expiry_times' => $expiry_times,
			)
		);

		$response = array(
			'snippets' => array(
				'apache' => Module_Server::get_code_snippet( 'caching', 'apache' ),
				'nginx'  => Module_Server::get_code_snippet( 'caching', 'nginx' ),
				'iis'    => Module_Server::get_code_snippet( 'caching', 'iis' ),
			),
		);

		if ( isset( $data['server'] ) && in_array( $data['server'], array( 'apache', 'cloudflare' ), true ) ) {
			if ( 'apache' === $data['server'] ) {
				Module_Server::unsave_htaccess( 'caching' );
				$response['htaccessUpdated'] = Module_Server::save_htaccess( 'caching' );
			}

			if ( 'cloudflare' === $data['server'] ) {
				$updated = Utils::get_module( 'cloudflare' )->set_caching_expiration( $data['expires']['CSS'] );

				$response['htaccessUpdated'] = ! is_wp_error( $updated );
			}

			$response['status'] = Utils::get_module( 'caching' )->get_analysis_data( true, true );
			$response['human']  = array_map( array( 'Hummingbird\\Core\\Utils', 'human_read_time_diff' ), $response['status'] );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Clear Cloudflare cache.
	 *
	 * @since 3.2.0
	 */
	public function clear_cache() {
		check_ajax_referer( 'wphb-fetch' );
		Utils::get_module( 'cloudflare' )->clear_cache();
		wp_send_json_success();
	}

}