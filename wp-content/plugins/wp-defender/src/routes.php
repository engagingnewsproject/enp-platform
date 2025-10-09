<?php
/**
 * This file contains the routes for the plugin.
 *
 * @package WP_Defender
 */

use Calotes\Helper\Route;
use Calotes\Helper\Array_Cache;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Initializes the routes for the plugin.
 *
 * @return void
 */
function defender_init_routes() {
	$routes = array(
		'mask_login'        => array(
			'update_settings' => 'save_settings',
			'get_posts'       => 'get_posts',
		),
		'security_headers'  => array(
			'update_settings' => 'save_settings',
		),
		'two_fa'            => array(
			'update_settings'         => 'save_settings',
			'send_test_email'         => 'send_test_email',
			'verify_otp_for_enabling' => 'verify_otp_for_enabling',
			'disable_totp'            => 'disable_totp',
			'send_backup_code'        => array( 'send_backup_code', true ),
			'generate_backup_codes'   => 'generate_backup_codes',
		),
		'security_tweaks'   => array(
			'process'                   => 'process',
			'ignore'                    => 'ignore',
			'revert'                    => 'revert',
			'restore'                   => 'restore',
			'recheck'                   => 'recheck',
			'bulk_action'               => 'bulk_action',
			'update_security_reminder'  => 'update_security_reminder',
			'update_autogenerate_flag'  => 'update_autogenerate_flag',
			'update_enabled_user_enums' => 'update_enabled_user_enums',
			'check_xml_rpc'             => 'check_xml_rpc',
		),
		'ip_lockout'        => array(
			'update_settings'      => 'save_settings',
			'download_geo_db'      => 'download_geo_db',
			'import_ips'           => 'import_ips',
			'get_listed_ips'       => 'get_listed_ips',
			'query_locked_ips'     => 'query_locked_ips',
			'ip_action'            => 'ip_action',
			'export_ips'           => 'export_ips',
			'empty_logs'           => 'empty_logs',
			'dashboard_activation' => 'dashboard_activation',
			'import_ua'            => 'import_ua',
			'export_ua'            => 'export_ua',
			'empty_lockouts'       => 'empty_lockouts',
			'verify_blocked_user'  => array( 'verify_blocked_user', true ),
			'send_again'           => array( 'send_again', true ),
			'agf_unlock_user'      => array( 'agf_unlock_user', true ),
		),
		'global_ip_lockout' => array(
			'refresh_global_ip_list'  => 'refresh_global_ip_list',
			'redirect_hub_connection' => 'redirect_hub_connection',
		),
		'scan'              => array(
			'start'           => 'start',
			'cancel'          => 'cancel',
			'process'         => array( 'process', true ),
			'status'          => 'status',
			'item_action'     => 'item_action',
			'update_settings' => 'save_settings',
			'bulk_action'     => 'bulk_action',
		),
		'audit'             => array(
			'update_settings' => 'save_settings',
			'pull_logs'       => 'pull_logs',
			'summary'         => 'summary',
			'export_as_csv'   => 'export_as_csv',
		),
		'notification'      => array(
			'get_users'         => 'get_users',
			'save_notification' => 'save_notification',
			'unscubscribe'      => 'unsubscribe',
			'subscribe'         => 'subscribe',
			'save_frequency'    => 'save_frequency',
			'save_subscriber'   => 'save_subscriber',
			'save_configs'      => 'save_configs',
			'save_bulk_configs' => 'save_bulk_configs',
			'bulk_deactivate'   => 'bulk_deactivate',
			'validate_email'    => 'validate_email',
		),
		'dashboard'         => array(
			'hide_new_features'                => 'hide_new_features',
			'activate_global_ip'               => 'activate_global_ip',
			'remove_global_ip_notice_reminder' => 'remove_global_ip_notice_reminder',
			'activate_session_protection'      => 'activate_session_protection',
		),
		'settings'          => array(
			'update_settings' => 'save_settings',
			'reset_settings'  => 'reset_settings',
		),
		'waf'               => array(
			'recheck' => 'recheck',
		),
		'onboard'           => array(
			'activating'       => 'activating',
			'skip'             => 'skip',
			'antibot_reminder' => 'antibot_reminder',
		),
		'blocklist_monitor' => array(
			'blacklist_status'        => 'blacklist_status',
			'toggle_blacklist_status' => 'toggle_blacklist_status',
		),
		'tracking'          => array(
			'close_track_modal' => 'close_track_modal',
			'save_track_modal'  => 'save_track_modal',
		),
		'hub_connector'     => array(
			'activate_dashboard_plugin' => 'activate_dashboard_plugin',
		),
		'rate'              => array(
			'postpone_notice' => 'postpone_notice',
			'refuse_notice'   => 'refuse_notice',
			'handle_notice'   => 'handle_notice',
		),
	);

	if ( class_exists( 'WP_Defender\Controller\Quarantine' ) ) {
		$routes['quarantine'] = array(
			'restore_file'          => 'restore_file',
			'quarantine_collection' => 'quarantine_collection',
			'delete_file'           => 'delete_file',
		);
	}

	foreach ( $routes as $module => $info ) {
		foreach ( $info as $name => $func ) {
			$nopriv = false;
			if ( is_array( $func ) ) {
				[ $func, $nopriv ] = $func;
			}
			Route::register_route(
				$name,
				$module,
				$name,
				array(
					Array_Cache::get( $module ),
					$func,
				),
				$nopriv
			);
		}
	}
}

defender_init_routes();