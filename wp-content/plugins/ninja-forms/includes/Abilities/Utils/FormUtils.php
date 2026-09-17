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

/**
 * Create one action on a form the way an ability must.
 *
 * CRITICAL ACTION CREATION REQUIREMENTS (Session 10 - November 2025):
 * =====================================================================
 * When creating actions, you MUST:
 * 1. Use update_setting() (singular) NOT update_settings() (plural array)
 * 2. EXPLICITLY set 'parent_id' => $form_id for EVERY action
 * 3. Set each property individually with update_setting()
 *
 * WHY THIS MATTERS:
 * - Without explicit parent_id, save actions don't work (submissions not saved)
 * - Forms appear to work (show success message) but data is lost
 * - This was root cause of W3, W6 submission bugs in testing
 * - add-action ability works because it sets parent_id explicitly
 * - Original code used update_settings() array without parent_id = BROKEN
 *
 * VERIFIED FIX (Session 10):
 * - Form 51 tested with explicit parent_id = submissions saved correctly
 * - Form 50 (before fix) = no submissions saved, required delete/recreate
 * =====================================================================
 * This helper carries that invariant for every action create-form makes, so
 * it is enforced — and documented — in exactly one place.
 *
 * @param int    $form_id  Parent form ID.
 * @param string $type     Registered action type.
 * @param string $label    Action label.
 * @param int    $order    Action order.
 * @param array  $settings Optional pre-sanitized extra settings. 'active'
 *                         defaults to 1 and may be overridden here;
 *                         parent_id, type, and order are managed by the
 *                         helper and ignored if passed.
 * @return int|WP_Error Saved action ID, or a persistence error.
 */
function ninja_forms_ability_create_form_action( $form_id, $type, $label, $order, $settings = array() ) {
	$action = Ninja_Forms()->form( $form_id )->action()->get();
	$action->update_setting( 'parent_id', $form_id ); // CRITICAL: Must explicitly set parent_id
	$action->update_setting( 'type', $type );
	$action->update_setting( 'label', $label );
	$action->update_setting( 'active', 1 );
	$action->update_setting( 'order', $order );

	foreach ( $settings as $setting_key => $setting_value ) {
		if ( in_array( $setting_key, array( 'parent_id', 'type', 'order' ), true ) ) {
			continue; // Managed above.
		}
		$action->update_setting( $setting_key, $setting_value );
	}

	$action->save();
	$action_id = (int) $action->get_id();
	if ( ! $action_id ) {
		return new WP_Error(
			'action_save_failed',
			__( 'A generated form action could not be saved.', 'ninja-forms' )
		);
	}

	global $wpdb;
	$stored = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, parent_id, type, label, active
			FROM {$wpdb->prefix}nf3_actions WHERE id = %d AND parent_id = %d",
			$action_id,
			$form_id
		),
		ARRAY_A
	);
	if ( ! is_array( $stored ) || $action_id !== (int) $stored['id'] ) {
		return new WP_Error(
			'action_save_unverified',
			__( 'A generated form action could not be verified after saving.', 'ninja-forms' )
		);
	}
	$meta_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT `key`, `value` FROM {$wpdb->prefix}nf3_action_meta WHERE parent_id = %d",
			$action_id
		),
		ARRAY_A
	);
	$persisted = $stored;
	foreach ( (array) $meta_rows as $meta_row ) {
		$persisted[ $meta_row['key'] ] = maybe_unserialize( $meta_row['value'] );
	}
	$expected = array_merge(
		$settings,
		array(
			'parent_id' => $form_id,
			'type'      => $type,
			'label'     => $label,
			'active'    => isset( $settings['active'] ) ? (int) $settings['active'] : 1,
			'order'     => $order,
		)
	);
	foreach ( $expected as $setting_key => $expected_value ) {
		if (
			! array_key_exists( $setting_key, $persisted )
			|| ! ninja_forms_ability_persisted_value_matches(
				$expected_value,
				$persisted[ $setting_key ]
			)
		) {
			return new WP_Error(
				'action_save_unverified',
				__( 'A generated form action setting could not be verified after saving.', 'ninja-forms' )
			);
		}
	}

	return $action_id;
}

/**
 * Compare normalized persisted settings across scalar database encodings.
 *
 * @param mixed $expected Expected value.
 * @param mixed $actual   Persisted value.
 * @return bool Whether values are equivalent.
 */
