<?php
/**
 * Gzip AJAX actions.
 *
 * @since 2.2.0
 * @package Hummingbird\Admin\Ajax
 */

namespace Hummingbird\Admin\Ajax;

use Hummingbird\Core\Module_Server;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gzip.
 */
class Gzip {

	/**
	 * Gzip constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wphb_react_gzip_status', array( $this, 'status' ) );
		add_action( 'wp_ajax_wphb_react_gzip_rules', array( $this, 'apply_rules' ) );
	}

	/**
	 * Fetch/refresh gzip status.
	 *
	 * @since 2.2.0
	 */
	public function status() {
		check_ajax_referer( 'wphb-fetch' );

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$params = json_decode( html_entity_decode( $params ), true );

		if ( 'refresh' === $params ) {
			wp_send_json_success( array( 'status' => Utils::get_module( 'gzip' )->get_analysis_data( true, true ) ) );
		}

		wp_send_json_success( array( 'status' => Utils::get_module( 'gzip' )->get_analysis_data() ) );
	}

	/**
	 * Add/remove Gzip .htaccess rules.
	 *
	 * @since 2.2.0
	 */
	public function apply_rules() {
		check_ajax_referer( 'wphb-fetch' );

		$params = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$params = json_decode( html_entity_decode( $params ), true );

		if ( 'add' === $params ) {
			Module_Server::save_htaccess( 'gzip' );
			wp_send_json_success(
				array(
					'status'           => Utils::get_module( 'gzip' )->get_analysis_data( true, true ),
					'htaccess_written' => Module_Server::is_htaccess_written( 'gzip' ),
				)
			);
		}

		if ( 'remove' === $params ) {
			Module_Server::unsave_htaccess( 'gzip' );
			wp_send_json_success(
				array(
					'status'           => Utils::get_module( 'gzip' )->get_analysis_data( true, true ),
					'htaccess_written' => Module_Server::is_htaccess_written( 'gzip' ),
				)
			);
		}

		wp_send_json_error( __( 'Error updating .htaccess file', 'wphb' ), 500 );
	}

}
