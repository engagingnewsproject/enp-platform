<?php
// Exit if uninstall is not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * Drop custom tables.
 *
 * @since 2.4
 */
function defender_drop_custom_tables() {
	global $wpdb;

	$wpdb->hide_errors();

	defender_drop_custom_fk_constraint( $wpdb->prefix . 'defender_quarantine' );

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_email_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_scan_item" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_scan" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_lockout_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_lockout" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_audit_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_unlockout" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_antibot" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}defender_quarantine" );
}

/**
 * Drop custom tables.
 *
 * @since 4.0.0
 */
function defender_drop_custom_fk_constraint( string $table_name ): void {
	global $wpdb;

	$results = $wpdb->get_results(
		"SELECT CONSTRAINT_NAME
		FROM information_schema.TABLE_CONSTRAINTS
		WHERE CONSTRAINT_SCHEMA = '{$wpdb->dbname}'
		AND CONSTRAINT_TYPE = 'FOREIGN KEY'
		AND TABLE_NAME = '{$table_name}'"
	);

	if ( $results ) {
		foreach ( $results as $fk ) {
			$wpdb->query( "ALTER TABLE {$table_name} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'wp-defender.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'extra/hub-connector/connector.php';
\WPMUDEV\Hub\Connector::get();
$settings           = wd_di()->get( \WP_Defender\Model\Setting\Main_Setting::class );
$uninstall_data     = isset( $settings->uninstall_data ) && 'remove' === $settings->uninstall_data;
$uninstall_settings = isset( $settings->uninstall_settings ) && 'reset' === $settings->uninstall_settings;

if ( $uninstall_settings || $uninstall_data ) {
	// Turn off Audit_Logging so that hooks are not processed after deleting the table or resetting settings.
	$audit          = wd_di()->get( \WP_Defender\Model\Setting\Audit_Logging::class );
	$audit->enabled = false;
	$audit->save();
	$advanced_tools = wd_di()->get( \WP_Defender\Controller\Advanced_Tools::class );

	// Only Settings.
	if ( $uninstall_settings ) {
		// Remove all settings of Advanced Tools.
		$advanced_tools->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_settings();
		// Start of Firewall parent and submodules.
		wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_settings();
		// End.
		wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Blocklist_Monitor::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Main_Setting::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Onboard::class )->remove_settings();
		// Delete plugin options.
		delete_option( 'wp_defender' );
		delete_site_option( 'wp_defender' );
		delete_option( 'wd_db_version' );
		delete_site_option( 'wd_db_version' );
		delete_site_option( 'wpdefender_config_clear_active_tag' );
		delete_site_option( 'wpdefender_preset_configs_transient_time' );
		delete_site_option( 'wp_defender_config_default' );
		delete_site_option( 'disable-xml-rpc' );
		// Because not call remove_settings from WAF and Onboard controllers.
		delete_site_transient( 'def_waf_status' );
		delete_site_option( 'wp_defender_is_activated' );
		delete_site_transient( \WP_Defender\Component\Blacklist_Lockout::IP_LIST_KEY );
		delete_site_transient( 'defender_run_background' );
	}
	// Only Data.
	if ( $uninstall_data ) {
		wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_data();
		wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_data();
		wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_data();
		wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_data();
		// Remove all data of Firewall.
		wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_data();
		wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_data();
		wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_data();
		wd_di()->get( \WP_Defender\Component\Backup_Settings::class )->clear_configs();
		// Remove all data of Advanced Tools.
		$advanced_tools->remove_data();
		defender_drop_custom_tables();
		wd_di()->get( \WP_Defender\Component\Network_Cron_Manager::class )->remove_data();
	}
}

if ( class_exists( 'WP_Defender\Controller\Quarantine' ) ) {
	// Quarantine.
	// All decision making logic added inside the quarantine component core method on_uninstall.
	wd_di()->get( \WP_Defender\Controller\Quarantine::class )->remove_data();
}

// Complete cleaning.
if ( $uninstall_settings && $uninstall_data ) {
	delete_site_option( 'wd_nofresh_install' );
	\WP_Defender\Component\Feature_Modal::delete_modal_key();
	\WP_Defender\Controller\Data_Tracking::delete_modal_key();
	wd_di()->get( \WP_Defender\Controller\Rate::class )->remove_data();
	\WP_Defender\Component\Firewall::delete_slugs();
}
// Remains from old versions.
delete_site_option( 'wd_audit_cached' );
delete_site_option( 'wd_show_ip_detection_notice' );