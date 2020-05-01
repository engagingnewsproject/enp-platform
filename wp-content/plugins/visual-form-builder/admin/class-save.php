<?php
/**
 * [Visual_Form_Builder_Admin_Save description]
 */
class Visual_Form_Builder_Admin_Save {

	/**
	 * Hook our save functions to the admin
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_new_form' ) );
		add_action( 'admin_init', array( $this, 'save_update_form' ) );
		add_action( 'admin_init', array( $this, 'save_trash_delete_form' ) );
		add_action( 'admin_init', array( $this, 'save_copy_form' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
	}

	/**
	 * Add New form
	 */
	public function add_new_form() {
		global $wpdb;

		if ( !isset( $_POST['action'] ) || !isset( $_GET['page'] ) )
			return;

		if ( 'vfb-add-new' !== $_GET['page'] )
			return;

		if ( 'create_form' !== $_POST['action'] )
			return;

		if ( !current_user_can( 'manage_options' ) )
    		wp_die( __( 'You do not have sufficient permissions to create a new form.', 'visual-form-builder' ) );

		check_admin_referer( 'create_form' );

		$form_key 		= sanitize_title( $_POST['form_title'] );
		$form_title 	= esc_html( $_POST['form_title'] );
		$form_from_name = esc_html( $_POST['form_email_from_name'] );
		$form_subject 	= esc_html( $_POST['form_email_subject'] );
		$form_from 		= esc_html( $_POST['form_email_from'] );
		$form_to 		= serialize( $_POST['form_email_to'] );

		$newdata = array(
			'form_key' 				=> $form_key,
			'form_title' 			=> $form_title,
			'form_email_from_name'	=> $form_from_name,
			'form_email_subject'	=> $form_subject,
			'form_email_from'		=> $form_from,
			'form_email_to'			=> $form_to,
			'form_success_message'	=> '<p id="form_success">Your form was successfully submitted. Thank you for contacting us.</p>'
		);

		// Create the form
		$wpdb->insert( VFB_WP_FORMS_TABLE_NAME, $newdata );

		// Get form ID to add our first field
		$new_form_selected = $wpdb->insert_id;

		// Setup the initial fieldset
		$initial_fieldset = array(
			'form_id' 			=> $wpdb->insert_id,
			'field_key' 		=> 'fieldset',
			'field_type' 		=> 'fieldset',
			'field_name' 		=> 'Fieldset',
			'field_sequence' 	=> 0
		);

		// Add the first fieldset to get things started
		$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $initial_fieldset );

		$verification_fieldset = array(
			'form_id' 			=> $new_form_selected,
			'field_key' 		=> 'verification',
			'field_type' 		=> 'verification',
			'field_name' 		=> 'Verification',
			'field_description' => '(This is for preventing spam)',
			'field_sequence' 	=> 1
		);

