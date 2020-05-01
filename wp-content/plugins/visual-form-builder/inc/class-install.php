<?php

class Visual_Form_Builder_Install {
	/**
	 * Initial setup
	 */
	public function __construct() {
	}

	/**
	 * Check DB version and run SQL install, if needed
	 * @return [type] [description]
	 */
	public function upgrade_db_check() {
		$current_db_version = VFB_WP_DB_VERSION;

		if ( get_option( 'vfb_db_version' ) != $current_db_version )
			$this->install_db();
	}

	public function install_db() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Forms table
		$sql = "CREATE TABLE " . VFB_WP_FORMS_TABLE_NAME . " (
			form_id BIGINT(20) NOT NULL AUTO_INCREMENT,
			form_key TINYTEXT NOT NULL,
			form_title TEXT NOT NULL,
			form_email_subject TEXT,
			form_email_to TEXT,
			form_email_from VARCHAR(255),
			form_email_from_name VARCHAR(255),
			form_email_from_override VARCHAR(255),
			form_email_from_name_override VARCHAR(255),
			form_success_type VARCHAR(25) DEFAULT 'text',
			form_success_message TEXT,
			form_notification_setting VARCHAR(25),
			form_notification_email_name VARCHAR(255),
			form_notification_email_from VARCHAR(255),
			form_notification_email VARCHAR(25),
			form_notification_subject VARCHAR(255),
			form_notification_message TEXT,
			form_notification_entry VARCHAR(25),
			form_label_alignment VARCHAR(25),
			PRIMARY KEY  (form_id)
		) $charset_collate;";

		dbDelta( $sql );

		// Fields table
		$sql = "CREATE TABLE " . VFB_WP_FIELDS_TABLE_NAME . " (
			field_id BIGINT(20) NOT NULL AUTO_INCREMENT,
			form_id BIGINT(20) NOT NULL,
			field_key VARCHAR(255) NOT NULL,
			field_type VARCHAR(25) NOT NULL,
			field_options TEXT,
			field_description TEXT,
			field_name TEXT NOT NULL,
			field_sequence BIGINT(20) DEFAULT '0',
			field_parent BIGINT(20) DEFAULT '0',
			field_validation VARCHAR(25),
			field_required VARCHAR(25),
			field_size VARCHAR(25) DEFAULT 'medium',
			field_css VARCHAR(255),
			field_layout VARCHAR(255),
			field_default TEXT,
			PRIMARY KEY  (field_id)
		) $charset_collate;";

		dbDelta( $sql );

		// Entries table
		$sql = "CREATE TABLE " . VFB_WP_ENTRIES_TABLE_NAME . " (
			entries_id BIGINT(20) NOT NULL AUTO_INCREMENT,
			form_id BIGINT(20) NOT NULL,
			data LONGTEXT NOT NULL,
			subject TEXT,
			sender_name VARCHAR(255),
			sender_email VARCHAR(255),
			emails_to TEXT,
			date_submitted DATETIME,
			ip_address VARCHAR(50),
			entry_approved VARCHAR(20) DEFAULT '1',
			PRIMARY KEY  (entries_id)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( 'vfb_db_version', VFB_WP_DB_VERSION );
	}

	/**
	 * A wrapper to check DB version which then calls install_db
	 * @return [type] [description]
	 */
	public function install() {
		$this->upgrade_db_check();
	}
}
