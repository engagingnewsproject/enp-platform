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

// Field persistence verification shares FormUtils' persisted-value comparison.
require_once __DIR__ . '/FormUtils.php';

/**
 * Ensure a field key ends with a millisecond timestamp.
 *
 * @param string $key Field key.
 * @return string Timestamped field key.
 */
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

/**
 * Normalize a requested field type against the field registry.
 *
 * Callers (including AI-generated specs and external MCP agents) can request
 * a type Ninja Forms doesn't register. Persisting one produces a broken
 * "unknown" field (a "?" in the builder), so coerce anything unrecognized to
 * a plain textbox — the safe, editable default.
 *
 * @param string $type Requested field type.
 * @return string A registered field type.
 */
function ninja_forms_ability_normalize_field_type( $type ) {
	$type = sanitize_text_field( $type );

	$registered = Ninja_Forms()->fields;
	if ( ! isset( $registered[ $type ] ) ) {
		if ( WP_DEBUG ) {
			error_log( sprintf( 'Ninja Forms: unknown field type "%s" coerced to textbox.', $type ) );
		}
		return 'textbox';
	}

	return $type;
}

/**
 * Apply the schema's conditional field flags shared by both add-field paths.
 *
 * Sets personally_identifiable (the GDPR flag the schema mandates for email,
 * phone, and address fields) and number_of_stars (mandated for starrating
 * fields — stars don't render without it). Each is only written when the
 * definition provides it, and number_of_stars only when numeric.
 *
 * @param object $field      Field model being built.
 * @param array  $field_data Field definition.
 * @return void
 */
function ninja_forms_ability_apply_field_flags( $field, $field_data ) {
	if ( isset( $field_data['personally_identifiable'] ) ) {
		$field->update_setting( 'personally_identifiable', $field_data['personally_identifiable'] ? '1' : '' );
	}

	if ( isset( $field_data['number_of_stars'] ) && is_numeric( $field_data['number_of_stars'] ) ) {
		$field->update_setting( 'number_of_stars', (int) $field_data['number_of_stars'] );
	}
}

/**
 * Create one field on a form — the shared worker behind the add-field and
 * create-form abilities.
 *
 * Persists the field with full dashboard parity (auto-generated timestamped
 * key, defaults, styling placeholder settings) so the builder can edit it
 * exactly like a hand-made field.
 *
 * @param int    $form_id         Parent form ID.
 * @param array  $field_data      {
 *     Field definition.
 *
 *     @type string $type                    Required. Types not registered on
 *                                           this install are coerced to
 *                                           textbox rather than persisting a
 *                                           broken field.
 *     @type string $label                   Optional. HTML and hr fields get
 *                                           their UI default labels.
 *     @type string $key                     Optional. Auto-generated from the
 *                                           label or type, timestamped, and
 *                                           de-duplicated per form.
 *     @type int    $required                Optional. Defaults to 0.
 *     @type bool   $personally_identifiable Optional. GDPR flag; the schema
 *                                           mandates it for email, phone, and
 *                                           address fields.
 *     @type int    $number_of_stars         Optional. Star count; the schema
 *                                           mandates it for starrating fields
 *                                           (stars don't render without it).
 *     @type array  $options                 Optional. List-field options as
 *                                           label/value(/calc) rows.
 *     @type string $placeholder             Optional. Also help_text, default
 *                                           (or legacy default_value),
 *                                           label_pos, order, admin_label,
 *                                           desc_text, and the *_class keys.
 * }
 * @param string $insertion_policy Preserve the supplied order or insert while
 *                                 shifting existing fields.
 * @return array|WP_Error [ success, field_id, form_id, type ], or WP_Error
 *                        when the type is missing or the save fails.
 */
