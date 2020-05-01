<?php
/**
 * Define the uninstall process
 *
 * Installs the DB
 *
 * @since      2.9.9
 */
class Visual_Form_Builder_Admin_Uninstall {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * uninstall function.
	 *
	 * @access public
	 * @param mixed $license_key
	 * @param mixed $license_email
	 * @return void
	 */
	public function uninstall() {
		$this->uninstall_data();
		$this->deactivate_plugin();
	}

	/**
	 * Deactivate VFB plugin.
	 *
	 * @access public
	 * @return void
	 */
	public function deactivate_plugin() {
		deactivate_plugins( 'visual-form-builder/visual-form-builder.php' );
		update_option(
			'recently_activated',
			array( $plugin => time() ) + (array) get_option( 'recently_activated' )
		);

		wp_redirect( admin_url( 'plugins.php' ) );
		exit();
	}

	/**
	 * Delete all tables and data.
	 *
	 * @access public
	 * @return void
	 */
	public function uninstall_data() {
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
	}
}