function ninja_forms_ability_persisted_value_matches( $expected, $actual ) {
	if ( is_array( $expected ) || is_array( $actual ) ) {
		return $expected === $actual;
	}

	return (string) $expected === (string) $actual;
}

/**
 * Delete a partially generated form and verify no related form remains.
 *
 * @param int      $form_id Generated form ID.
 * @param WP_Error $error   Original creation failure.
 * @return WP_Error Original error, or a cleanup failure.
 */
function ninja_forms_ability_abort_form_creation( $form_id, $error ) {
	$children = ninja_forms_ability_capture_generated_form_children( $form_id );
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( $form && $form->get_id() ) {
		$form->delete();
	}

	$verified = ninja_forms_ability_verify_generated_form_absent( $form_id, $children );
	if ( is_wp_error( $verified ) ) {
		return $verified;
	}

	return $error;
}

/**
 * Capture the child IDs needed to detect orphan metadata after form deletion.
 *
 * @param int $form_id Generated form ID.
 * @return array{fields:array<int>,actions:array<int>} Generated child IDs.
 */
function ninja_forms_ability_capture_generated_form_children( $form_id ) {
	global $wpdb;

	return array(
		'fields'  => array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d",
					$form_id
				)
			)
		),
		'actions' => array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}nf3_actions WHERE parent_id = %d",
					$form_id
				)
			)
		),
	);
}

/**
 * Count durable rows still tied to one deleted child object.
 *
 * @param int    $id        Child object ID.
 * @param string $type      Child type: field or action.
 * @param string $table     Primary table suffix.
 * @param string $meta_table Meta table suffix.
 * @return int Remaining primary, metadata, and relationship rows.
 */
function ninja_forms_ability_count_generated_child_rows( $id, $type, $table, $meta_table ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT
				(SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}{$meta_table} WHERE parent_id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_relationships
					WHERE (parent_id = %d AND parent_type = %s)
						OR (child_id = %d AND child_type = %s))",
			$id,
			$id,
			$id,
			$type,
			$id,
			$type
		)
	);
}

/**
 * Verify that no generated-form rows, child metadata, or relationships remain.
 *
 * @param int   $form_id Generated form ID.
 * @param array $children Captured field and action IDs from before deletion.
 * @return true|WP_Error Exact cleanup result.
 */
function ninja_forms_ability_verify_generated_form_absent( $form_id, $children = array() ) {
	global $wpdb;

	$remaining = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_forms WHERE id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_form_meta WHERE parent_id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_actions WHERE parent_id = %d) +
				(SELECT COUNT(*) FROM {$wpdb->prefix}nf3_relationships
					WHERE (parent_id = %d AND parent_type = 'form')
						OR (child_id = %d AND child_type = 'form'))",
			$form_id,
			$form_id,
			$form_id,
			$form_id,
			$form_id,
			$form_id
		)
	);
	foreach ( (array) ( $children['fields'] ?? array() ) as $field_id ) {
		$remaining += ninja_forms_ability_count_generated_child_rows(
			(int) $field_id,
			'field',
			'nf3_fields',
			'nf3_field_meta'
		);
	}
	foreach ( (array) ( $children['actions'] ?? array() ) as $action_id ) {
		$remaining += ninja_forms_ability_count_generated_child_rows(
			(int) $action_id,
			'action',
			'nf3_actions',
			'nf3_action_meta'
		);
	}
	if ( 0 !== $remaining ) {
		return new WP_Error(
			'form_cleanup_failed',
			__( 'The incomplete generated form could not be removed safely.', 'ninja-forms' )
		);
	}

	return true;
}

/**
 * Normalize one provider-generated action through the public action boundary.
 *
 * The generation schema groups type-specific values beneath `settings`, while
 * the shared action normalizer accepts the public ability's flat input. This
 * adapter permits only the four public action types and maps only their
 * declared settings before returning storage-keyed values.
 *
 * @param array $action_data Provider-generated action definition.
 * @return array|WP_Error Normalized action definition, or validation error.
 */