function ninja_forms_ability_persist_field( $form_id, $field_data, $insertion_policy = 'preserve' ) {
	// Validate required field type
	if ( empty( $field_data['type'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field type is required', 'ninja-forms' ) );
	}
	$field_data = ninja_forms_ability_normalize_field_input( $field_data, 'add' );
	if ( is_wp_error( $field_data ) ) {
		return $field_data;
	}

	// Coerce unknown types to textbox so we never persist a broken field.
	$field_data['type'] = ninja_forms_ability_normalize_field_type( $field_data['type'] );
	if ( 'insert' === $insertion_policy ) {
		return ninja_forms_ability_insert_field( $form_id, $field_data );
	}

	// Create new field
	$field = Ninja_Forms()->form( $form_id )->field()->get();

	// Set required field type
	$field->update_setting( 'type', $field_data['type'] );

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

	// Conditional schema flags (personally_identifiable, number_of_stars)
	ninja_forms_ability_apply_field_flags( $field, $field_data );

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
	// Match builder-created field data. The canvas also treats value as
	// runtime-only, but storing its initial value prevents legacy renderers
	// from having to backfill a missing setting on load.
	$field->update_setting( 'value', $field->get_setting( 'default' ) );

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

	$verified = ninja_forms_ability_verify_field_persistence(
		$form_id,
		(int) $field_id,
		(int) $field->get_setting( 'order' ),
		array(
			'type'     => (string) $field->get_setting( 'type' ),
			'label'    => (string) $field->get_setting( 'label' ),
			'key'      => (string) $field->get_setting( 'key' ),
			'required' => (int) $field->get_setting( 'required' ),
		)
	);
	if ( is_wp_error( $verified ) ) {
		$verified->add_data( array( 'field_id' => (int) $field_id ) );
		return $verified;
	}

	return array(
		'success'  => true,
		'field_id' => $field_id,
		'form_id'  => $form_id,
		'type'     => $field_data['type'],
	);
}

/**
 * Compatibility entry point for create-form and existing internal callers.
 *
 * @param int   $form_id    Parent form ID.
 * @param array $field_data Field definition.
 * @return array|WP_Error Field result.
 */
function ninja_forms_ability_add_field_internal( $form_id, $field_data ) {
	return ninja_forms_ability_persist_field( $form_id, $field_data, 'preserve' );
}

/**
 * Insert one field and shift existing order values as one transaction.
 *
 * @param int   $form_id    Parent form ID.
 * @param array $field_data Normalized field definition.
 * @return array|WP_Error Field result.
 */
function ninja_forms_ability_insert_field( $form_id, $field_data ) {
	$existing_fields    = array_values( Ninja_Forms()->form( $form_id )->get_fields() );
	$persisted_orders   = ninja_forms_ability_get_field_orders( $form_id );
	$field_types        = array();
	$original_orders    = array();
	$existing_by_id     = array();
	if ( is_wp_error( $persisted_orders ) ) {
		return $persisted_orders;
	}
	foreach ( $existing_fields as $existing_field ) {
		$existing_by_id[ (int) $existing_field->get_id() ] = $existing_field;
	}
	asort( $persisted_orders );
	foreach ( $persisted_orders as $field_id => $persisted_order ) {
		if ( ! isset( $existing_by_id[ $field_id ] ) ) {
			return new WP_Error(
				'field_order_unverified',
				__( 'The existing field order could not be verified before insertion.', 'ninja-forms' )
			);
		}
		$existing_field = $existing_by_id[ $field_id ];
		$field_types[]  = (string) $existing_field->get_setting( 'type' );
		$original_orders[] = array(
			'field' => $existing_field,
			'order' => (int) $persisted_order,
		);
	}
	$requested_order = isset( $field_data['order'] ) ? (int) $field_data['order'] : null;
	$insertion_order = ninja_forms_ability_resolve_field_insertion_order(
		$field_types,
		$requested_order
	);
	$field_data['order'] = $insertion_order;

	global $wpdb;
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error(
			'transaction_failed',
			__( 'The field could not be added safely. No field order was changed.', 'ninja-forms' )
		);
	}

	$expected_orders = array();
	foreach ( $original_orders as $original ) {
		$existing_field  = $original['field'];
		$current_order   = (int) $original['order'];
		$effective_order = $current_order >= $insertion_order
			? $current_order + 1
			: $current_order;
		$existing_field->update_setting( 'order', $effective_order );
		$existing_field->save();
		$field_id = (int) $existing_field->get_id();
		$verified = ninja_forms_ability_verify_field_persistence(
			$form_id,
			$field_id,
			$effective_order
		);
		if ( is_wp_error( $verified ) ) {
			return ninja_forms_ability_rollback_field_insertion(
				$form_id,
				$original_orders,
				$verified
			);
		}
		$expected_orders[ $field_id ] = $effective_order;
	}

	$result = ninja_forms_ability_persist_field( $form_id, $field_data, 'preserve' );
	if ( is_wp_error( $result ) ) {
		$error_data        = $result->get_error_data();
		$inserted_field_id = is_array( $error_data ) && isset( $error_data['field_id'] )
			? (int) $error_data['field_id']
			: 0;
		return ninja_forms_ability_rollback_field_insertion(
			$form_id,
			$original_orders,
			$result,
			$inserted_field_id
		);
	}

	$expected_orders[ (int) $result['field_id'] ] = $insertion_order;
	$verified = ninja_forms_ability_verify_field_order( $form_id, $expected_orders );
	if ( is_wp_error( $verified ) ) {
		return ninja_forms_ability_rollback_field_insertion(
			$form_id,
			$original_orders,
			$verified,
			(int) $result['field_id']
		);
	}

	if ( false === $wpdb->query( 'COMMIT' ) ) {
		return ninja_forms_ability_rollback_field_insertion(
			$form_id,
			$original_orders,
			new WP_Error(
				'save_failed',
				__( 'The field could not be added safely. No field order was changed.', 'ninja-forms' )
			),
			(int) $result['field_id']
		);
	}

	ninja_forms_ability_clear_form_cache( $form_id );
	$result['order']   = $insertion_order;
	$result['message'] = sprintf(
		/* translators: 1: Form ID. 2: Field ID. */
		__( 'Field added to form %1$d with ID %2$d', 'ninja-forms' ),
		$form_id,
		$result['field_id']
	);

	return $result;
}

/**
 * List the field settings the field model persists as table columns.
 *
 * Every other setting is authoritative in the field meta table. The
 * `nf3_fields` table also carries mirror columns (`required`, `order`,
 * `default_value`, `label_pos`, `personally_identifiable`) that only the
 * builder publish path writes, so they are never read back as truth here.
 *
 * @return array<int,string> Settings stored on the field row itself.
 */
function ninja_forms_ability_field_column_settings() {
	return array( 'type', 'label', 'key' );
}

/**
 * Verify one field row and its order setting directly from storage.
 *
 * @param int   $form_id          Parent form ID.
 * @param int   $field_id         Field ID.
 * @param int   $expected_order   Expected persisted order.
 * @param array $expected_settings Required normalized field settings.
 * @return true|WP_Error True when the exact row is durable, otherwise an error.
 */
function ninja_forms_ability_verify_field_persistence( $form_id, $field_id, $expected_order, $expected_settings = array() ) {
	global $wpdb;

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT fields.id, fields.type, fields.label, fields.`key`,
				field_order.meta_value AS field_order
			FROM {$wpdb->prefix}nf3_fields AS fields
			LEFT JOIN {$wpdb->prefix}nf3_field_meta AS field_order
				ON field_order.parent_id = fields.id AND field_order.meta_key = %s
			WHERE fields.id = %d AND fields.parent_id = %d",
			'order',
			$field_id,
			$form_id
		),
		ARRAY_A
	);
	if (
		! is_array( $row )
		|| $field_id !== (int) $row['id']
		|| $expected_order !== (int) maybe_unserialize( $row['field_order'] )
	) {
		return new WP_Error(
			'field_save_unverified',
			__( 'The field could not be verified after saving.', 'ninja-forms' )
		);
	}

	$column_settings = ninja_forms_ability_field_column_settings();
	$meta_settings   = array_diff( array_keys( $expected_settings ), $column_settings );
	$persisted_meta  = ninja_forms_ability_get_field_meta_settings( $field_id, $meta_settings );
	foreach ( $expected_settings as $setting => $expected ) {
		$actual = in_array( $setting, $column_settings, true )
			? maybe_unserialize( $row[ $setting ] ?? null )
			: ( $persisted_meta[ $setting ] ?? null );
		$matches = 'required' === $setting
			? (int) (bool) $expected === (int) (bool) $actual
			: ninja_forms_ability_persisted_value_matches( $expected, $actual );
		if ( ! $matches ) {
			return new WP_Error(
				'field_save_unverified',
				__( 'The field settings could not be verified after saving.', 'ninja-forms' )
			);
		}
	}

	return true;
}

