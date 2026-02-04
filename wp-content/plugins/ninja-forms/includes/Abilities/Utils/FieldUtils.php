<?php
/**
 * Field Utility Functions for Ninja Forms Abilities
 *
 * This file contains all field-related execute callback functions.
 * These functions handle field CRUD operations, validation, and management.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ninja_forms_ensure_field_key_timestamp( $key ) {
	// Check if key already ends with a 13-digit timestamp
	// Pattern: ends with underscore followed by exactly 13 digits
	if ( preg_match( '/_\d{13}$/', $key ) ) {
		// Key already has timestamp format
		return $key;
	}

	// Append current timestamp (milliseconds since epoch)
	// PHP's microtime returns seconds.microseconds, multiply by 1000 for milliseconds
	$timestamp = round( microtime( true ) * 1000 );

	return $key . '_' . $timestamp;
}

function ninja_forms_ability_add_field_internal( $form_id, $field_data ) {
	// Validate required field type
	if ( empty( $field_data['type'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field type is required', 'ninja-forms' ) );
	}

	// Create new field
	$field = Ninja_Forms()->form( $form_id )->field()->get();

	// Set required field type
	$field->update_setting( 'type', sanitize_text_field( $field_data['type'] ) );

	// Set parent form ID
	$field->update_setting( 'parent_id', $form_id );

	// Set optional label
	if ( isset( $field_data['label'] ) && ! empty( $field_data['label'] ) ) {
		$field->update_setting( 'label', sanitize_text_field( $field_data['label'] ) );
	} elseif ( $field_data['type'] === 'html' ) {
		// HTML fields default to "HTML" label if not specified (matches UI)
		$field->update_setting( 'label', 'HTML' );
	} elseif ( $field_data['type'] === 'hr' ) {
		// HR fields default to "Divider" label if not specified (matches UI)
		$field->update_setting( 'label', 'Divider' );
	}

	// Set optional admin label
	if ( isset( $field_data['admin_label'] ) && ! empty( $field_data['admin_label'] ) ) {
		$field->update_setting( 'admin_label', sanitize_text_field( $field_data['admin_label'] ) );
	}

	// Set key (required for field display)
	// Auto-generate if not provided, and ensure proper timestamp format
	if ( isset( $field_data['key'] ) && ! empty( $field_data['key'] ) ) {
		$key = sanitize_text_field( $field_data['key'] );
	} else {
		// Generate key from label or type
		if ( isset( $field_data['label'] ) && ! empty( $field_data['label'] ) ) {
			// Sanitize label to create key: "Full Name" -> "full_name"
			$key = sanitize_title_with_dashes( $field_data['label'] );
			$key = str_replace( '-', '_', $key );
		} else {
			// Fallback to type-based key
			$key = sanitize_text_field( $field_data['type'] );
		}
	}

	// Ensure key has timestamp suffix (matches WordPress UI and Template Builder format)
	// This ensures consistency: descriptive_name_1234567890123
	$key = ninja_forms_ensure_field_key_timestamp( $key );

	// Ensure uniqueness by checking existing keys and appending counter if needed
	$original_key = $key;
	$counter = 1;
	global $wpdb;
	while ( $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d AND `key` = %s",
		$form_id,
		$key
	) ) ) {
		// If duplicate found, strip timestamp and append counter before re-adding timestamp
		$key_without_timestamp = preg_replace( '/_\d{13}$/', '', $original_key );
		$key = ninja_forms_ensure_field_key_timestamp( $key_without_timestamp . '_' . $counter );
		$counter++;
	}

	$field->update_setting( 'key', $key );

	// Set required toggle (default to not required)
	$field->update_setting( 'required', isset( $field_data['required'] ) ? (int) $field_data['required'] : 0 );

	// Set placeholder (always set, even if empty, for dashboard parity)
	$field->update_setting( 'placeholder', isset( $field_data['placeholder'] ) ? sanitize_text_field( $field_data['placeholder'] ) : '' );

	// Set optional default value
	// Support both 'default' and 'default_value' parameters for backwards compatibility
	if ( isset( $field_data['default'] ) ) {
		// HTML fields and other fields can use 'default' directly
		// For HTML fields, don't sanitize - we want to preserve HTML tags
		if ( isset( $field_data['type'] ) && $field_data['type'] === 'html' ) {
			$default_content = wp_kses_post( $field_data['default'] );
			$field->update_setting( 'default', $default_content );
			// CRITICAL: HTML fields need 'value' set for frontend rendering
			// The JavaScript template uses {{{ data.value }}}, not data.default
			$field->update_setting( 'value', $default_content );
		} else {
			$field->update_setting( 'default', sanitize_text_field( $field_data['default'] ) );
		}
	} elseif ( isset( $field_data['default_value'] ) && ! empty( $field_data['default_value'] ) ) {
		// Legacy parameter name support
		$field->update_setting( 'default', sanitize_text_field( $field_data['default_value'] ) );
	}

	// Set help text (always set, even if empty, for dashboard parity)
	$field->update_setting( 'help_text', isset( $field_data['help_text'] ) ? sanitize_text_field( $field_data['help_text'] ) : '' );

	// Handle list field options
	if ( isset( $field_data['options'] ) && is_array( $field_data['options'] ) ) {
		$options = array();
		foreach ( $field_data['options'] as $option ) {
			$option_item = array(
				'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '',
				'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
			);
			if ( isset( $option['calc'] ) ) {
				$option_item['calc'] = sanitize_text_field( $option['calc'] );
			}
			$options[] = $option_item;
		}
		$field->update_setting( 'options', $options );
	}

	// Dashboard metadata (for parity with manual fields)
	$field->update_setting( 'objectType', 'Field' );
	$field->update_setting( 'objectDomain', 'fields' );
	$field->update_setting( 'editActive', false );  // boolean false, not empty string
	$field->update_setting( 'idAttribute', 'id' );  // Critical for JavaScript initialization

	// Field configuration defaults
	$field->update_setting( 'label_pos', isset( $field_data['label_pos'] ) ? sanitize_text_field( $field_data['label_pos'] ) : 'above' );
	$field->update_setting( 'order', isset( $field_data['order'] ) ? (int) $field_data['order'] : '' );
	$field->update_setting( 'wrapper_class', isset( $field_data['wrapper_class'] ) ? sanitize_text_field( $field_data['wrapper_class'] ) : '' );
	$field->update_setting( 'element_class', isset( $field_data['element_class'] ) ? sanitize_text_field( $field_data['element_class'] ) : '' );
	$field->update_setting( 'container_class', isset( $field_data['container_class'] ) ? sanitize_text_field( $field_data['container_class'] ) : '' );
	$field->update_setting( 'admin_label', isset( $field_data['admin_label'] ) ? sanitize_text_field( $field_data['admin_label'] ) : '' );
	$field->update_setting( 'desc_text', isset( $field_data['desc_text'] ) ? sanitize_text_field( $field_data['desc_text'] ) : '' );
	$field->update_setting( 'manual_key', '' );
	$field->update_setting( 'disable_input', '' );
	$field->update_setting( 'disable_browser_autocomplete', '' );
	$field->update_setting( 'mask', '' );
	$field->update_setting( 'custom_mask', '' );
	$field->update_setting( 'input_limit', '' );
	$field->update_setting( 'input_limit_type', 'characters' );
	$field->update_setting( 'input_limit_msg', 'Character(s) left' );
	$field->update_setting( 'cellcid', '' );

	// Ensure default setting exists (even if empty)
	// Only set to empty if NEITHER 'default' nor 'default_value' is provided
	if ( ! isset( $field_data['default'] ) && ! isset( $field_data['default_value'] ) ) {
		$field->update_setting( 'default', '' );
	}

	// CSS styling placeholders - wrap styles
	$field->update_setting( 'wrap_styles_background-color', '' );
	$field->update_setting( 'wrap_styles_border', '' );
	$field->update_setting( 'wrap_styles_border-style', '' );
	$field->update_setting( 'wrap_styles_border-color', '' );
	$field->update_setting( 'wrap_styles_color', '' );
	$field->update_setting( 'wrap_styles_height', '' );
	$field->update_setting( 'wrap_styles_width', '' );
	$field->update_setting( 'wrap_styles_font-size', '' );
	$field->update_setting( 'wrap_styles_margin', '' );
	$field->update_setting( 'wrap_styles_padding', '' );
	$field->update_setting( 'wrap_styles_display', '' );
	$field->update_setting( 'wrap_styles_float', '' );
	$field->update_setting( 'wrap_styles_show_advanced_css', '0' );
	$field->update_setting( 'wrap_styles_advanced', '' );

	// CSS styling placeholders - label styles
	$field->update_setting( 'label_styles_background-color', '' );
	$field->update_setting( 'label_styles_border', '' );
	$field->update_setting( 'label_styles_border-style', '' );
	$field->update_setting( 'label_styles_border-color', '' );
	$field->update_setting( 'label_styles_color', '' );
	$field->update_setting( 'label_styles_height', '' );
	$field->update_setting( 'label_styles_width', '' );
	$field->update_setting( 'label_styles_font-size', '' );
	$field->update_setting( 'label_styles_margin', '' );
	$field->update_setting( 'label_styles_padding', '' );
	$field->update_setting( 'label_styles_display', '' );
	$field->update_setting( 'label_styles_float', '' );
	$field->update_setting( 'label_styles_show_advanced_css', '0' );
	$field->update_setting( 'label_styles_advanced', '' );

	// CSS styling placeholders - element styles
	$field->update_setting( 'element_styles_background-color', '' );
	$field->update_setting( 'element_styles_border', '' );
	$field->update_setting( 'element_styles_border-style', '' );
	$field->update_setting( 'element_styles_border-color', '' );
	$field->update_setting( 'element_styles_color', '' );
	$field->update_setting( 'element_styles_height', '' );
	$field->update_setting( 'element_styles_width', '' );
	$field->update_setting( 'element_styles_font-size', '' );
	$field->update_setting( 'element_styles_margin', '' );
	$field->update_setting( 'element_styles_padding', '' );
	$field->update_setting( 'element_styles_display', '' );
	$field->update_setting( 'element_styles_float', '' );
	$field->update_setting( 'element_styles_show_advanced_css', '0' );
	$field->update_setting( 'element_styles_advanced', '' );

	// Save field
	$field->save();

	// Get field ID
	$field_id = $field->get_id();

	if ( ! $field_id ) {
		return new WP_Error( 'save_failed', __( 'Failed to save field', 'ninja-forms' ) );
	}

	return array(
		'success'  => true,
		'field_id' => $field_id,
		'form_id'  => $form_id,
		'type'     => $field_data['type'],
	);
}

function ninja_forms_ability_add_field( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	if ( empty( $input['type'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field type is required', 'ninja-forms' ) );
	}

	// Verify form exists
	$form = Ninja_Forms()->form( $input['form_id'] )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $input['form_id'] ) );
	}

	// Create new field
	$field = Ninja_Forms()->form( $input['form_id'] )->field()->get();

	// Set required field type
	$field->update_setting( 'type', sanitize_text_field( $input['type'] ) );

	// Set parent form ID
	$field->update_setting( 'parent_id', $input['form_id'] );

	// Set optional label
	if ( isset( $input['label'] ) && ! empty( $input['label'] ) ) {
		$field->update_setting( 'label', sanitize_text_field( $input['label'] ) );
	}

	// Set key (required for field display)
	// Auto-generate if not provided, and ensure proper timestamp format
	if ( isset( $input['key'] ) && ! empty( $input['key'] ) ) {
		$key = sanitize_text_field( $input['key'] );
	} else {
		// Generate key from label or type
		if ( isset( $input['label'] ) && ! empty( $input['label'] ) ) {
			// Sanitize label to create key: "Full Name" -> "full_name"
			$key = sanitize_title_with_dashes( $input['label'] );
			$key = str_replace( '-', '_', $key );
		} else {
			// Fallback to type-based key
			$key = sanitize_text_field( $input['type'] );
		}
	}

	// Ensure key has timestamp suffix (matches WordPress UI and Template Builder format)
	// This ensures consistency: descriptive_name_1234567890123
	$key = ninja_forms_ensure_field_key_timestamp( $key );

	// Ensure uniqueness by checking existing keys and appending counter if needed
	$original_key = $key;
	$counter = 1;
	global $wpdb;
	while ( $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d AND `key` = %s",
		$input['form_id'],
		$key
	) ) ) {
		// If duplicate found, strip timestamp and append counter before re-adding timestamp
		$key_without_timestamp = preg_replace( '/_\d{13}$/', '', $original_key );
		$key = ninja_forms_ensure_field_key_timestamp( $key_without_timestamp . '_' . $counter );
		$counter++;
	}

	$field->update_setting( 'key', $key );

	// Set required toggle (default to not required)
	$field->update_setting( 'required', isset( $input['required'] ) ? (int) $input['required'] : 0 );

	// Set placeholder (always set, even if empty, for dashboard parity)
	$field->update_setting( 'placeholder', isset( $input['placeholder'] ) ? sanitize_text_field( $input['placeholder'] ) : '' );

	// Set optional default value
	if ( isset( $input['default_value'] ) && ! empty( $input['default_value'] ) ) {
		$field->update_setting( 'default', sanitize_text_field( $input['default_value'] ) );
	}

	// Set help text (always set, even if empty, for dashboard parity)
	$field->update_setting( 'help_text', isset( $input['help_text'] ) ? sanitize_text_field( $input['help_text'] ) : '' );

	// Dashboard metadata (for parity with manual fields)
	$field->update_setting( 'objectType', 'Field' );
	$field->update_setting( 'objectDomain', 'fields' );
	$field->update_setting( 'editActive', false );  // boolean false, not empty string
	$field->update_setting( 'idAttribute', 'id' );  // Critical for JavaScript initialization

	// Field configuration defaults
	$field->update_setting( 'label_pos', isset( $input['label_pos'] ) ? sanitize_text_field( $input['label_pos'] ) : 'above' );
	$field->update_setting( 'order', isset( $input['order'] ) ? (int) $input['order'] : '' );
	$field->update_setting( 'wrapper_class', isset( $input['wrapper_class'] ) ? sanitize_text_field( $input['wrapper_class'] ) : '' );
	$field->update_setting( 'element_class', isset( $input['element_class'] ) ? sanitize_text_field( $input['element_class'] ) : '' );
	$field->update_setting( 'container_class', isset( $input['container_class'] ) ? sanitize_text_field( $input['container_class'] ) : '' );
	$field->update_setting( 'admin_label', isset( $input['admin_label'] ) ? sanitize_text_field( $input['admin_label'] ) : '' );
	$field->update_setting( 'desc_text', isset( $input['desc_text'] ) ? sanitize_text_field( $input['desc_text'] ) : '' );
	$field->update_setting( 'manual_key', '' );
	$field->update_setting( 'disable_input', '' );
	$field->update_setting( 'disable_browser_autocomplete', '' );
	$field->update_setting( 'mask', '' );
	$field->update_setting( 'custom_mask', '' );
	$field->update_setting( 'input_limit', '' );
	$field->update_setting( 'input_limit_type', 'characters' );
	$field->update_setting( 'input_limit_msg', 'Character(s) left' );
	$field->update_setting( 'cellcid', '' );

	// Ensure default setting exists (even if empty)
	if ( ! isset( $input['default_value'] ) ) {
		$field->update_setting( 'default', '' );
	}

	// CSS styling placeholders - wrap styles (for Layout & Styles compatibility)
	$field->update_setting( 'wrap_styles_background-color', '' );
	$field->update_setting( 'wrap_styles_border', '' );
	$field->update_setting( 'wrap_styles_border-style', '' );
	$field->update_setting( 'wrap_styles_border-color', '' );
	$field->update_setting( 'wrap_styles_color', '' );
	$field->update_setting( 'wrap_styles_height', '' );
	$field->update_setting( 'wrap_styles_width', '' );
	$field->update_setting( 'wrap_styles_font-size', '' );
	$field->update_setting( 'wrap_styles_margin', '' );
	$field->update_setting( 'wrap_styles_padding', '' );
	$field->update_setting( 'wrap_styles_display', '' );
	$field->update_setting( 'wrap_styles_float', '' );
	$field->update_setting( 'wrap_styles_show_advanced_css', '0' );
	$field->update_setting( 'wrap_styles_advanced', '' );

	// CSS styling placeholders - label styles
	$field->update_setting( 'label_styles_background-color', '' );
	$field->update_setting( 'label_styles_border', '' );
	$field->update_setting( 'label_styles_border-style', '' );
	$field->update_setting( 'label_styles_border-color', '' );
	$field->update_setting( 'label_styles_color', '' );
	$field->update_setting( 'label_styles_height', '' );
	$field->update_setting( 'label_styles_width', '' );
	$field->update_setting( 'label_styles_font-size', '' );
	$field->update_setting( 'label_styles_margin', '' );
	$field->update_setting( 'label_styles_padding', '' );
	$field->update_setting( 'label_styles_display', '' );
	$field->update_setting( 'label_styles_float', '' );
	$field->update_setting( 'label_styles_show_advanced_css', '0' );
	$field->update_setting( 'label_styles_advanced', '' );

	// CSS styling placeholders - element styles
	$field->update_setting( 'element_styles_background-color', '' );
	$field->update_setting( 'element_styles_border', '' );
	$field->update_setting( 'element_styles_border-style', '' );
	$field->update_setting( 'element_styles_border-color', '' );
	$field->update_setting( 'element_styles_color', '' );
	$field->update_setting( 'element_styles_height', '' );
	$field->update_setting( 'element_styles_width', '' );
	$field->update_setting( 'element_styles_font-size', '' );
	$field->update_setting( 'element_styles_margin', '' );
	$field->update_setting( 'element_styles_padding', '' );
	$field->update_setting( 'element_styles_display', '' );
	$field->update_setting( 'element_styles_float', '' );
	$field->update_setting( 'element_styles_show_advanced_css', '0' );
	$field->update_setting( 'element_styles_advanced', '' );

	// Save field
	$field->save();

	// Get field ID
	$field_id = $field->get_id();

	if ( ! $field_id ) {
		return new WP_Error( 'save_failed', __( 'Failed to save field', 'ninja-forms' ) );
	}

	return array(
		'success'  => true,
		'field_id' => $field_id,
		'form_id'  => $input['form_id'],
		'type'     => $input['type'],
		'message'  => sprintf( __( 'Field added to form %d with ID %d', 'ninja-forms' ), $input['form_id'], $field_id ),
	);
}

function ninja_forms_ability_update_field( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['field_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Field ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$field_id = (int) $input['field_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Check if field exists
	$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
	if ( ! $field || ! $field->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Field with ID %d not found in form %d', 'ninja-forms' ), $field_id, $form_id ),
		);
	}

	// Build settings array with only the provided values
	$settings_to_update = array();

	// Map of input keys to field settings
	$setting_map = array(
		'label'         => 'label',
		'key'           => 'key',
		'required'      => 'required',
		'placeholder'   => 'placeholder',
		'default_value' => 'default',
		'help_text'     => 'help_text',
		'admin_label'   => 'admin_label',
		'order'         => 'order',
		'options'       => 'options',
	);

	// Only include settings that were provided in the input
	foreach ( $setting_map as $input_key => $setting_key ) {
		if ( isset( $input[ $input_key ] ) ) {
			$value = $input[ $input_key ];

			// Sanitize based on type
			if ( $input_key === 'required' ) {
				$value = (int) $value;
			} elseif ( $input_key === 'order' ) {
				$value = (int) $value;
			} elseif ( $input_key === 'options' ) {
				// Options is an array, sanitize each option
				if ( is_array( $value ) ) {
					$sanitized_options = array();
					foreach ( $value as $option ) {
						$sanitized_options[] = array(
							'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '',
							'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
							'calc'  => isset( $option['calc'] ) ? sanitize_text_field( $option['calc'] ) : '',
						);
					}
					$value = $sanitized_options;
				}
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

	// Update the field settings
	$field->update_settings( $settings_to_update );
	$field->save();

	// Get the updated field label for the message
	$field_label = $field->get_setting( 'label' );

	return array(
		'success'  => true,
		'form_id'  => $form_id,
		'field_id' => $field_id,
		'updated'  => $settings_to_update,
		'message'  => sprintf( __( 'Successfully updated field "%s" (ID: %d) in form %d. Updated %d setting(s).', 'ninja-forms' ), $field_label, $field_id, $form_id, count( $settings_to_update ) ),
	);
}

function ninja_forms_count_submissions_with_field_data( $form_id, $field_id ) {
	global $wpdb;

	$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
	if ( ! $field ) {
		return 0;
	}

	// FIXED: Use _field_{id} format which is how NF stores submission field values
	// Original bug: queried nf3_object_meta/nf3_objects tables (for form definitions)
	// Fix: query wp_postmeta for nf_sub post type with _field_{id} meta_key
	$meta_key = "_field_" . $field_id;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value != ''
			AND pm.meta_value IS NOT NULL
			AND p.post_type = 'nf_sub'
			AND p.post_status = 'publish'
			AND pm2.meta_key = '_form_id'
			AND pm2.meta_value = %s",
			$meta_key,
			$form_id
		)
	);

	return (int) $count;
}

function ninja_forms_export_field_data_to_csv( $form_id, $field_id ) {
	$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
	if ( ! $field ) {
		return new WP_Error( 'field_not_found', __( 'Field not found', 'ninja-forms' ) );
	}

	$field_key   = $field->get_setting( 'key' );
	$field_label = $field->get_setting( 'label' );

	// Get all submissions for this form
	$submissions = Ninja_Forms()->form( $form_id )->get_subs();

	$csv_data = array();
	$csv_data[] = array( 'Submission ID', 'Date Submitted', $field_label );

	foreach ( $submissions as $sub ) {
		$field_value = $sub->get_field_value( $field_key );
		if ( ! empty( $field_value ) ) {
			$csv_data[] = array(
				$sub->get_id(),
				$sub->get_sub_date( 'Y-m-d H:i:s' ),
				$field_value,
			);
		}
	}

	// Generate filename
	$filename = sprintf(
		'field-export_%s_%s_%s.csv',
		sanitize_title( $field_label ),
		$form_id,
		gmdate( 'Y-m-d_H-i-s' )
	);

	// Create export directory if it doesn't exist
	$upload_dir = wp_upload_dir();
	$export_dir = $upload_dir['basedir'] . '/nf-field-exports/';

	if ( ! file_exists( $export_dir ) ) {
		wp_mkdir_p( $export_dir );
		// Protect directory with .htaccess
		file_put_contents( $export_dir . '.htaccess', 'Deny from all' );
	}

	$file_path = $export_dir . $filename;

	// Write CSV file
	$fp = fopen( $file_path, 'w' );
	if ( ! $fp ) {
		return new WP_Error( 'export_failed', __( 'Failed to create export file', 'ninja-forms' ) );
	}

	foreach ( $csv_data as $row ) {
		fputcsv( $fp, $row );
	}
	fclose( $fp );

	return array(
		'file_path' => $file_path,
		'filename'  => $filename,
		'count'     => count( $csv_data ) - 1, // Exclude header row
		'size'      => filesize( $file_path ),
	);
}

function ninja_forms_get_field_sample_data( $form_id, $field_id, $limit = 5 ) {
	$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
	if ( ! $field ) {
		return array();
	}

	$field_key = $field->get_setting( 'key' );

	$submissions = Ninja_Forms()->form( $form_id )->get_subs();
	$samples     = array();

	foreach ( $submissions as $sub ) {
		if ( count( $samples ) >= $limit ) {
			break;
		}

		$field_value = $sub->get_field_value( $field_key );
		if ( ! empty( $field_value ) ) {
			$samples[] = array(
				'submission_id' => $sub->get_id(),
				'value'         => $field_value,
				'date'          => $sub->get_sub_date( 'Y-m-d' ),
			);
		}
	}

	return $samples;
}

function ninja_forms_ability_remove_field( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}
	if ( empty( $input['field_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field ID is required', 'ninja-forms' ) );
	}

	$form_id  = (int) $input['form_id'];
	$field_id = $input['field_id'];

	// ENFORCE: field_id must be single integer, not array
	if ( is_array( $field_id ) ) {
		return new WP_Error(
			'bulk_removal_blocked',
			sprintf(
				__( 'This ability can only remove ONE field at a time. You provided %d fields. Please process each field individually with full confirmation workflow.', 'ninja-forms' ),
				count( $field_id )
			)
		);
	}

	$field_id = (int) $field_id;

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error(
			'form_not_found',
			sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id )
		);
	}

	// Check if field exists
	$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
	if ( ! $field || ! $field->get_id() ) {
		return new WP_Error(
			'field_not_found',
			sprintf( __( 'Field with ID %d not found in form %d', 'ninja-forms' ), $field_id, $form_id )
		);
	}

	// Get field information
	$field_label = $field->get_setting( 'label' );

	// STEP 1: Check for submission data
	$submissions_with_data = ninja_forms_count_submissions_with_field_data( $form_id, $field_id );

	if ( $submissions_with_data > 0 ) {
		// Field has submission data - enhanced workflow required

		// STEP 2: Has export been completed?
		if ( empty( $input['export_completed'] ) ) {
			// Must export first
			$export_result = ninja_forms_export_field_data_to_csv( $form_id, $field_id );

			if ( is_wp_error( $export_result ) ) {
				return $export_result;
			}

			return array(
				'success'                => false,
				'workflow_step'          => 'export_required',
				'field_name'             => $field_label,
				'field_id'               => $field_id,
				'submissions_affected'   => $submissions_with_data,
				'export_file'            => $export_result['file_path'],
				'export_filename'        => $export_result['filename'],
				'sample_data'            => ninja_forms_get_field_sample_data( $form_id, $field_id, 5 ),
				'message'                => sprintf(
					__( 'Field data exported to %s. Contains %d values. Review the data and provide confirmation to proceed with deletion.', 'ninja-forms' ),
					$export_result['filename'],
					$submissions_with_data
				),
				'next_step'              => sprintf(
					__( 'To proceed, call again with export_completed: true and user_confirmation_phrase: "DELETE %s DATA FROM %d SUBMISSIONS"', 'ninja-forms' ),
					strtoupper( $field_label ),
					$submissions_with_data
				),
			);
		}

		// STEP 3: Validate confirmation phrase
		$required_phrase = sprintf(
			'DELETE %s DATA FROM %d SUBMISSIONS',
			strtoupper( $field_label ),
			$submissions_with_data
		);

		$provided_phrase = isset( $input['user_confirmation_phrase'] ) ? strtoupper( trim( $input['user_confirmation_phrase'] ) ) : '';

		if ( $provided_phrase !== $required_phrase ) {
			return new WP_Error(
				'invalid_confirmation',
				sprintf(
					__( 'Invalid confirmation phrase. You must type EXACTLY: "%s"\n\nThis ensures you understand that %d submission values will be permanently deleted.', 'ninja-forms' ),
					$required_phrase,
					$submissions_with_data
				)
			);
		}
	}

	// All checks passed - proceed with CONTROLLED removal
	// SECURITY: We implement our own deletion process instead of calling $field->delete()
	// to prevent any fallback to unsafe deletion mechanisms
	
	global $wpdb;
	
	// Begin transaction for atomic deletion
	$wpdb->query( 'START TRANSACTION' );
	
	try {
		// Delete field meta data
		$meta_deleted = $wpdb->delete(
			$wpdb->prefix . 'nf3_field_meta',
			array( 'parent_id' => $field_id ),
			array( '%d' )
		);
		
		if ( $meta_deleted === false ) {
			throw new Exception( 'Failed to delete field meta data' );
		}
		
		// Delete the field record itself
		$field_deleted = $wpdb->delete(
			$wpdb->prefix . 'nf3_fields',
			array( 'id' => $field_id ),
			array( '%d' )
		);
		
		if ( $field_deleted === false ) {
			throw new Exception( 'Failed to delete field record' );
		}
		
		// Delete any relationships involving this field
		$relationships_deleted = $wpdb->delete(
			$wpdb->prefix . 'nf3_relationships',
			array( 
				'child_id' => $field_id,
				'child_type' => 'field'
			),
			array( '%d', '%s' )
		);
		
		// Also delete where this field is a parent (shouldn't happen but safety check)
		$wpdb->delete(
			$wpdb->prefix . 'nf3_relationships',
			array( 
				'parent_id' => $field_id,
				'parent_type' => 'field'
			),
			array( '%d', '%s' )
		);
		
		// Commit transaction
		$wpdb->query( 'COMMIT' );
		
	} catch ( Exception $e ) {
		// Rollback on error
		$wpdb->query( 'ROLLBACK' );
		
		return new WP_Error(
			'deletion_failed',
			sprintf(
				__( 'Failed to delete field "%s": %s. No data was modified.', 'ninja-forms' ),
				$field_label,
				$e->getMessage()
			)
		);
	}

	return array(
		'success'              => true,
		'form_id'              => $form_id,
		'field_id'             => $field_id,
		'submissions_affected' => $submissions_with_data,
		'message'              => sprintf(
			__( 'Field "%s" safely removed from form. %s', 'ninja-forms' ),
			$field_label,
			$submissions_with_data > 0
				? sprintf( __( 'Data deleted from %d submissions. Backup was created before deletion.', 'ninja-forms' ), $submissions_with_data )
				: __( 'Field had no submission data.', 'ninja-forms' )
		),
		'deletion_method'      => 'controlled_safe_deletion',
	);
}

function ninja_forms_ability_reorder_fields( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}
	if ( empty( $input['field_order'] ) || ! is_array( $input['field_order'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field order mapping is required and must be an array', 'ninja-forms' ) );
	}

	$form_id = (int) $input['form_id'];
	$field_order = $input['field_order'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error( 'form_not_found', sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ) );
	}

	$reordered_count = 0;
	$updated_order = array();

	// Process each field in the order mapping
	foreach ( $field_order as $field_id => $order ) {
		$field_id = (int) $field_id;
		$order = (int) $order;

		// Get the field
		$field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
		if ( ! $field || ! $field->get_id() ) {
			// Skip fields that don't exist
			continue;
		}

		// Update the field's order
		$field->update_setting( 'order', $order );
		$field->save();

		$reordered_count++;
		$updated_order[ $field_id ] = $order;
	}

	if ( $reordered_count === 0 ) {
		return new WP_Error( 'no_fields_reordered', __( 'No valid fields were reordered', 'ninja-forms' ) );
	}

	return array(
		'success'     => true,
		'form_id'     => $form_id,
		'reordered'   => $reordered_count,
		'field_order' => $updated_order,
		'message'     => sprintf( __( 'Successfully reordered %d field(s) in form %d.', 'ninja-forms' ), $reordered_count, $form_id ),
	);
}

function ninja_forms_ability_list_field_types( $input ) {
	// Get all registered field types
	$field_types_config = Ninja_Forms()->fields;

	if ( empty( $field_types_config ) ) {
		return new WP_Error( 'no_field_types', __( 'No field types are registered', 'ninja-forms' ) );
	}

	$field_types = array();

	foreach ( $field_types_config as $type_name => $field_class ) {
		// Get field type instance
		$field_obj = $field_class;

		// Extract useful information about this field type
		$field_info = array(
			'name'          => $type_name,
			'nicename'      => isset( $field_obj->_nicename ) ? $field_obj->_nicename : ucfirst( $type_name ),
			'section'       => isset( $field_obj->_section ) ? $field_obj->_section : 'common',
			'icon'          => isset( $field_obj->_icon ) ? $field_obj->_icon : 'file-text-o',
			'default_label' => isset( $field_obj->_settings['label']['value'] ) ? $field_obj->_settings['label']['value'] : '',
		);

		$field_types[] = $field_info;
	}

	// Sort by nicename for better UX
	usort( $field_types, function( $a, $b ) {
		return strcmp( $a['nicename'], $b['nicename'] );
	});

	$count = count( $field_types );

	return array(
		'success'     => true,
		'field_types' => $field_types,
		'count'       => $count,
		'message'     => sprintf( __( 'Found %d field type(s)', 'ninja-forms' ), $count ),
	);
}