		// Insert the submit field
		$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $verification_fieldset );

		$verify_fieldset_parent_id = $wpdb->insert_id;

		$secret = array(
			'form_id' 			=> $new_form_selected,
			'field_key' 		=> 'secret',
			'field_type' 		=> 'secret',
			'field_name' 		=> 'Please enter any two digits',
			'field_description'	=> 'Example: 12',
			'field_size' 		=> 'medium',
			'field_required' 	=> 'yes',
			'field_parent' 		=> $verify_fieldset_parent_id,
			'field_sequence' 	=> 2
		);

		// Insert the submit field
		$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $secret );

		// Make the submit last in the sequence
		$submit = array(
			'form_id' 			=> $new_form_selected,
			'field_key' 		=> 'submit',
			'field_type' 		=> 'submit',
			'field_name' 		=> 'Submit',
			'field_parent' 		=> $verify_fieldset_parent_id,
			'field_sequence' 	=> 3
		);

		// Insert the submit field
		$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $submit );

		// Redirect to keep the URL clean (use AJAX in the future?)
		wp_redirect( 'admin.php?page=visual-form-builder&action=edit&form=' . $new_form_selected );
		exit();
	}

	/**
	 * [save_update_form description]
	 * @return [type] [description]
	 */
	public function save_update_form() {
		global $wpdb;

		if ( !isset( $_POST['action'] ) || !isset( $_GET['page'] ) )
			return;

		if ( 'visual-form-builder' !== $_GET['page'] )
			return;

		if ( 'update_form' !== $_POST['action'] )
			return;

		check_admin_referer( 'vfb_update_form' );

		$form_id 						= absint( $_POST['form_id'] );
		$form_key 						= sanitize_title( $_POST['form_title'], $form_id );
		$form_title 					= $_POST['form_title'];
		$form_subject 					= $_POST['form_email_subject'];
		$form_to 						= serialize( array_map( 'sanitize_email', $_POST['form_email_to'] ) );
		$form_from 						= sanitize_email( $_POST['form_email_from'] );
		$form_from_name 				= $_POST['form_email_from_name'];
		$form_from_override 			= isset( $_POST['form_email_from_override'] ) ? $_POST['form_email_from_override'] : '';
		$form_from_name_override 		= isset( $_POST['form_email_from_name_override'] ) ? $_POST['form_email_from_name_override'] : '';
		$form_success_type 				= $_POST['form_success_type'];
		$form_notification_setting 		= isset( $_POST['form_notification_setting']    ) ? $_POST['form_notification_setting']                      : '';
		$form_notification_email_name 	= isset( $_POST['form_notification_email_name'] ) ? $_POST['form_notification_email_name']                   : '';
		$form_notification_email_from 	= isset( $_POST['form_notification_email_from'] ) ? sanitize_email( $_POST['form_notification_email_from'] ) : '';
		$form_notification_email 		= isset( $_POST['form_notification_email']      ) ? $_POST['form_notification_email']                        : '';
		$form_notification_subject 		= isset( $_POST['form_notification_subject']    ) ? $_POST['form_notification_subject']                      : '';
		$form_notification_message 		= isset( $_POST['form_notification_message']    ) ? format_for_editor( $_POST['form_notification_message'] )   : '';
		$form_notification_entry 		= isset( $_POST['form_notification_entry']      ) ? $_POST['form_notification_entry']                        : '';
		$form_label_alignment 			= $_POST['form_label_alignment'];

		// Add confirmation based on which type was selected
		switch ( $form_success_type ) {
			case 'text' :
				$form_success_message = format_for_editor( $_POST['form_success_message_text'] );
			break;
			case 'page' :
				$form_success_message = $_POST['form_success_message_page'];
			break;
			case 'redirect' :
				$form_success_message = $_POST['form_success_message_redirect'];
			break;
		}

		$newdata = array(
			'form_key' 						=> $form_key,
			'form_title' 					=> $form_title,
			'form_email_subject' 			=> $form_subject,
			'form_email_to' 				=> $form_to,
			'form_email_from' 				=> $form_from,
			'form_email_from_name' 			=> $form_from_name,
			'form_email_from_override' 		=> $form_from_override,
			'form_email_from_name_override' => $form_from_name_override,
			'form_success_type' 			=> $form_success_type,
			'form_success_message' 			=> $form_success_message,
			'form_notification_setting' 	=> $form_notification_setting,
			'form_notification_email_name' 	=> $form_notification_email_name,
			'form_notification_email_from' 	=> $form_notification_email_from,
			'form_notification_email' 		=> $form_notification_email,
			'form_notification_subject' 	=> $form_notification_subject,
			'form_notification_message' 	=> $form_notification_message,
			'form_notification_entry' 		=> $form_notification_entry,
			'form_label_alignment' 			=> $form_label_alignment
		);

		$where = array( 'form_id' => $form_id );

		// Update form details
		$wpdb->update( VFB_WP_FORMS_TABLE_NAME, $newdata, $where );

		$field_ids = array();

		foreach ( $_POST['field_id'] as $fields ) {
				$field_ids[] = $fields;
		}

		// Initialize field sequence
		$field_sequence = 0;

		// Loop through each field and update
		foreach ( $field_ids as $id ) {
			$id = absint( $id );

			$field_name 		= isset( $_POST['field_name-' . $id] ) ? trim( $_POST['field_name-' . $id] ) : '';
			$field_key 			= sanitize_key( sanitize_title( $field_name, $id ) );
			$field_desc 		= isset( $_POST['field_description-' . $id] ) ? trim( $_POST['field_description-' . $id] ) : '';
			$field_options 		= isset( $_POST['field_options-' . $id]     ) ? serialize( array_map( 'trim', $_POST['field_options-' . $id] ) ) : '';
			$field_validation 	= isset( $_POST['field_validation-' . $id]  ) ? $_POST['field_validation-' . $id]      : '';
			$field_required 	= isset( $_POST['field_required-' . $id]    ) ? $_POST['field_required-' . $id]        : '';
			$field_size 		= isset( $_POST['field_size-' . $id]        ) ? $_POST['field_size-' . $id]            : '';
			$field_css 			= isset( $_POST['field_css-' . $id]         ) ? $_POST['field_css-' . $id]             : '';
			$field_layout 		= isset( $_POST['field_layout-' . $id]      ) ? $_POST['field_layout-' . $id]          : '';
			$field_default 		= isset( $_POST['field_default-' . $id]     ) ? trim( $_POST['field_default-' . $id] ) : '';

			$field_data = array(
				'field_key' 		=> $field_key,
				'field_name' 		=> $field_name,
				'field_description' => $field_desc,
				'field_options'		=> $field_options,
				'field_validation' 	=> $field_validation,
				'field_required' 	=> $field_required,
				'field_size' 		=> $field_size,
				'field_css' 		=> $field_css,
				'field_layout' 		=> $field_layout,
				'field_sequence' 	=> $field_sequence,
				'field_default' 	=> $field_default
			);

			$where = array(
				'form_id' 	=> $form_id,
				'field_id' 	=> $id
			);

			// Update all fields
			$wpdb->update( VFB_WP_FIELDS_TABLE_NAME, $field_data, $where );

			$field_sequence++;
		}
	}

	/**
	 * [save_trash_delete_form description]
	 * @return [type] [description]
	 */
	public function save_trash_delete_form() {
		global $wpdb;

		if ( !isset( $_GET['action'] ) || !isset( $_GET['page'] ) )
			return;

		if ( 'visual-form-builder' !== $_GET['page'] )
			return;

		if ( 'delete_form' !== $_GET['action'] )
			return;

		$id = absint( $_GET['form'] );

		check_admin_referer( 'delete-form-' . $id );

		// Delete form and all fields
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE form_id = %d", $id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_FIELDS_TABLE_NAME . " WHERE form_id = %d", $id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE form_id = %d", $id ) );

		// Redirect to keep the URL clean (use AJAX in the future?)
		wp_redirect( add_query_arg( 'action', 'deleted', 'admin.php?page=visual-form-builder' ) );
		exit();
	}

	/**
	 * [save_copy_form description]
	 * @return [type] [description]
	 */
	public function save_copy_form() {
		global $wpdb;

		if ( !isset( $_GET['action'] ) || !isset( $_GET['page'] ) )
			return;

		if ( 'visual-form-builder' !== $_GET['page'] )
			return;

		if ( 'copy_form' !== $_GET['action'] )
			return;

		$id = absint( $_GET['form'] );

		check_admin_referer( 'copy-form-' . $id );

		// Get all fields and data for the request form
		$fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . VFB_WP_FIELDS_TABLE_NAME . " WHERE form_id = %d", $id ) );
		$forms     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE form_id = %d", $id ) );
		$override  = $wpdb->get_var( $wpdb->prepare( "SELECT form_email_from_override, form_email_from_name_override, form_notification_email FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE form_id = %d", $id ) );
		$from_name = $wpdb->get_var( null, 1 );
		$notify    = $wpdb->get_var( null, 2 );

		// Copy this form and force the initial title to denote a copy
		foreach ( $forms as $form ) {
			$data = array(
				'form_key'						=> sanitize_title( $form->form_key . ' copy' ),
				'form_title' 					=> $form->form_title . ' Copy',
				'form_email_subject' 			=> $form->form_email_subject,
				'form_email_to' 				=> $form->form_email_to,
				'form_email_from' 				=> $form->form_email_from,
				'form_email_from_name' 			=> $form->form_email_from_name,
				'form_email_from_override' 		=> $form->form_email_from_override,
				'form_email_from_name_override' => $form->form_email_from_name_override,
				'form_success_type' 			=> $form->form_success_type,
				'form_success_message' 			=> $form->form_success_message,
				'form_notification_setting' 	=> $form->form_notification_setting,
				'form_notification_email_name' 	=> $form->form_notification_email_name,
				'form_notification_email_from' 	=> $form->form_notification_email_from,
				'form_notification_email' 		=> $form->form_notification_email,
				'form_notification_subject' 	=> $form->form_notification_subject,
				'form_notification_message' 	=> $form->form_notification_message,
				'form_notification_entry' 		=> $form->form_notification_entry,
				'form_label_alignment' 			=> $form->form_label_alignment
			);

			$wpdb->insert( VFB_WP_FORMS_TABLE_NAME, $data );
		}

		// Get form ID to add our first field
		$new_form_selected = $wpdb->insert_id;

		// Copy each field and data
		foreach ( $fields as $field ) {
			$data = array(
				'form_id' 			=> $new_form_selected,
				'field_key' 		=> $field->field_key,
				'field_type' 		=> $field->field_type,
				'field_name' 		=> $field->field_name,
				'field_description' => $field->field_description,
				'field_options' 	=> $field->field_options,
				'field_sequence' 	=> $field->field_sequence,
				'field_validation' 	=> $field->field_validation,
				'field_required' 	=> $field->field_required,
				'field_size' 		=> $field->field_size,
				'field_css' 		=> $field->field_css,
				'field_layout' 		=> $field->field_layout,
				'field_parent' 		=> $field->field_parent
			);

			$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $data );

			// If a parent field, save the old ID and the new ID to update new parent ID
			if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) {
				$parents[ $field->field_id ] = $wpdb->insert_id;
			}

			if ( $override == $field->field_id ) {
				$wpdb->update( VFB_WP_FORMS_TABLE_NAME, array( 'form_email_from_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}


			if ( $from_name == $field->field_id ) {
				$wpdb->update( VFB_WP_FORMS_TABLE_NAME, array( 'form_email_from_name_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}

			if ( $notify == $field->field_id ) {
				$wpdb->update( VFB_WP_FORMS_TABLE_NAME, array( 'form_notification_email' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
			}
		}

		// Loop through our parents and update them to their new IDs
		foreach ( $parents as $k => $v ) {
			$wpdb->update( VFB_WP_FIELDS_TABLE_NAME, array( 'field_parent' => $v ), array( 'form_id' => $new_form_selected, 'field_parent' => $k ) );
		}

		// Redirect to keep the URL clean (use AJAX in the future?)
		wp_redirect( 'admin.php?page=visual-form-builder&action=edit&form=' . $new_form_selected );
		exit();
	}

	/**
	 * [save_settings description]
	 * @return [type] [description]
	 */
	public function save_settings() {
		if ( !isset( $_POST['action'] ) || !isset( $_GET['page'] ) )
			return;

		if ( 'vfb-settings' !== $_GET['page'] )
			return;

		if ( 'vfb_settings' !== $_POST['action'] )
			return;

		check_admin_referer( 'vfb-update-settings' );

		if ( isset( $_POST['visual-form-builder-uninstall'] ) ) {
			$this->uninstall_plugin();

			return;
		}

		$data = array();

		foreach ( $_POST['vfb-settings'] as $key => $val ) {
			$data[ $key ] = esc_html( $val );
		}

		update_option( 'vfb-settings', $data );
	}

	/**
	 * Uninstall plugin.
	 *
	 * Run uninstall on Settings page instead of Plugins page so we can
	 * keep VFB files on the server.
	 *
	 * @access private
	 * @param mixed $license_key
	 * @param mixed $license_email
	 * @return void
	 */
	private function uninstall_plugin() {
		$uninstall = new Visual_Form_Builder_Admin_Uninstall();
		$uninstall->uninstall();
	}
}