/**
 * Read persisted field settings from the authoritative field meta table.
 *
 * @param int             $field_id Field ID.
 * @param array<int,string> $settings Setting keys to read.
 * @return array<string,mixed> Persisted values keyed by setting.
 */
function ninja_forms_ability_get_field_meta_settings( $field_id, $settings ) {
	$settings = array_values( array_unique( (array) $settings ) );
	if ( empty( $settings ) ) {
		return array();
	}

	global $wpdb;

	$placeholders = implode( ', ', array_fill( 0, count( $settings ), '%s' ) );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are generated, values are bound.
			"SELECT `key`, `value` FROM {$wpdb->prefix}nf3_field_meta
			WHERE parent_id = %d AND `key` IN ({$placeholders})",
			array_merge( array( $field_id ), $settings )
		),
		ARRAY_A
	);
	$persisted = array();
	foreach ( (array) $rows as $meta_row ) {
		$persisted[ $meta_row['key'] ] = maybe_unserialize( $meta_row['value'] );
	}

	return $persisted;
}

/**
 * Verify the exact persisted field IDs and order values for a form.
 *
 * @param int            $form_id         Parent form ID.
 * @param array<int,int> $expected_orders Expected order keyed by field ID.
 * @return true|WP_Error True when storage exactly matches, otherwise an error.
 */
