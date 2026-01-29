<?php
/**
 * Submission Utility Functions for Ninja Forms Abilities
 *
 * This file contains all submission-related execute callback functions.
 * These functions handle submission CRUD operations, export, and processing.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ninja_forms_ability_get_submissions( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get filtering parameters
	$where = isset( $input['where'] ) && is_array( $input['where'] ) ? $input['where'] : array();
	$sub_ids = isset( $input['submission_ids'] ) && is_array( $input['submission_ids'] ) ? $input['submission_ids'] : array();

	// Get submissions
	$submissions = Ninja_Forms()->form( $form_id )->get_subs( $where, FALSE, $sub_ids );

	// Build response array
	$submissions_data = array();
	foreach ( $submissions as $sub ) {
		$submissions_data[] = array(
			'id'       => $sub->get_id(),
			'form_id'  => $sub->get_form_id(),
			'seq_num'  => $sub->get_seq_num(),
			'date'     => $sub->get_sub_date( 'Y-m-d H:i:s' ),
			'modified' => $sub->get_mod_date( 'Y-m-d H:i:s' ),
			'status'   => $sub->get_status(),
			'user_id'  => $sub->get_user() ? $sub->get_user()->ID : 0,
		);
	}

	return array(
		'success'     => true,
		'form_id'     => $form_id,
		'count'       => count( $submissions_data ),
		'submissions' => $submissions_data,
		'message'     => sprintf( __( 'Found %d submission(s) for form %d.', 'ninja-forms' ), count( $submissions_data ), $form_id ),
	);
}

function ninja_forms_ability_get_submission( $input ) {
	// Validate required input
	if ( empty( $input['submission_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Submission ID is required', 'ninja-forms' ),
		);
	}

	$submission_id = (int) $input['submission_id'];

	// Get submission
	$sub = Ninja_Forms()->form()->get_sub( $submission_id );
	if ( ! $sub || ! $sub->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Submission with ID %d not found', 'ninja-forms' ), $submission_id ),
		);
	}

	// Get field values
	$field_values = $sub->get_field_values();

	// Build submission data
	$submission_data = array(
		'id'           => $sub->get_id(),
		'form_id'      => $sub->get_form_id(),
		'form_title'   => $sub->get_form_title(),
		'seq_num'      => $sub->get_seq_num(),
		'date'         => $sub->get_sub_date( 'Y-m-d H:i:s' ),
		'modified'     => $sub->get_mod_date( 'Y-m-d H:i:s' ),
		'status'       => $sub->get_status(),
		'user_id'      => $sub->get_user() ? $sub->get_user()->ID : 0,
		'user_name'    => $sub->get_user() ? $sub->get_user()->display_name : '',
		'field_values' => $field_values,
	);

	return array(
		'success'    => true,
		'submission' => $submission_data,
		'message'    => sprintf( __( 'Retrieved submission %d from form "%s".', 'ninja-forms' ), $submission_id, $sub->get_form_title() ),
	);
}

function ninja_forms_ability_get_submission_fields( $input ) {
	// Validate required input
	if ( empty( $input['submission_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Submission ID is required', 'ninja-forms' ),
		);
	}

	$submission_id = (int) $input['submission_id'];

	// Get submission
	$sub = Ninja_Forms()->form()->get_sub( $submission_id );
	if ( ! $sub || ! $sub->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Submission with ID %d not found', 'ninja-forms' ), $submission_id ),
		);
	}

	// Get field values
	$field_values = $sub->get_field_values();

	// Optionally get field keys/labels if requested
	$include_labels = ! empty( $input['include_labels'] ) ? (bool) $input['include_labels'] : false;
	$fields_data = array();

	if ( $include_labels ) {
		$form_id = $sub->get_form_id();
		$form_fields = Ninja_Forms()->form( $form_id )->get_fields();

		// Build a map of field IDs to field objects
		$fields_by_id = array();
		foreach ( $form_fields as $field ) {
			$fields_by_id[ $field->get_id() ] = $field;
		}

		// Iterate through field values and match to fields
		foreach ( $field_values as $key => $value ) {
			// Skip metadata keys
			if ( in_array( $key, array( '_form_id', '_seq_num' ), true ) ) {
				continue;
			}

			// Extract field ID from key like "_field_123" or use key directly
			$field_id = $key;
			if ( strpos( $key, '_field_' ) === 0 ) {
				$field_id = (int) str_replace( '_field_', '', $key );
			}

			// Get field object
			if ( isset( $fields_by_id[ $field_id ] ) ) {
				$field = $fields_by_id[ $field_id ];
				$fields_data[] = array(
					'field_id'    => $field->get_id(),
					'field_key'   => $field->get_setting( 'key' ),
					'field_label' => $field->get_setting( 'label' ),
					'field_type'  => $field->get_setting( 'type' ),
					'value'       => $value,
				);
			}
		}
	} else {
		$fields_data = $field_values;
	}

	return array(
		'success'       => true,
		'submission_id' => $submission_id,
		'form_id'       => $sub->get_form_id(),
		'count'         => count( $field_values ),
		'fields'        => $fields_data,
		'message'       => sprintf( __( 'Retrieved %d field value(s) from submission %d.', 'ninja-forms' ), count( $field_values ), $submission_id ),
	);
}

function ninja_forms_ability_update_submission( $input ) {
	// Validate required inputs
	if ( empty( $input['submission_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Submission ID is required', 'ninja-forms' ),
		);
	}

	if ( empty( $input['field_values'] ) || ! is_array( $input['field_values'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Field values array is required', 'ninja-forms' ),
		);
	}

	$submission_id = (int) $input['submission_id'];

	// Get submission
	$sub = Ninja_Forms()->form()->get_sub( $submission_id );
	if ( ! $sub || ! $sub->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Submission with ID %d not found', 'ninja-forms' ), $submission_id ),
		);
	}

	$form_id = $sub->get_form_id();
	$field_values = $input['field_values'];
	$updated_count = 0;

	// Update each field value
	foreach ( $field_values as $field_ref => $value ) {
		$sub->update_field_value( $field_ref, $value );
		$updated_count++;
	}

	// Save the submission
	$sub->save();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) && $form_id ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'       => true,
		'submission_id' => $submission_id,
		'form_id'       => $form_id,
		'updated'       => $updated_count,
		'message'       => sprintf( __( 'Successfully updated %d field value(s) in submission %d.', 'ninja-forms' ), $updated_count, $submission_id ),
	);
}

function ninja_forms_ability_delete_submission( $input ) {
	// Validate required input
	if ( empty( $input['submission_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Submission ID is required', 'ninja-forms' ),
		);
	}

	$submission_id = (int) $input['submission_id'];

	// Get submission
	$sub = Ninja_Forms()->form()->get_sub( $submission_id );
	if ( ! $sub || ! $sub->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Submission with ID %d not found', 'ninja-forms' ), $submission_id ),
		);
	}

	$form_id = $sub->get_form_id();
	$seq_num = $sub->get_seq_num();

	// Determine deletion type (permanent or trash)
	$permanent = ! empty( $input['permanent'] ) ? (bool) $input['permanent'] : false;

	if ( $permanent ) {
		$sub->delete();
		$action_taken = 'permanently deleted';
	} else {
		$sub->trash();
		$action_taken = 'moved to trash';
	}

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) && $form_id ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'       => true,
		'submission_id' => $submission_id,
		'form_id'       => $form_id,
		'permanent'     => $permanent,
		'message'       => sprintf( __( 'Successfully %s submission #%d (ID: %d) from form %d.', 'ninja-forms' ), $action_taken, $seq_num, $submission_id, $form_id ),
	);
}

function ninja_forms_ability_export_submissions( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get submission IDs to export (empty = all)
	$sub_ids = isset( $input['submission_ids'] ) && is_array( $input['submission_ids'] ) ? $input['submission_ids'] : array();

	// Get format (CSV or JSON)
	$format = isset( $input['format'] ) ? strtolower( sanitize_text_field( $input['format'] ) ) : 'csv';

	try {
		if ( $format === 'csv' ) {
			// Export to CSV
			$csv_content = Ninja_Forms()->form( $form_id )->export_subs( $sub_ids, TRUE );

			if ( empty( $csv_content ) ) {
				return array(
					'success' => false,
					'message' => __( 'No submissions found to export', 'ninja-forms' ),
				);
			}

			// Return CSV content
			return array(
				'success' => true,
				'form_id' => $form_id,
				'format'  => 'csv',
				'count'   => count( $sub_ids ) > 0 ? count( $sub_ids ) : count( Ninja_Forms()->form( $form_id )->get_subs() ),
				'content' => $csv_content,
				'message' => __( 'Successfully exported submissions to CSV.', 'ninja-forms' ),
			);
		} elseif ( $format === 'json' ) {
			// Export to JSON
			$submissions = Ninja_Forms()->form( $form_id )->get_subs( array(), FALSE, $sub_ids );
			$submissions_data = array();

			foreach ( $submissions as $sub ) {
				$submissions_data[] = array(
					'id'           => $sub->get_id(),
					'form_id'      => $sub->get_form_id(),
					'seq_num'      => $sub->get_seq_num(),
					'date'         => $sub->get_sub_date( 'Y-m-d H:i:s' ),
					'status'       => $sub->get_status(),
					'user_id'      => $sub->get_user() ? $sub->get_user()->ID : 0,
					'field_values' => $sub->get_field_values(),
				);
			}

			return array(
				'success'     => true,
				'form_id'     => $form_id,
				'format'      => 'json',
				'count'       => count( $submissions_data ),
				'submissions' => $submissions_data,
				'message'     => __( 'Successfully exported submissions to JSON.', 'ninja-forms' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Unsupported export format: %s. Use "csv" or "json".', 'ninja-forms' ), $format ),
			);
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Export failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}

function ninja_forms_ability_process_submission( $input ) {
	// Validate required inputs
	if ( empty( $input['submission_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Submission ID is required', 'ninja-forms' ),
		);
	}

	if ( empty( $input['action_type'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Action type is required (e.g., "email")', 'ninja-forms' ),
		);
	}

	$submission_id = (int) $input['submission_id'];
	$action_type = sanitize_text_field( $input['action_type'] );

	// Get submission
	$sub = Ninja_Forms()->form()->get_sub( $submission_id );
	if ( ! $sub || ! $sub->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Submission with ID %d not found', 'ninja-forms' ), $submission_id ),
		);
	}

	$form_id = $sub->get_form_id();

	// Currently only support email action processing
	if ( $action_type !== 'email' ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Action type "%s" is not supported. Only "email" action processing is available.', 'ninja-forms' ), $action_type ),
		);
	}

	// Validate action settings
	if ( empty( $input['action_settings'] ) || ! is_array( $input['action_settings'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Action settings are required for email processing', 'ninja-forms' ),
		);
	}

	$action_settings = $input['action_settings'];

	// Ensure required email fields
	if ( empty( $action_settings['to'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Email "to" address is required in action_settings', 'ninja-forms' ),
		);
	}

	// Build submission data for action processing
	$field_values = $sub->get_field_values();
	$fields_data = array();

	// Get fields to build proper data structure
	$form_fields = Ninja_Forms()->form( $form_id )->get_fields();
	foreach ( $form_fields as $field ) {
		$field_id = $field->get_id();
		$field_key = $field->get_setting( 'key' );

		if ( isset( $field_values[ $field_id ] ) ) {
			$fields_data[] = array(
				'id'    => $field_id,
				'key'   => $field_key,
				'value' => $field_values[ $field_id ],
			);
		}
	}

	// Get email action class
	if ( ! isset( Ninja_Forms()->actions['email'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Email action not available', 'ninja-forms' ),
		);
	}

	$email_action = Ninja_Forms()->actions['email'];

	// Build data array for action processing
	$data = array(
		'form_id'       => $form_id,
		'submission_id' => $submission_id,
		'fields'        => $fields_data,
		'settings'      => Ninja_Forms()->form( $form_id )->get()->get_settings(),
	);

	try {
		// Process the email action
		$result = $email_action->process( $action_settings, $form_id, $data );

		return array(
			'success'       => true,
			'submission_id' => $submission_id,
			'form_id'       => $form_id,
			'action_type'   => $action_type,
			'message'       => __( 'Successfully processed email action for submission.', 'ninja-forms' ),
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Action processing failed: %s', 'ninja-forms' ), $e->getMessage() ),
		);
	}
}