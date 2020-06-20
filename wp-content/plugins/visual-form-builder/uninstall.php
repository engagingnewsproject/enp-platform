<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

global $wpdb;

$form_table 	= $wpdb->prefix . 'visual_form_builder_fields';
$fields_table 	= $wpdb->prefix . 'visual_form_builder_forms';
$entries_table 	= $wpdb->prefix . 'visual_form_builder_entries';

$wpdb->query( "DROP TABLE IF EXISTS $form_table" );
$wpdb->query( "DROP TABLE IF EXISTS $fields_table" );
$wpdb->query( "DROP TABLE IF EXISTS $entries_table" );

delete_option( 'vfb_db_version' );
delete_option( 'visual-form-builder-screen-options' );
delete_option( 'vfb_dashboard_widget_options' );
delete_option( 'vfb-settings' );

$wpdb->query( "DELETE FROM " . $wpdb->prefix . "usermeta WHERE meta_key IN ( 'vfb-form-settings', 'vfb_entries_per_page', 'vfb_forms_per_page', 'managevisual-form-builder_page_vfb-entriescolumnshidden' )" );