function ninja_forms_ability_verify_field_order( $form_id, $expected_orders ) {
	$actual_orders   = ninja_forms_ability_get_field_orders( $form_id );
	if ( is_wp_error( $actual_orders ) ) {
		return $actual_orders;
	}
	$expected_orders = array_map( 'intval', $expected_orders );
	ksort( $actual_orders );
	ksort( $expected_orders );

	if ( $expected_orders !== $actual_orders ) {
		return new WP_Error(
			'field_order_unverified',
			__( 'The final field order could not be verified.', 'ninja-forms' )
		);
	}

	return true;
}

/**
 * Read the exact persisted field IDs and order values for a form.
 *
 * @param int $form_id Parent form ID.
 * @return array<int,int>|WP_Error Persisted order keyed by field ID, or an ambiguity error.
 */
function ninja_forms_ability_get_field_orders( $form_id ) {
	global $wpdb;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT fields.id, field_order.meta_value AS field_order
			FROM {$wpdb->prefix}nf3_fields AS fields
			LEFT JOIN {$wpdb->prefix}nf3_field_meta AS field_order
				ON field_order.parent_id = fields.id AND field_order.meta_key = %s
			WHERE fields.parent_id = %d",
			'order',
			$form_id
		),
		ARRAY_A
	);
	$orders = array();
	foreach ( (array) $rows as $row ) {
		$field_id = (int) $row['id'];
		if ( isset( $orders[ $field_id ] ) ) {
			return new WP_Error(
				'field_order_unverified',
				__( 'The final field order could not be verified.', 'ninja-forms' )
			);
		}
		$orders[ $field_id ] = (int) maybe_unserialize( $row['field_order'] );
	}

	return $orders;
}

/**
 * Verify original fields after rollback without rejecting concurrent inserts.
 *
 * @param int            $form_id           Parent form ID.
 * @param array<int,int> $expected_orders   Original order keyed by field ID.
 * @param int            $inserted_field_id Field created by this request, if known.
 * @return true|WP_Error True when originals are exact and this request's insert is absent.
 */
function ninja_forms_ability_verify_field_rollback( $form_id, $expected_orders, $inserted_field_id = 0 ) {
	$actual_orders = ninja_forms_ability_get_field_orders( $form_id );
	if ( is_wp_error( $actual_orders ) ) {
		return $actual_orders;
	}
	foreach ( $expected_orders as $field_id => $expected_order ) {
		if (
			! isset( $actual_orders[ $field_id ] )
			|| (int) $expected_order !== (int) $actual_orders[ $field_id ]
		) {
			return new WP_Error(
				'field_order_unverified',
				__( 'The final field order could not be verified.', 'ninja-forms' )
			);
		}
	}
	if ( $inserted_field_id && isset( $actual_orders[ $inserted_field_id ] ) ) {
		return new WP_Error(
			'field_order_unverified',
			__( 'The inserted field could not be removed during rollback.', 'ninja-forms' )
		);
	}
	if ( $inserted_field_id ) {
		global $wpdb;
		$remaining_rows = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT
					(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_field_meta WHERE parent_id = %d) +
					(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_relationships
						WHERE (parent_id = %d AND parent_type = 'field')
							OR (child_id = %d AND child_type = 'field'))",
				$inserted_field_id,
				$inserted_field_id,
				$inserted_field_id
			)
		);
		if ( 0 !== $remaining_rows ) {
			return new WP_Error(
				'field_order_unverified',
				__( 'The inserted field data could not be removed during rollback.', 'ninja-forms' )
			);
		}
	} elseif ( count( $actual_orders ) !== count( $expected_orders ) ) {
		return new WP_Error(
			'field_order_unverified',
			__( 'The field rollback left an unidentified persisted field.', 'ninja-forms' )
		);
	}

	return true;
}

