<?php
// Exit if uninstall is not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	// php 5.2 does not need uninstall.
	return;
}

/**
 * Drop custom tables.
 *
 * @since 2.4
 */
function defender_drop_custom_tables() {
	global $wpdb;

	$wpdb->hide_errors();

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_email_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_scan_item" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_scan" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_lockout_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_lockout" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_audit_log" );
}

include_once __DIR__ . DIRECTORY_SEPARATOR . 'wp-defender.php';
$settings           = wd_di()->get( \WP_Defender\Model\Setting\Main_Setting::class );
$uninstall_data     = isset( $settings->uninstall_data ) && 'remove' === $settings->uninstall_data;
$uninstall_settings = isset( $settings->uninstall_settings ) && 'reset' === $settings->uninstall_settings;

if ( $uninstall_settings || $uninstall_data ) {
	// Turn off Audit_Logging so that hooks are not processed after deleting the table or resetting settings.
	$audit          = wd_di()->get( \WP_Defender\Model\Setting\Audit_Logging::class );
	$audit->enabled = false;
	$audit->save();
	$advanced_tools = wd_di()->get( \WP_Defender\Controller\Advanced_Tools::class );
}
// Only Settings.
if ( $uninstall_settings ) {
	$advanced_tools->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Firewall_Logs::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Login_Lockout::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Nf_Lockout::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Mask_Login::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Tutorial::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Blocklist_Monitor::class )->remove_settings();
	wd_di()->get( \WP_Defender\Controller\Main_Setting::class )->remove_settings();
	// Delete plugin options.
	delete_option( 'wp_defender' );
	delete_site_option( 'wp_defender' );
	delete_option( 'wd_db_version' );
	delete_site_option( 'wd_db_version' );
	delete_site_option( 'wpdefender_config_clear_active_tag' );
	delete_site_option( 'wpdefender_preset_configs_transient_time' );
	delete_site_option( 'wp_defender_config_default' );
	// Because not call remove_settings from WAF and Onboard controllers.
	delete_site_transient( 'def_waf_status' );
	delete_site_option( 'wp_defender_is_activated' );
}
// Only Data.
if ( $uninstall_data ) {
	wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Firewall_Logs::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Login_Lockout::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Nf_Lockout::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Mask_Login::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Tutorial::class )->remove_data();
	wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_data();
	wd_di()->get( \WP_Defender\Component\Backup_Settings::class )->clear_configs();
	$advanced_tools->remove_data();
	defender_drop_custom_tables();
}
// Complete cleaning.
if ( $uninstall_settings && $uninstall_data ) {
	delete_site_option( 'wd_nofresh_install' );
}
// Remains from old versions.
delete_site_option( 'wd_audit_cached' );
// Remove BF notice.
delete_site_option( 'wp_defender_show_black_friday' );