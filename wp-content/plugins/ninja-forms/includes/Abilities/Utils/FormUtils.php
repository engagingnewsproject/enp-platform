<?php
/**
 * Form Utility Functions for Ninja Forms Abilities
 *
 * This file contains all form-related execute callback functions.
 * These functions handle form CRUD operations, import/export, embedding, and public links.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ninja_forms_ability_create_form( $input ) {
	if ( empty( $input['title'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form title is required', 'ninja-forms' ) );
	}

	$form = Ninja_Forms()->form()->get();

	// Build form settings array with defaults and overrides from input
	$settings = array(
		// Required setting
		'title'                   => sanitize_text_field( $input['title'] ),

		// Dashboard metadata (for parity with manual forms)
		'objectType'              => 'Form Setting',
		'editActive'              => '',

		// Display settings
		'show_title'              => isset( $input['show_title'] ) ? (int) $input['show_title'] : 1,
		'form_title_heading_level' => isset( $input['form_title_heading_level'] ) ? sanitize_text_field( $input['form_title_heading_level'] ) : '3',
		'default_label_pos'       => isset( $input['default_label_pos'] ) ? sanitize_text_field( $input['default_label_pos'] ) : 'above',

		// Submission behavior
		'clear_complete'          => isset( $input['clear_complete'] ) ? (int) $input['clear_complete'] : 1,
		'hide_complete'           => isset( $input['hide_complete'] ) ? (int) $input['hide_complete'] : 1,

		// Access control
		'allow_public_link'       => isset( $input['allow_public_link'] ) ? (int) $input['allow_public_link'] : 0,
		'logged_in'               => isset( $input['logged_in'] ) ? (bool) $input['logged_in'] : false,

		// Form builder settings
		'add_submit'              => 0,

		// CSS classes
		'wrapper_class'           => isset( $input['wrapper_class'] ) ? sanitize_text_field( $input['wrapper_class'] ) : '',
		'element_class'           => isset( $input['element_class'] ) ? sanitize_text_field( $input['element_class'] ) : '',

		// Advanced settings
		'currency'                => isset( $input['currency'] ) ? sanitize_text_field( $input['currency'] ) : '',
		'embed_form'              => '',
		'key'                     => '',

		// User-facing messages
		'not_logged_in_msg'       => isset( $input['not_logged_in_msg'] ) ? sanitize_text_field( $input['not_logged_in_msg'] ) : '',
		'sub_limit_msg'           => isset( $input['sub_limit_msg'] ) ? sanitize_text_field( $input['sub_limit_msg'] ) : __( 'The form has reached its submission limit.', 'ninja-forms' ),
		'unique_field_error'      => __( 'A form with this value has already been submitted.', 'ninja-forms' ),

		// Data structures
		'calculations'            => isset( $input['calculations'] ) ? $input['calculations'] : array(),
		'formContentData'         => array(),
	);

	// Add submission limit if provided
	if ( isset( $input['sub_limit_number'] ) && is_numeric( $input['sub_limit_number'] ) ) {
		$settings['sub_limit_number'] = (int) $input['sub_limit_number'];
	}

	// Add honeypot if requested
	if ( isset( $input['honeypot_enabled'] ) && $input['honeypot_enabled'] ) {
		$settings['honeypot_enabled'] = 1;
	}

	// Add ajax_submit if explicitly provided
	if ( isset( $input['ajax_submit'] ) ) {
		$settings['ajax_submit'] = (int) $input['ajax_submit'];
	}

	// Add conditional logic if provided
	if ( isset( $input['conditions'] ) && is_array( $input['conditions'] ) ) {
		$settings['conditions'] = $input['conditions'];
	}

	$form->update_settings( $settings );
	$form->save();
	$form_id = $form->get_id();

	// Process fields if provided
	$fields_created = array();
	if ( isset( $input['fields'] ) && is_array( $input['fields'] ) ) {
		foreach ( $input['fields'] as $field_data ) {
			$field_result = ninja_forms_ability_add_field_internal( $form_id, $field_data );
			if ( is_wp_error( $field_result ) ) {
				// Log error but continue with other fields
				error_log( 'Failed to add field: ' . $field_result->get_error_message() );
			} else {
				$fields_created[] = $field_result['field_id'];
			}
		}
	}

	// Always add a submit button (forms are useless without one)
	// Allow customization via input parameter, otherwise use sensible default
	$submit_label = isset( $input['submit_label'] ) ? sanitize_text_field( $input['submit_label'] ) : __( 'Submit', 'ninja-forms' );

	$submit_field_data = array(
		'type'  => 'submit',
		'label' => $submit_label,
		'order' => 9999, // Ensure submit button always appears last
	);

	$submit_result = ninja_forms_ability_add_field_internal( $form_id, $submit_field_data );
	if ( ! is_wp_error( $submit_result ) ) {
		$fields_created[] = $submit_result['field_id'];
	}

	// CRITICAL FIX: HTML fields with merge tags need save() called twice for merge tag processing to work
	// First save (in add_field_internal line 2251) creates the field
	// Second save (here) initializes merge tag processing after calculations are set up
	// Without this, merge tags like {calc:name} won't display in HTML fields
	if ( ! empty( $fields_created ) ) {
		foreach ( $fields_created as $field_id ) {
			$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
			if ( $field && $field->get_setting( 'type' ) === 'html' ) {
				// Re-save HTML field to initialize merge tag processing
				$field->save();
			}
		}
	}

	// Process actions
	if ( isset( $input['actions'] ) && is_array( $input['actions'] ) ) {
		// Custom actions provided - create those
		$order = 1;
		foreach ( $input['actions'] as $action_data ) {
			if ( empty( $action_data['type'] ) ) {
				continue; // Skip actions without type
			}

			$action = Ninja_Forms()->form( $form_id )->action()->get();

			$action_settings = array(
				'type'   => sanitize_text_field( $action_data['type'] ),
				'label'  => isset( $action_data['label'] ) ? sanitize_text_field( $action_data['label'] ) : ucfirst( $action_data['type'] ),
				'active' => isset( $action_data['active'] ) ? (int) $action_data['active'] : 1,
				'order'  => $order++,
			);

			// Merge in additional settings
			if ( isset( $action_data['settings'] ) && is_array( $action_data['settings'] ) ) {
				$action_settings = array_merge( $action_settings, $action_data['settings'] );
			}

			$action->update_settings( $action_settings );
			$action->save();
		}
	} else {
		// No custom actions - add default actions that match dashboard "Blank Form" behavior

		// CRITICAL ACTION CREATION REQUIREMENTS (Session 10 - November 2025):
		// =====================================================================
		// When creating actions, you MUST:
		// 1. Use update_setting() (singular) NOT update_settings() (plural array)
		// 2. EXPLICITLY set 'parent_id' => $form_id for EVERY action
		// 3. Set each property individually with update_setting()
		//
		// WHY THIS MATTERS:
		// - Without explicit parent_id, save actions don't work (submissions not saved)
		// - Forms appear to work (show success message) but data is lost
		// - This was root cause of W3, W6 submission bugs in testing
		// - add-action ability works because it sets parent_id explicitly
		// - Original code used update_settings() array without parent_id = BROKEN
		//
		// VERIFIED FIX (Session 10):
		// - Form 51 tested with explicit parent_id = submissions saved correctly
		// - Form 50 (before fix) = no submissions saved, required delete/recreate
		// =====================================================================

		// Action 1: Success Message
		$success_message = isset( $input['success_message'] ) ? sanitize_text_field( $input['success_message'] ) : __( 'Your form has been successfully submitted.', 'ninja-forms' );
		$success_action = Ninja_Forms()->form( $form_id )->action()->get();
		$success_action->update_setting( 'parent_id', $form_id );
		$success_action->update_setting( 'type', 'successmessage' );
		$success_action->update_setting( 'label', __( 'Success Message', 'ninja-forms' ) );
		$success_action->update_setting( 'active', 1 );
		$success_action->update_setting( 'success_msg', $success_message ); // CRITICAL: Use 'success_msg' not 'message'
		$success_action->update_setting( 'order', 1 );
		$success_action->save();

		// Action 2: Admin Email
		$email_to = isset( $input['admin_email_to'] ) ? sanitize_text_field( $input['admin_email_to'] ) : '{wp:admin_email}';
		$email_subject = isset( $input['admin_email_subject'] ) ? sanitize_text_field( $input['admin_email_subject'] ) : __( 'Ninja Forms Submission', 'ninja-forms' );

		$email_action = Ninja_Forms()->form( $form_id )->action()->get();
		$email_action->update_setting( 'parent_id', $form_id );
		$email_action->update_setting( 'type', 'email' );
		$email_action->update_setting( 'label', __( 'Admin Email', 'ninja-forms' ) );
		$email_action->update_setting( 'active', 1 );
		$email_action->update_setting( 'to', $email_to );
		$email_action->update_setting( 'email_subject', $email_subject );
		$email_action->update_setting( 'email_message', '{fields_table}' );
		$email_action->update_setting( 'email_format', 'html' );
		$email_action->update_setting( 'reply_to', '' );
		$email_action->update_setting( 'from_name', '' );
		$email_action->update_setting( 'from_address', '' );
		$email_action->update_setting( 'cc', '' );
		$email_action->update_setting( 'bcc', '' );
		$email_action->update_setting( 'order', 2 );
		$email_action->save();

		// Action 3: Record Submission
		$save_action = Ninja_Forms()->form( $form_id )->action()->get();
		$save_action->update_setting( 'parent_id', $form_id ); // CRITICAL: Must explicitly set parent_id
		$save_action->update_setting( 'type', 'save' );
		$save_action->update_setting( 'label', __( 'Record Submission', 'ninja-forms' ) );
		$save_action->update_setting( 'active', 1 );
		$save_action->update_setting( 'order', 3 );
		$save_action->save();
	}

	$message = sprintf( __( 'Form created with ID %d', 'ninja-forms' ), $form_id );
	if ( ! empty( $fields_created ) ) {
		$message .= sprintf( __( ' with %d field(s)', 'ninja-forms' ), count( $fields_created ) );
	}

	return array(
		'success'     => true,
		'form_id'     => $form_id,
		'field_count' => count( $fields_created ),
		'message'     => $message,
	);
}

function ninja_forms_ability_list_forms( $input ) {
	global $wpdb;

	// Build WHERE clause for optional title filter
	$where = '';
	$where_args = array();

	if ( ! empty( $input['title'] ) ) {
		$where = 'WHERE title LIKE %s';
		$where_args[] = '%' . $wpdb->esc_like( sanitize_text_field( $input['title'] ) ) . '%';
	}

	// Build base query
	$base_query = "SELECT id, title, created_at FROM {$wpdb->prefix}nf3_forms {$where} ORDER BY created_at DESC";
	
	// Add LIMIT using prepared statement
	if ( ! empty( $input['limit'] ) && (int) $input['limit'] > 0 ) {
		$limit_value = (int) $input['limit'];
		$base_query .= " LIMIT %d";
		
		if ( ! empty( $where_args ) ) {
			$where_args[] = $limit_value;
		} else {
			$where_args = array( $limit_value );
		}
	}

	// Prepare final query
	if ( ! empty( $where_args ) ) {
		$query = $wpdb->prepare( $base_query, $where_args );
	} else {
		$query = $base_query;
	}

	$forms_data = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return new WP_Error( 'database_error', __( 'Failed to retrieve forms', 'ninja-forms' ), array( 'error' => $wpdb->last_error ) );
	}

	// Build response array
	$forms = array();
	$include_fields = isset( $input['include_fields'] ) ? (bool) $input['include_fields'] : true;
	$include_actions = isset( $input['include_actions'] ) ? (bool) $input['include_actions'] : true;

	foreach ( $forms_data as $form_data ) {
		$form_id = (int) $form_data['id'];
		$form = Ninja_Forms()->form( $form_id )->get();

		$form_info = array(
			'id'         => $form_id,
			'title'      => $form_data['title'],
			'created_at' => (string) $form_data['created_at'],
			'settings'   => $form->get_settings(),
		);

		// Add field count if requested
		if ( $include_fields ) {
			$field_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d",
				$form_id
			) );
			$form_info['field_count'] = (int) $field_count;
		}

		// Add action count if requested
		if ( $include_actions ) {
			$action_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}nf3_actions WHERE parent_id = %d",
				$form_id
			) );
			$form_info['action_count'] = (int) $action_count;
		}

		$forms[] = $form_info;
	}

	$count = count( $forms );
	$message = sprintf( __( 'Found %d form(s)', 'ninja-forms' ), $count );

	return array(
		'success' => true,
		'forms'   => $forms,
		'count'   => $count,
		'message' => $message,
	);
}

function ninja_forms_ability_get_form( $input ) {
	// Validate form_id
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	$form_id = (int) $input['form_id'];

	// Get form object
	$form = Ninja_Forms()->form( $form_id )->get();

	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ) );
	}

	// Build base form data
	global $wpdb;
	$form_record = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, title, created_at FROM {$wpdb->prefix}nf3_forms WHERE id = %d",
		$form_id
	), ARRAY_A );

	$form_data = array(
		'id'         => $form_id,
		'title'      => $form_record['title'],
		'created_at' => (string) $form_record['created_at'],
		'settings'   => $form->get_settings(),
	);

	// Include fields if requested (default: true)
	$include_fields = isset( $input['include_fields'] ) ? (bool) $input['include_fields'] : true;
	if ( $include_fields ) {
		// Use factory pattern to get fields
		$fields = Ninja_Forms()->form( $form_id )->get_fields();
		$fields_data = array();

		foreach ( $fields as $field ) {
			$fields_data[] = array(
				'id'       => $field->get_id(),
				'type'     => $field->get_setting( 'type' ),
				'label'    => $field->get_setting( 'label' ),
				'key'      => $field->get_setting( 'key' ),
				'order'    => $field->get_setting( 'order' ),
				'required' => $field->get_setting( 'required' ),
				'settings' => $field->get_settings(),
			);
		}

		$form_data['fields'] = $fields_data;
		$form_data['field_count'] = count( $fields_data );
	}

	// Include actions if requested (default: true)
	$include_actions = isset( $input['include_actions'] ) ? (bool) $input['include_actions'] : true;
	if ( $include_actions ) {
		// Use factory pattern to get actions
		$actions = Ninja_Forms()->form( $form_id )->get_actions();
		$actions_data = array();

		foreach ( $actions as $action ) {
			$actions_data[] = array(
				'id'       => $action->get_id(),
				'type'     => $action->get_setting( 'type' ),
				'label'    => $action->get_setting( 'label' ),
				'active'   => $action->get_setting( 'active' ),
				'order'    => $action->get_setting( 'order' ),
				'settings' => $action->get_settings(),
			);
		}

		$form_data['actions'] = $actions_data;
		$form_data['action_count'] = count( $actions_data );
	}

	// Include calculations if requested (default: true)
	$include_calculations = isset( $input['include_calculations'] ) ? (bool) $input['include_calculations'] : true;
	if ( $include_calculations ) {
		$calculations = $form->get_setting( 'calculations' );
		$form_data['calculations'] = is_array( $calculations ) ? $calculations : array();
		$form_data['calculation_count'] = count( $form_data['calculations'] );
	}

	return array(
		'success' => true,
		'form'    => $form_data,
		'message' => sprintf( __( 'Retrieved form "%s" (ID: %d)', 'ninja-forms' ), $form_record['title'], $form_id ),
	);
}

function ninja_forms_ability_update_form( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ) );
	}

	// Build settings array with only the provided values
	$settings_to_update = array();

	// Map of input keys to form settings
	$setting_map = array(
		'title'                     => 'title',
		'show_title'                => 'show_title',
		'form_title_heading_level'  => 'form_title_heading_level',
		'default_label_pos'         => 'default_label_pos',
		'clear_complete'            => 'clear_complete',
		'hide_complete'             => 'hide_complete',
		'allow_public_link'         => 'allow_public_link',
		'logged_in'                 => 'logged_in',
		'wrapper_class'             => 'wrapper_class',
		'element_class'             => 'element_class',
		'currency'                  => 'currency',
	);

	// Only include settings that were provided in the input
	foreach ( $setting_map as $input_key => $setting_key ) {
		if ( isset( $input[ $input_key ] ) ) {
			$value = $input[ $input_key ];

			// Sanitize based on type
			if ( in_array( $input_key, array( 'show_title', 'clear_complete', 'hide_complete', 'allow_public_link' ), true ) ) {
				$value = (int) $value;
			} elseif ( $input_key === 'logged_in' ) {
				$value = (bool) $value;
			} else {
				$value = sanitize_text_field( $value );
			}

			$settings_to_update[ $setting_key ] = $value;
		}
	}

	// If no settings to update, return error
	if ( empty( $settings_to_update ) ) {
		return new WP_Error( 'no_settings', __( 'No settings provided to update', 'ninja-forms' ) );
	}

	// Update the form settings
	$form->update_settings( $settings_to_update );
	$form->save();

	// Clear form cache
	WPN_Helper::delete_nf_cache( $form_id );

	// Get the updated form title for the message
	$form_title = $form->get_setting( 'title' );

	return array(
		'success' => true,
		'form_id' => $form_id,
		'updated' => $settings_to_update,
		'message' => sprintf( __( 'Successfully updated form "%s" (ID: %d). Updated %d setting(s).', 'ninja-forms' ), $form_title, $form_id, count( $settings_to_update ) ),
	);
}

function ninja_forms_ability_delete_form( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ) );
	}

	// Store form title for message before deletion
	$form_title = $form->get_setting( 'title' );

	// Delete the form (cascades to fields, actions, submissions, metadata)
	$form->delete();

	return array(
		'success' => true,
		'form_id' => $form_id,
		'message' => sprintf( __( 'Successfully deleted form "%s" (ID: %d) and all associated data.', 'ninja-forms' ), $form_title, $form_id ),
	);
}

function ninja_forms_ability_duplicate_form( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ) );
	}

	// Duplicate the form using the static method
	$new_form_id = NF_Database_Models_Form::duplicate( $form_id );

	if ( ! $new_form_id ) {
		return new WP_Error( 'duplication_failed', __( 'Failed to duplicate form', 'ninja-forms' ) );
	}

	// Get the new form to check if custom title was requested
	$new_form = Ninja_Forms()->form( $new_form_id )->get();

	if ( ! empty( $input['new_title'] ) ) {
		// Update with custom title
		$new_form->update_setting( 'title', sanitize_text_field( $input['new_title'] ) );
		$new_form->save();
		$new_title = $input['new_title'];
	} else {
		// Use the automatically generated title (original + " (Copy)")
		$new_title = $new_form->get_setting( 'title' );
	}

	return array(
		'success'     => true,
		'form_id'     => $form_id,
		'new_form_id' => $new_form_id,
		'title'       => $new_title,
		'message'     => sprintf( __( 'Successfully duplicated form (ID: %d) to "%s" (ID: %d).', 'ninja-forms' ), $form_id, $new_title, $new_form_id ),
	);
}

function ninja_forms_ability_import_form( $input ) {
	// Validate required inputs
	if ( empty( $input['file_content'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'No file content provided.', 'ninja-forms' ),
		);
	}

	$file_content = $input['file_content'];
	$decode_utf8 = isset( $input['decode_utf8'] ) ? (bool) $input['decode_utf8'] : true;

	// Import form
	try {
		$form_id = Ninja_Forms()->form()->import_form( $file_content, $decode_utf8 );

		if ( ! $form_id ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to import form. Invalid format or data.', 'ninja-forms' ),
			);
		}

		// Get form title
		$form = Ninja_Forms()->form( $form_id )->get();
		$form_title = $form->get_setting( 'title' );

		return array(
			'success'    => true,
			'message'    => sprintf( __( 'Form "%s" imported successfully.', 'ninja-forms' ), $form_title ),
			'form_id'    => $form_id,
			'form_title' => $form_title,
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Import failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}

function ninja_forms_ability_export_form_definition( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];

	// Validate form exists - check database directly
	global $wpdb;
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}nf3_forms WHERE id = %d", $form_id ) );
	if ( ! $exists ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found.', 'ninja-forms' ), $form_id ),
		);
	}

	$form = Ninja_Forms()->form( $form_id )->get();

	try {
		// Export form (returns array when $return = true)
		$export_data = Ninja_Forms()->form( $form_id )->export_form( true );

		if ( ! $export_data || ! is_array( $export_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to export form data.', 'ninja-forms' ),
			);
		}

		// Get form title for filename
		$form_title = $form->get_setting( 'title' );
		$form_title_sanitized = preg_replace( "/[^A-Za-z0-9 ]/", '', $form_title );
		$form_title_sanitized = str_replace( ' ', '_', $form_title_sanitized );
		$date = date( 'm/d/Y', current_time( 'timestamp' ) );
		$filename = 'nf_form_' . $date . '_' . $form_title_sanitized . '.nff';

		// JSON encode with UTF-8 encoding
		$content = json_encode( WPN_Helper::utf8_encode( $export_data ) );

		return array(
			'success'    => true,
			'message'    => sprintf( __( 'Form "%s" exported successfully.', 'ninja-forms' ), $form_title ),
			'form_id'    => $form_id,
			'form_title' => $form_title,
			'content'    => $content,
			'filename'   => $filename,
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Export failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}

function ninja_forms_ability_embed_form( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	// Extract and sanitize inputs
	$form_id             = (int) $input['form_id'];
	$post_id             = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
	$post_title          = isset( $input['post_title'] ) ? sanitize_text_field( $input['post_title'] ) : '';
	$post_content        = isset( $input['post_content'] ) ? wp_kses_post( $input['post_content'] ) : '';
	$post_status         = isset( $input['post_status'] ) ? sanitize_key( $input['post_status'] ) : 'publish';
	$post_type           = isset( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'page';
	$embed_method        = isset( $input['embed_method'] ) ? sanitize_key( $input['embed_method'] ) : '';
	$placement           = isset( $input['placement'] ) ? sanitize_key( $input['placement'] ) : '';
	$placement_reference = isset( $input['placement_reference'] ) ? sanitize_text_field( $input['placement_reference'] ) : '';
	$confirm_create      = isset( $input['confirm_create'] ) ? (bool) $input['confirm_create'] : false;

	// Validate form exists
	global $wpdb;
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}nf3_forms WHERE id = %d", $form_id ) );
	if ( ! $exists ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found.', 'ninja-forms' ), $form_id ),
		);
	}

	$form = Ninja_Forms()->form( $form_id )->get();

	try {
		// ========== STEP 1: DISCOVERY MODE ==========
		// If no embed_method provided, we're in discovery - return page info for user to review
		if ( empty( $embed_method ) ) {
			// Try to find existing page
			$existing_page = null;

			if ( $post_id ) {
				$existing_page = get_post( $post_id );
			} elseif ( ! empty( $post_title ) ) {
				$existing_page = get_page_by_title( $post_title, OBJECT, $post_type );
			}

			if ( $existing_page ) {
				// PAGE FOUND - Return content for user to review
				$blocks = parse_blocks( $existing_page->post_content );
				$readable_blocks = array();

				foreach ( $blocks as $index => $block ) {
					$block_name = ! empty( $block['blockName'] ) ? $block['blockName'] : 'unknown';
					$inner_content = ! empty( $block['innerHTML'] ) ? wp_strip_all_tags( $block['innerHTML'] ) : '';
					$inner_content = trim( preg_replace( '/\s+/', ' ', $inner_content ) );

					if ( ! empty( $inner_content ) ) {
						$readable_blocks[] = array(
							'index'   => $index,
							'type'    => $block_name,
							'preview' => substr( $inner_content, 0, 100 ) . ( strlen( $inner_content ) > 100 ? '...' : '' ),
						);
					}
				}

				return array(
					'success'            => true,
					'page_found'         => true,
					'post_id'            => $existing_page->ID,
					'post_title'         => $existing_page->post_title,
					'post_content'       => $existing_page->post_content,
					'content_blocks'     => $readable_blocks,
					'needs_embed_method' => true,
					'available_methods'  => array( 'shortcode', 'block', 'metabox' ),
					'message'            => sprintf( __( 'Found existing page "%s". Choose an embed method to continue.', 'ninja-forms' ), $existing_page->post_title ),
				);
			} else {
				// PAGE NOT FOUND - Ask user to confirm creation
				return array(
					'success'            => false,
					'page_not_found'     => true,
					'needs_confirmation' => true,
					'message'            => sprintf( __( 'No page titled "%s" was found. User must confirm page creation.', 'ninja-forms' ), $post_title ),
				);
			}
		}

		// ========== STEP 2: EXECUTION MODE ==========
		// embed_method is provided, proceed with embedding

		// Find or create the page
		$target_post = null;

		if ( $post_id ) {
			$target_post = get_post( $post_id );
			if ( ! $target_post ) {
				return array(
					'success' => false,
					'message' => sprintf( __( 'Post with ID %d not found.', 'ninja-forms' ), $post_id ),
				);
			}
		} elseif ( ! empty( $post_title ) ) {
			$target_post = get_page_by_title( $post_title, OBJECT, $post_type );

			if ( ! $target_post && $confirm_create ) {
				// Create new page
				$new_post_id = wp_insert_post( array(
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_status'  => $post_status,
					'post_type'    => $post_type,
				) );

				if ( is_wp_error( $new_post_id ) ) {
					return array(
						'success' => false,
						'message' => $new_post_id->get_error_message(),
					);
				}

				$target_post = get_post( $new_post_id );
			} elseif ( ! $target_post ) {
				return array(
					'success' => false,
					'message' => sprintf( __( 'Page "%s" not found and creation not confirmed.', 'ninja-forms' ), $post_title ),
				);
			}
		}

		if ( ! $target_post ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to determine target page.', 'ninja-forms' ),
			);
		}

		$post_id = $target_post->ID;
		$result_message = '';

		// Handle embedding based on method
		if ( $embed_method === 'metabox' ) {
			// METABOX: Set post meta
			update_post_meta( $post_id, '_nf_form_id', $form_id );
			$result_message = sprintf( __( 'Form embedded via metabox in "%s" (will appear at bottom of page).', 'ninja-forms' ), $target_post->post_title );

		} elseif ( $embed_method === 'shortcode' || $embed_method === 'block' ) {
			// SHORTCODE or BLOCK: Insert into content with placement
			$shortcode = "[ninja_forms id='{$form_id}']";
			$block_markup = "<!-- wp:ninja-forms/form {\"formId\":{$form_id}} /-->";
			$insert_content = ( $embed_method === 'block' ) ? $block_markup : $shortcode;

			$existing_content = $target_post->post_content;
			$new_content = '';

			// Handle placement
			if ( $placement === 'at_beginning' ) {
				$new_content = $insert_content . "\n\n" . $existing_content;
			} elseif ( $placement === 'at_end' ) {
				$new_content = $existing_content . "\n\n" . $insert_content;
			} elseif ( in_array( $placement, array( 'before', 'after', 'replace' ) ) && ! empty( $placement_reference ) ) {
				// Parse blocks and insert at specific position
				$blocks = parse_blocks( $existing_content );
				$block_index = (int) str_replace( 'block_', '', $placement_reference );

				if ( isset( $blocks[ $block_index ] ) ) {
					if ( $placement === 'replace' ) {
						$blocks[ $block_index ]['innerHTML'] = $insert_content;
						$blocks[ $block_index ]['innerContent'] = array( $insert_content );
					} elseif ( $placement === 'before' ) {
						array_splice( $blocks, $block_index, 0, array( array( 'innerHTML' => $insert_content, 'innerContent' => array( $insert_content ) ) ) );
					} elseif ( $placement === 'after' ) {
						array_splice( $blocks, $block_index + 1, 0, array( array( 'innerHTML' => $insert_content, 'innerContent' => array( $insert_content ) ) ) );
					}
					$new_content = serialize_blocks( $blocks );
				} else {
					// Fallback to end if block not found
					$new_content = $existing_content . "\n\n" . $insert_content;
				}
			} else {
				// Default to end
				$new_content = $existing_content . "\n\n" . $insert_content;
			}

			// Update post content
			$update_result = wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			) );

			if ( is_wp_error( $update_result ) ) {
				return array(
					'success' => false,
					'message' => $update_result->get_error_message(),
				);
			}

			$placement_desc = $placement === 'at_beginning' ? 'at the beginning' :
			                  ( $placement === 'at_end' ? 'at the end' :
			                  ( ! empty( $placement_reference ) ? "{$placement} {$placement_reference}" : 'at the end' ) );

			$result_message = sprintf( __( 'Form embedded via %s in "%s" (%s).', 'ninja-forms' ), $embed_method, $target_post->post_title, $placement_desc );

		} else {
			return array(
				'success' => false,
				'message' => __( 'Invalid embed method. Use shortcode, block, or metabox.', 'ninja-forms' ),
			);
		}

		// Update form embed_form setting
		$embed_form = $form->get_setting( 'embed_form' );
		if ( empty( $embed_form ) ) {
			$embed_form = array();
		} else {
			$embed_form = explode( ',', $embed_form );
		}
		if ( ! in_array( $post_id, $embed_form ) ) {
			$embed_form[] = $post_id;
			$form->update_setting( 'embed_form', implode( ',', $embed_form ) );
			$form->save();
		}

		return array(
			'success'    => true,
			'message'    => $result_message,
			'post_id'    => $post_id,
			'post_title' => $target_post->post_title,
			'permalink'  => get_permalink( $post_id ),
		);

	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Embed failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}

function ninja_forms_ability_get_public_link( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$enable  = isset( $input['enable'] ) ? (bool) $input['enable'] : true;

	// Validate form exists - check database directly
	global $wpdb;
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}nf3_forms WHERE id = %d", $form_id ) );
	if ( ! $exists ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found.', 'ninja-forms' ), $form_id ),
		);
	}

	$form = Ninja_Forms()->form( $form_id )->get();

	try {
		if ( $enable ) {
			// Enable public link
			$form->update_setting( 'allow_public_link', 1 );

			// Generate public_link_key if not exists
			// Use short alphanumeric slug (6 chars) to match UI behavior, not long MD5 hash
			$public_link_key = $form->get_setting( 'public_link_key' );
			if ( empty( $public_link_key ) ) {
				// Generate 6-character random alphanumeric string (lowercase letters + numbers)
				$characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
				$key_length = 6;
				$public_link_key = '';
				for ( $i = 0; $i < $key_length; $i++ ) {
					$public_link_key .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
				}
				$form->update_setting( 'public_link_key', $public_link_key );
			}

			// Build public link URL using pretty URL format /ninja-forms/{slug}
			// This matches the UI behavior and uses WordPress rewrite rules
			$public_link = home_url( '/ninja-forms/' . $public_link_key );
			$form->update_setting( 'public_link', $public_link );

			$form->save();

			return array(
				'success'         => true,
				'message'         => __( 'Public link enabled successfully.', 'ninja-forms' ),
				'public_link'     => $public_link,
				'public_link_key' => $public_link_key,
				'enabled'         => true,
			);

		} else {
			// Disable public link
			$form->update_setting( 'allow_public_link', 0 );
			$form->save();

			return array(
				'success' => true,
				'message' => __( 'Public link disabled successfully.', 'ninja-forms' ),
				'enabled' => false,
			);
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Public link operation failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}