/**
 * Roll back an insertion and restore the in-memory field order snapshot.
 *
 * @param int              $form_id         Parent form ID.
 * @param array<int,array> $original_orders Original field models and orders.
 * @param WP_Error         $error           Failure that caused the rollback.
 * @param int              $inserted_field_id Field created by this request, if known.
 * @return WP_Error Original failure, or a rollback failure.
 */
function ninja_forms_ability_rollback_field_insertion( $form_id, $original_orders, $error, $inserted_field_id = 0 ) {
	global $wpdb;

	$expected_orders = array();
	foreach ( $original_orders as $original ) {
		$original['field']->update_setting( 'order', $original['order'] );
		$expected_orders[ (int) $original['field']->get_id() ] = (int) $original['order'];
	}

	$wpdb->query( 'ROLLBACK' );
	ninja_forms_ability_clear_form_cache( $form_id );
	$verified = ninja_forms_ability_verify_field_rollback(
		$form_id,
		$expected_orders,
		$inserted_field_id
	);
	if ( ! is_wp_error( $verified ) ) {
		return $error;
	}

	// Nontransactional tables can ignore ROLLBACK. Compensate explicitly,
	// then read back the complete original ID/order set before reporting safety.
	if ( $inserted_field_id ) {
		$wpdb->delete(
			$wpdb->prefix . 'nf3_relationships',
			array( 'parent_id' => $inserted_field_id, 'parent_type' => 'field' ),
			array( '%d', '%s' )
		);
		$wpdb->delete(
			$wpdb->prefix . 'nf3_relationships',
			array( 'child_id' => $inserted_field_id, 'child_type' => 'field' ),
			array( '%d', '%s' )
		);
		$wpdb->delete(
			$wpdb->prefix . 'nf3_field_meta',
			array( 'parent_id' => $inserted_field_id ),
			array( '%d' )
		);
		$wpdb->delete(
			$wpdb->prefix . 'nf3_fields',
			array( 'id' => $inserted_field_id, 'parent_id' => $form_id ),
			array( '%d', '%d' )
		);
	}
	foreach ( $original_orders as $original ) {
		$original['field']->update_setting( 'order', $original['order'] );
		$original['field']->save();
	}
	ninja_forms_ability_clear_form_cache( $form_id );
	$verified = ninja_forms_ability_verify_field_rollback(
		$form_id,
		$expected_orders,
		$inserted_field_id
	);
	if ( is_wp_error( $verified ) ) {
		return new WP_Error(
			'transaction_rollback_failed',
			__( 'The field write failed and its database rollback could not be verified.', 'ninja-forms' )
		);
	}

	return $error;
}

/**
 * Clear the cached model factory state for a form after transactional writes.
 *
 * @param int $form_id Form ID.
 * @return void
 */
function ninja_forms_ability_clear_form_cache( $form_id ) {
	if ( class_exists( 'WPN_Helper' ) ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}
}

/**
 * Execute callback for the ninjaforms/add-field ability.
 *
 * Standalone add-field entry point: verifies the target form exists, then
 * persists one field with the same dashboard-parity treatment as
 * ninja_forms_ability_add_field_internal() — including coercing types not
 * registered on this install to textbox instead of saving a broken field.
 *
 * @param array $input {
 *     Ability input, validated against the add-field schema.
 *
 *     @type int    $form_id                 Required. Target form ID.
 *     @type string $type                    Required. Unregistered types are
 *                                           coerced to textbox.
 *     @type bool   $personally_identifiable Optional. GDPR flag; the schema
 *                                           mandates it for email, phone, and
 *                                           address fields.
 *     @type int    $number_of_stars         Optional. Star count; the schema
 *                                           mandates it for starrating fields.
 *     @type string $label                   Optional. Remaining keys (key,
 *                                           required, placeholder, help_text,
 *                                           default, options, label_pos,
 *                                           order, …) follow the same contract
 *                                           as add_field_internal().
 * }
 * @return array|WP_Error [ success, field_id, form_id, type ], or WP_Error
 *                        when form_id/type is missing, the form doesn't
 *                        exist, or the save fails.
 */