function ninja_forms_ability_normalize_generated_action( $action_data ) {
	if ( ! is_array( $action_data ) ) {
		return new WP_Error(
			'invalid_action',
			__( 'Each generated action must be an object.', 'ninja-forms' )
		);
	}
	$valid = ninja_forms_ability_validate_input_keys(
		$action_data,
		array( 'type', 'label', 'active', 'settings' )
	);
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$type = isset( $action_data['type'] ) ? sanitize_key( $action_data['type'] ) : '';
	if ( ! ninja_forms_ability_is_supported_action_type( $type ) ) {
		return new WP_Error(
			'unsupported_action_type',
			__( 'That action type is not supported.', 'ninja-forms' )
		);
	}
	$settings = isset( $action_data['settings'] ) ? $action_data['settings'] : array();
	if ( ! is_array( $settings ) ) {
		return new WP_Error(
			'invalid_action_settings',
			__( 'Generated action settings must be an object.', 'ninja-forms' )
		);
	}

	$setting_map = array(
		'email'          => array(
			'to'            => 'to',
			'email_subject' => 'subject',
			'email_message' => 'message',
			'email_format'  => 'email_format',
			'from_name'     => 'from_name',
			'from_address'  => 'from_address',
			'reply_to'      => 'reply_to',
			'cc'            => 'cc',
			'bcc'           => 'bcc',
		),
		'redirect'       => array( 'redirect_url' => 'redirect_url' ),
		'successmessage' => array( 'success_msg' => 'success_msg' ),
		'save'           => array(),
	);
	$valid = ninja_forms_ability_validate_input_keys(
		$settings,
		array_keys( $setting_map[ $type ] )
	);
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$flat = array( 'type' => $type );
	foreach ( array( 'label', 'active' ) as $key ) {
		if ( array_key_exists( $key, $action_data ) ) {
			$flat[ $key ] = $action_data[ $key ];
		}
	}
	foreach ( $setting_map[ $type ] as $stored_key => $public_key ) {
		if ( array_key_exists( $stored_key, $settings ) ) {
			$flat[ $public_key ] = $settings[ $stored_key ];
		}
	}

	$normalized = ninja_forms_ability_normalize_action_input( $flat, '', 'add' );
	if ( is_wp_error( $normalized ) ) {
		return $normalized;
	}

	/*
	 * Fall back to the same human names the guaranteed actions use. ucfirst on
	 * the type alone reads as "Successmessage" in the administrator's actions
	 * list, which is where these names are seen.
	 */
	$default_labels = array(
		'email'          => __( 'Email', 'ninja-forms' ),
		'redirect'       => __( 'Redirect', 'ninja-forms' ),
		'successmessage' => __( 'Success Message', 'ninja-forms' ),
		'save'           => __( 'Record Submission', 'ninja-forms' ),
	);
	$default_label = isset( $default_labels[ $type ] ) ? $default_labels[ $type ] : ucfirst( $type );

	$result = array(
		'type'     => $normalized['type'],
		'label'    => isset( $normalized['label'] ) ? $normalized['label'] : $default_label,
		'active'   => isset( $normalized['active'] ) ? $normalized['active'] : 1,
		'settings' => array(),
	);
	foreach ( $setting_map[ $type ] as $stored_key => $public_key ) {
		if ( array_key_exists( $public_key, $normalized ) ) {
			$result['settings'][ $stored_key ] = $normalized[ $public_key ];
		}
	}

	return $result;
}

