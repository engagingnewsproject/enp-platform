<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

function defender_init_routes() {
	$routes = [
		'mask_login'       => [
			'update_settings' => 'save_settings',
			'get_posts'         => 'get_posts',
		],
		'security_headers' => [
			'update_settings' => 'save_settings'
		],
		'two_fa'           => [
			'update_settings'         => 'save_settings',
			'send_test_email'         => 'send_test_email',
			'verify_otp_for_enabling' => 'verify_otp_for_enabling',
			'disable_totp'            => 'disable_totp',
			'send_backup_code'        => [ 'send_backup_code', true ],
			'generate_backup_codes'   => 'generate_backup_codes',
		],
		'security_tweaks'   => [
			'process'                   => 'process',
			'ignore'                    => 'ignore',
			'revert'                    => 'revert',
			'restore'                   => 'restore',
			'recheck'                   => 'recheck',
			'bulk'                      => 'bulk_hub',
			'update_security_reminder'  => 'update_security_reminder',
			'update_autogenerate_flag'  => 'update_autogenerate_flag',
			'update_enabled_user_enums' => 'update_enabled_user_enums',
		],
		'ip_lockout'       => [
			'update_settings'      => 'save_settings',
			'download_geo_db'      => 'download_geo_db',
			'import_ips'           => 'import_ips',
			'query_locked_ips'     => 'query_locked_ips',
			'ip_action'            => 'ip_action',
			'export_ips'           => 'export_ips',
			'empty_logs'           => 'empty_logs',
			'dashboard_activation' => 'dashboard_activation',
			'import_ua'            => 'import_ua',
			'export_ua'            => 'export_ua',
		],
		'scan'             => [
			'start'           => 'start',
			'cancel'          => 'cancel',
			'process'         => [ 'process', true ],
			'status'          => 'status',
			'item_hub'        => 'item_hub',
			'update_settings' => 'save_settings',
			'bulk_hub'        => 'bulk_hub'
		],
		'audit'            => [
			'update_settings' => 'save_settings',
			'pull_logs'       => 'pull_logs',
			'summary'         => 'summary',
			'export_as_csv'   => 'export_as_csv'
		],
		'notification'     => [
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
		],
		'dashboard'        => [
			'hide_new_features' => 'hide_new_features',
		],
		'settings'         => [
			'update_settings' => 'save_settings',
			'reset_settings'  => 'reset_settings'
		],
		'waf'              => [
			'recheck' => 'recheck'
		],
		'onboard'  => [
			'activating' => 'activating',
			'skip'       => 'skip',
		],
		'tutorial' => [
			'hide' => 'hide'
		],
		'blocklist_monitor'        => [
			'blacklist_status'        => 'blacklist_status',
			'toggle_blacklist_status' => 'toggle_blacklist_status',
		],
	];
	foreach ( $routes as $module => $info ) {
		foreach ( $info as $name => $func ) {
			$nopriv = false;
			if ( is_array( $func ) ) {
				list( $func, $nopriv ) = $func;
			}
			\Calotes\Helper\Route::register_route( $name, $module, $name, [
				\Calotes\Helper\Array_Cache::get( $module ),
				$func
			], $nopriv );
		}
	}
}

defender_init_routes();