function ninja_forms_ability_add_field( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Form ID is required', 'ninja-forms' ) );
	}

	if ( empty( $input['type'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Field type is required', 'ninja-forms' ) );
	}

	$input = ninja_forms_ability_normalize_field_input( $input, 'add' );
	if ( is_wp_error( $input ) ) {
		return $input;
	}

	// Verify form exists
	$form = Ninja_Forms()->form( $input['form_id'] )->get();
	if ( ! $form || ! $form->get_id() ) {
		return new WP_Error(
			'form_not_found',
			sprintf(
				/* translators: %d: Form ID. */
				__( 'Form with ID %1$d not found', 'ninja-forms' ),
				$input['form_id']
			)
		);
	}

	$form_id = (int) $input['form_id'];

	return ninja_forms_ability_persist_field( $form_id, $input, 'insert' );
}

/**
 * Update an existing form field.
 *
 * @param array $input Ability input.
 * @return array|WP_Error Field update result.
 */
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
	$input = ninja_forms_ability_normalize_field_input(
		$input,
		'update',
		(string) $field->get_setting( 'type' )
	);
	if ( is_wp_error( $input ) ) {
		return $input;
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
		if ( array_key_exists( $input_key, $input ) ) {
			$settings_to_update[ $setting_key ] = $input[ $input_key ];
		}
	}

	// If no settings to update, return error
	if ( empty( $settings_to_update ) ) {
		return new WP_Error( 'no_settings', __( 'No settings provided to update', 'ninja-forms' ) );
	}

	// Update the field settings
	$field->update_settings( $settings_to_update );
	if (
		isset( $settings_to_update['default'] )
		&& 'html' === $field->get_setting( 'type' )
	) {
		$field->update_setting( 'value', $settings_to_update['default'] );
	}
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

/**
 * Count published submissions containing data for one field.
 *
 * @param int $form_id  Parent form ID.
 * @param int $field_id Field ID.
 * @return int Matching submission count.
 */
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

/**
 * Export the populated submission values for one field to a protected CSV.
 *
 * @param int $form_id  Parent form ID.
 * @param int $field_id Field ID.
 * @return array|WP_Error Export metadata, or an error when the export fails.
 */
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

/**
 * Get a bounded sample of populated submission values for one field.
 *
 * @param int $form_id  Parent form ID.
 * @param int $field_id Field ID.
 * @param int $limit    Maximum number of samples.
 * @return array<int,array{submission_id:int,value:mixed,date:string}> Sample rows.
 */
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

/**
 * Remove one field through the guarded export-and-confirmation workflow.
 *
 * @param array $input Ability input.
 * @return array|WP_Error Removal result, required workflow step, or an error.
 */
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

/**
 * Apply an explicit order mapping to existing fields on a form.
 *
 * @param array $input Ability input.
 * @return array|WP_Error Reorder result, or an error when no field is updated.
 */
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

/**
 * List field types currently registered with Ninja Forms.
 *
 * @param array $input Unused ability input.
 * @return array|WP_Error Registered field metadata, or an error when none exist.
 */
function ninja_forms_ability_list_field_types( $input ) {
	// Get all registered field types
	$field_types_config = Ninja_Forms()->fields;

	if ( empty( $field_types_config ) ) {
		return new WP_Error( 'no_field_types', __( 'No field types are registered', 'ninja-forms' ) );
	}

	$field_types = array();

	/*
	 * Every property below is protected on NF_Abstract_Field, so reading it
	 * directly always failed isset() from out here and every type fell to the
	 * same defaults: 44 types all reporting section "common", one icon, no
	 * default label, and a nicename derived from the type name rather than the
	 * real one ("Listselect" for what a person calls Select). The accessors
	 * return the declared values.
	 */
	foreach ( $field_types_config as $type_name => $field_obj ) {
		if ( ! is_object( $field_obj ) || ! method_exists( $field_obj, 'get_section' ) ) {
			continue;
		}

		// A section-less type is retired: unreachable in the builder palette and
		// kept only for forms that already contain one. Never offer it.
		if ( ! ninja_forms_ability_field_type_is_selectable( (string) $type_name ) ) {
			continue;
		}

		$settings = method_exists( $field_obj, 'get_settings' ) ? $field_obj->get_settings() : array();

		$field_types[] = array(
			'name'          => $type_name,
			'nicename'      => method_exists( $field_obj, 'get_nicename' ) && '' !== $field_obj->get_nicename()
				? $field_obj->get_nicename()
				: ucfirst( $type_name ),
			'section'       => $field_obj->get_section(),
			'icon'          => method_exists( $field_obj, 'get_icon' ) ? $field_obj->get_icon() : 'file-text-o',
			'default_label' => isset( $settings['label']['value'] ) ? $settings['label']['value'] : '',
		);
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