/**
 * Execute callback for the ninjaforms/create-form ability.
 *
 * Creates a form with its fields and actions in one call, resilient to
 * imperfect generated input: unknown field types are coerced to textbox and
 * unknown action types are skipped, so one bad entry never fails the whole
 * generation. A submit button field is always appended, and the saved form
 * is always given a save (Record Submission) action and a success-message
 * action even when the provided actions omit them; with no actions at all,
 * the dashboard Blank Form defaults (success message, admin email, save)
 * are created instead.
 *
 * @param array $input {
 *     Ability input, validated against the create-form schema.
 *
 *     @type string $title           Required. Form title.
 *     @type array  $fields          Optional. Field definitions, each passed to
 *                                   ninja_forms_ability_add_field_internal()
 *                                   (see it for the per-field contract).
 *     @type array  $actions         Optional. Action definitions (type, label,
 *                                   active, settings). Types not registered on
 *                                   this install are skipped.
 *     @type string $success_message Optional. Body for the guaranteed
 *                                   success-message action.
 *     @type string $submit_label    Optional. Submit button label.
 *                                   Plus optional display/behavior settings
 *                                   (show_title, default_label_pos,
 *                                   sub_limit_number, honeypot_enabled, …).
 * }
 * @return array|WP_Error [ success, form_id, field_count, message ], or
 *                        WP_Error when the title is missing.
 */
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
	$form_id = (int) $form->get_id();
	if ( ! $form_id ) {
		return new WP_Error(
			'form_save_failed',
			__( 'The generated form could not be saved.', 'ninja-forms' )
		);
	}
	global $wpdb;
	$stored_title = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT title FROM {$wpdb->prefix}nf3_forms WHERE id = %d",
			$form_id
		)
	);
	if ( (string) $settings['title'] !== (string) $stored_title ) {
		return ninja_forms_ability_abort_form_creation(
			$form_id,
			new WP_Error(
				'form_save_unverified',
				__( 'The generated form settings could not be verified after saving.', 'ninja-forms' )
			)
		);
	}

	// Process fields if provided
	$fields_created = array();
	if ( isset( $input['fields'] ) && is_array( $input['fields'] ) ) {
		$next_order = 0;
		foreach ( $input['fields'] as $field_data ) {
			/*
			 * Order carries the caller's array sequence when it says nothing.
			 * Without this every field persists at the same order, and the
			 * one-based insertion positions add-field documents cannot hold:
			 * a field inserted at position 2 sorts after every existing one.
			 */
			++$next_order;
			if ( ! isset( $field_data['order'] ) || '' === $field_data['order'] ) {
				$field_data['order'] = $next_order;
			} else {
				$next_order = max( $next_order, (int) $field_data['order'] );
			}
			$field_result = ninja_forms_ability_add_field_internal( $form_id, $field_data );
			if ( is_wp_error( $field_result ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $field_result );
			}
			$fields_created[] = $field_result['field_id'];
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
	if ( is_wp_error( $submit_result ) ) {
		return ninja_forms_ability_abort_form_creation( $form_id, $submit_result );
	}
	$fields_created[] = $submit_result['field_id'];

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
				if ( ! $field->get_id() ) {
					return ninja_forms_ability_abort_form_creation(
						$form_id,
						new WP_Error(
							'field_save_failed',
							__( 'A generated HTML field could not be finalized.', 'ninja-forms' )
						)
					);
				}
			}
		}
	}

	// Process actions — every creation path goes through
	// ninja_forms_ability_create_form_action(), which carries the critical
	// parent_id-via-update_setting() invariant.
	$success_message = isset( $input['success_message'] )
		? sanitize_text_field( $input['success_message'] )
		: __( 'Your form has been successfully submitted.', 'ninja-forms' );

	if ( isset( $input['actions'] ) && is_array( $input['actions'] ) ) {
		// Custom actions provided - create those.
		$order         = 1;
		$created_types = array();

		foreach ( $input['actions'] as $action_data ) {
			$action_data = ninja_forms_ability_normalize_generated_action( $action_data );
			if ( is_wp_error( $action_data ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $action_data );
			}

			$action_type = $action_data['type'];
			$settings    = array_merge(
				array( 'active' => (int) $action_data['active'] ),
				$action_data['settings']
			);

			$action_result = ninja_forms_ability_create_form_action(
				$form_id,
				$action_type,
				$action_data['label'],
				$order++,
				$settings
			);
			if ( is_wp_error( $action_result ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $action_result );
			}
			$created_types[] = $action_type;
		}

		// Every form needs its submissions recorded and a success message -
		// guarantee both even when the provided actions omit them.
		if ( ! in_array( 'save', $created_types, true ) ) {
			$action_result = ninja_forms_ability_create_form_action( $form_id, 'save', __( 'Record Submission', 'ninja-forms' ), $order++ );
			if ( is_wp_error( $action_result ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $action_result );
			}
		}

		if ( ! in_array( 'successmessage', $created_types, true ) ) {
			$action_result = ninja_forms_ability_create_form_action( $form_id, 'successmessage', __( 'Success Message', 'ninja-forms' ), $order++, array(
				'success_msg' => $success_message, // CRITICAL: Use 'success_msg' not 'message'
			) );
			if ( is_wp_error( $action_result ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $action_result );
			}
		}
	} else {
		// No custom actions - add default actions that match dashboard "Blank Form" behavior

		// Action 1: Success Message
		$default_action_results = array();
		$default_action_results[] = ninja_forms_ability_create_form_action( $form_id, 'successmessage', __( 'Success Message', 'ninja-forms' ), 1, array(
			'success_msg' => $success_message, // CRITICAL: Use 'success_msg' not 'message'
		) );

		// Action 2: Admin Email
		$default_action_results[] = ninja_forms_ability_create_form_action( $form_id, 'email', __( 'Admin Email', 'ninja-forms' ), 2, array(
			'to'            => isset( $input['admin_email_to'] ) ? sanitize_text_field( $input['admin_email_to'] ) : '{wp:admin_email}',
			'email_subject' => isset( $input['admin_email_subject'] ) ? sanitize_text_field( $input['admin_email_subject'] ) : __( 'Ninja Forms Submission', 'ninja-forms' ),
			'email_message' => '{fields_table}',
			'email_format'  => 'html',
			'reply_to'      => '',
			'from_name'     => '',
			'from_address'  => '',
			'cc'            => '',
			'bcc'           => '',
		) );

		// Action 3: Record Submission
		$default_action_results[] = ninja_forms_ability_create_form_action( $form_id, 'save', __( 'Record Submission', 'ninja-forms' ), 3 );
		foreach ( $default_action_results as $action_result ) {
			if ( is_wp_error( $action_result ) ) {
				return ninja_forms_ability_abort_form_creation( $form_id, $action_result );
			}
		}
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

/**
 * Get a form and its requested related data.
 *
 * @param array $input Ability input.
 * @return array|WP_Error Form result.
 */
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
		'settings'   => ninja_forms_ability_project_form_settings( $form->get_settings() ),
	);

	// Include fields if requested (default: true)
	$include_fields = isset( $input['include_fields'] ) ? (bool) $input['include_fields'] : true;
	if ( $include_fields ) {
		// Use factory pattern to get fields
		$fields = Ninja_Forms()->form( $form_id )->get_fields();
		$fields_data = array();

		foreach ( $fields as $field ) {
			$field_settings = ninja_forms_ability_project_field( $field->get_settings() );
			$fields_data[] = array(
				'id'       => $field->get_id(),
				'type'     => $field->get_setting( 'type' ),
				'label'    => $field->get_setting( 'label' ),
				'key'      => $field->get_setting( 'key' ),
				'order'    => $field->get_setting( 'order' ),
				'required' => $field->get_setting( 'required' ),
				'settings' => $field_settings,
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
			$action_settings = $action->get_settings();
			$action_settings['id'] = $action->get_id();
			foreach ( array( 'type', 'label', 'active', 'order' ) as $setting ) {
				$action_settings[ $setting ] = $action->get_setting( $setting );
			}
			$actions_data[] = ninja_forms_ability_project_action( $action_settings );
		}

		$form_data['actions'] = $actions_data;
		$form_data['action_count'] = count( $actions_data );
	}

	// Include calculations if requested (default: true)
	$include_calculations = isset( $input['include_calculations'] ) ? (bool) $input['include_calculations'] : true;
	if ( $include_calculations ) {
		$calculations = $form->get_setting( 'calculations' );
		$form_data['calculations'] = is_array( $calculations )
			? ninja_forms_ability_project_calculations( $calculations )
			: array();
		$form_data['calculation_count'] = count( $form_data['calculations'] );
	}

	return array(
		'success' => true,
		'form'    => $form_data,
		'message' => sprintf( __( 'Retrieved form "%s" (ID: %d)', 'ninja-forms' ), $form_record['title'], $form_id ),
	);
}

/**
 * Update form settings.
 *
 * @param array $input Ability input.
 * @return array|WP_Error Form update result.
 */
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
	$input = ninja_forms_ability_normalize_form_input( $input );
	if ( is_wp_error( $input ) ) {
		return $input;
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
		if ( array_key_exists( $input_key, $input ) ) {
			$settings_to_update[ $setting_key ] = $input[ $input_key ];
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
				// Verify user can edit this specific post before returning its content.
				// @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
				if ( ! current_user_can( 'edit_post', $existing_page->ID ) ) {
					return array(
						'success' => false,
						'message' => __( 'You do not have permission to edit this post.', 'ninja-forms' ),
					);
				}

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
				// Verify user can create posts of this type (defense in depth).
				// @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
				$post_type_object = get_post_type_object( $post_type );
				$create_cap       = $post_type_object ? $post_type_object->cap->create_posts : 'edit_posts';
				if ( ! current_user_can( $create_cap ) ) {
					return array(
						'success' => false,
						'message' => __( 'You do not have permission to create posts.', 'ninja-forms' ),
					);
				}

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

		// Verify user can edit this specific post before modifying it.
		// @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to edit this post.', 'ninja-forms' ),
			);
		}

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
