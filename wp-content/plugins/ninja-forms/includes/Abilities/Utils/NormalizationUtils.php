<?php
/**
 * Storage-independent validation, normalization, and provider projections.
 *
 * These functions are shared by the persisted Abilities callbacks and the
 * in-memory AI draft adapter. Keeping the rules here prevents an AI edit from
 * accepting data that the public Abilities API would reject or sanitize.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an error when input contains keys outside an ability's contract.
 *
 * @param array $input   Raw ability input.
 * @param array $allowed Allowed top-level keys.
 * @return true|WP_Error
 */
function ninja_forms_ability_validate_input_keys( array $input, array $allowed ) {
	$unknown = array_diff( array_keys( $input ), $allowed );
	if ( empty( $unknown ) ) {
		return true;
	}

	return new WP_Error(
		'unsupported_setting',
		sprintf(
			/* translators: %s: Unsupported setting key. */
			__( 'Unsupported setting: %s', 'ninja-forms' ),
			sanitize_text_field( (string) reset( $unknown ) )
		)
	);
}

/**
 * Normalize list-field options recursively.
 *
 * Only the three keys declared by the ability schema are accepted. This
 * prevents nested provider-supplied data from bypassing top-level checks.
 *
 * @param mixed $options Raw option rows.
 * @return array|WP_Error
 */
function ninja_forms_ability_normalize_options( $options ) {
	if ( ! is_array( $options ) ) {
		return new WP_Error( 'invalid_options', __( 'Field options must be a list.', 'ninja-forms' ) );
	}

	$normalized = array();
	foreach ( $options as $option ) {
		if ( ! is_array( $option ) ) {
			return new WP_Error( 'invalid_option', __( 'Each field option must be an object.', 'ninja-forms' ) );
		}

		$valid = ninja_forms_ability_validate_input_keys( $option, array( 'label', 'value', 'calc' ) );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$row = array(
			'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '',
			'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
		);
		if ( array_key_exists( 'calc', $option ) ) {
			$row['calc'] = sanitize_text_field( $option['calc'] );
		}
		$normalized[] = $row;
	}

	return $normalized;
}

/**
 * Resolve a clamped one-based field insertion position.
 *
 * When no position is requested, insertion defaults immediately before the
 * first Submit field, or to the end when the form has no Submit field.
 *
 * @param array    $field_types Ordered field type names.
 * @param int|null $requested   Requested one-based position.
 * @return int
 */
function ninja_forms_ability_resolve_field_insertion_order(
	array $field_types,
	?int $requested = null
): int {
	$maximum = count( $field_types ) + 1;
	if ( null === $requested ) {
		$submit = array_search( 'submit', $field_types, true );
		$requested = false === $submit ? $maximum : $submit + 1;
	}

	return max( 1, min( $requested, $maximum ) );
}

/**
 * Normalize add-field or update-field input without touching storage.
 *
 * @param array  $input      Raw ability input.
 * @param string $operation  Either "add" or "update".
 * @param string $field_type Existing type for an update.
 * @return array|WP_Error
 */
function ninja_forms_ability_normalize_field_input(
	array $input,
	string $operation = 'add',
	string $field_type = ''
) {
	$common = array(
		'form_id',
		'label',
		'key',
		'required',
		'placeholder',
		'default_value',
		'help_text',
		'admin_label',
		'order',
		'options',
	);
	$add_only = array(
		'type',
		'default',
		'personally_identifiable',
		'number_of_stars',
		'label_pos',
		'wrapper_class',
		'element_class',
		'container_class',
		'desc_text',
	);
	$allowed = 'update' === $operation
		? array_merge( $common, array( 'field_id' ) )
		: array_merge( $common, $add_only );
	$valid = ninja_forms_ability_validate_input_keys( $input, $allowed );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$normalized = array();
	foreach ( array( 'form_id', 'field_id', 'order', 'number_of_stars' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = (int) $input[ $key ];
		}
	}
	foreach ( array( 'required', 'personally_identifiable' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}
	}

	if ( array_key_exists( 'type', $input ) ) {
		$normalized['type'] = function_exists( 'ninja_forms_ability_normalize_field_type' )
			? ninja_forms_ability_normalize_field_type( $input['type'] )
			: sanitize_key( $input['type'] );
		$field_type = $normalized['type'];
		if ( ! ninja_forms_ability_field_type_is_selectable( $field_type ) ) {
			return new WP_Error(
				'field_type_not_selectable',
				sprintf(
					/* translators: %s: field type name. */
					__(
						'The "%s" field type is retired and cannot be added to a form. It is kept only for forms that already contain one.',
						'ninja-forms'
					),
					$field_type
				)
			);
		}
	}
	if ( array_key_exists( 'key', $input ) ) {
		$normalized['key'] = str_replace( '-', '_', sanitize_key( $input['key'] ) );
	}
	foreach (
		array(
			'label',
			'placeholder',
			'help_text',
			'admin_label',
			'wrapper_class',
			'element_class',
			'container_class',
			'desc_text',
		) as $key
	) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = sanitize_text_field( $input[ $key ] );
		}
	}
	if ( array_key_exists( 'label_pos', $input ) ) {
		$label_pos = sanitize_key( $input['label_pos'] );
		$normalized['label_pos'] = in_array(
			$label_pos,
			array( 'above', 'below', 'left', 'right', 'hidden' ),
			true
		) ? $label_pos : 'above';
	}

	foreach ( array( 'default', 'default_value' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = 'html' === $field_type
				? wp_kses_post( $input[ $key ] )
				: sanitize_text_field( $input[ $key ] );
		}
	}
	if ( array_key_exists( 'options', $input ) ) {
		$options = ninja_forms_ability_normalize_options( $input['options'] );
		if ( is_wp_error( $options ) ) {
			return $options;
		}
		$normalized['options'] = $options;
	}

	return $normalized;
}

/**
 * Determine whether a field type can be chosen when building a form.
 *
 * Core retires a field type by clearing its section: the builder palette
 * renders by section, so a section-less type is unreachable by hand and
 * survives only for forms that already contain one. Total, product, shipping
 * and the legacy password types are all in that group, along with the
 * "unknown" type an unrecognized field is coerced to. An agent gets the same
 * border a person has, rather than being advised of it and trusted to comply.
 *
 * Permissive when the field registry is unavailable, which is the case under
 * unit tests: the registry is the only authority on what a section holds, and
 * guessing without it would reject every type.
 *
 * @param string $type Field type name.
 * @return bool Whether the type may be added to a form.
 */
function ninja_forms_ability_field_type_is_selectable( string $type ): bool {
	if ( ! function_exists( 'Ninja_Forms' ) ) {
		return true;
	}
	$fields = Ninja_Forms()->fields;
	if ( ! is_array( $fields ) || ! isset( $fields[ $type ] ) ) {
		return true;
	}
	$field = $fields[ $type ];
	if ( ! is_object( $field ) || ! method_exists( $field, 'get_section' ) ) {
		return true;
	}

	return '' !== trim( (string) $field->get_section() );
}

/**
 * Determine whether an action type is supported by the public AI contract.
 *
 * @param string $type Requested action type.
 * @return bool
 */
function ninja_forms_ability_is_supported_action_type( string $type ): bool {
	return in_array( $type, array( 'email', 'redirect', 'successmessage', 'save' ), true );
}

/**
 * Normalize add-action or update-action input without touching storage.
 *
 * @param array  $input       Raw ability input.
 * @param string $action_type Existing type for an update.
 * @param string $operation   Either "add" or "update".
 * @return array|WP_Error
 */
function ninja_forms_ability_normalize_action_input(
	array $input,
	string $action_type = '',
	string $operation = 'add'
) {
	if ( 'add' === $operation ) {
		$action_type = isset( $input['type'] ) ? sanitize_key( $input['type'] ) : '';
	} else {
		$action_type = sanitize_key( $action_type );
	}
	if ( ! ninja_forms_ability_is_supported_action_type( $action_type ) ) {
		return new WP_Error( 'unsupported_action_type', __( 'That action type is not supported.', 'ninja-forms' ) );
	}

	$allowed = array( 'form_id', 'label', 'active' );
	if ( 'add' === $operation ) {
		$allowed[] = 'type';
	} else {
		$allowed[] = 'action_id';
	}
	if ( 'email' === $action_type ) {
		$allowed = array_merge(
			$allowed,
			array(
				'to',
				'subject',
				'message',
				'email_format',
				'from_name',
				'from_address',
				'reply_to',
				'cc',
				'bcc',
			)
		);
	} elseif ( 'redirect' === $action_type ) {
		$allowed[] = 'redirect_url';
	} elseif ( 'successmessage' === $action_type ) {
		$allowed[] = 'success_msg';
	}

	$valid = ninja_forms_ability_validate_input_keys( $input, $allowed );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$normalized = array( 'type' => $action_type );
	foreach ( array( 'form_id', 'action_id' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = (int) $input[ $key ];
		}
	}
	if ( array_key_exists( 'active', $input ) ) {
		$normalized['active'] = empty( $input['active'] ) ? 0 : 1;
	}
	foreach ( array( 'label', 'to', 'subject', 'from_name', 'cc', 'bcc' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = sanitize_text_field( $input[ $key ] );
		}
	}
	foreach ( array( 'from_address', 'reply_to' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = sanitize_email( $input[ $key ] );
		}
	}
	foreach ( array( 'message', 'success_msg' ) as $key ) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = wp_kses_post( $input[ $key ] );
		}
	}
	if ( array_key_exists( 'email_format', $input ) ) {
		$email_format = sanitize_key( $input['email_format'] );
		if ( ! in_array( $email_format, array( 'html', 'plain' ), true ) ) {
			return new WP_Error(
				'invalid_email_format',
				__( 'Email format must be HTML or plain text.', 'ninja-forms' )
			);
		}
		$normalized['email_format'] = $email_format;
	}
	if ( array_key_exists( 'redirect_url', $input ) ) {
		$url = esc_url_raw( $input['redirect_url'], array( 'http', 'https' ) );
		$scheme = is_string( $url ) ? strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) ) : '';
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new WP_Error(
				'invalid_redirect_url',
				__( 'Redirect URLs must use HTTP or HTTPS.', 'ninja-forms' )
			);
		}
		$normalized['redirect_url'] = $url;
	}

	return $normalized;
}

/**
 * Normalize update-form input without touching storage.
 *
 * @param array $input Raw ability input.
 * @return array|WP_Error
 */
function ninja_forms_ability_normalize_form_input( array $input ) {
	$allowed = array(
		'form_id',
		'title',
		'show_title',
		'form_title_heading_level',
		'default_label_pos',
		'clear_complete',
		'hide_complete',
		'allow_public_link',
		'logged_in',
		'wrapper_class',
		'element_class',
		'currency',
	);
	$valid = ninja_forms_ability_validate_input_keys( $input, $allowed );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$normalized = array();
	if ( array_key_exists( 'form_id', $input ) ) {
		$normalized['form_id'] = (int) $input['form_id'];
	}
	foreach (
		array( 'show_title', 'clear_complete', 'hide_complete', 'allow_public_link', 'logged_in' ) as $key
	) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}
	}
	foreach (
		array(
			'title',
			'form_title_heading_level',
			'default_label_pos',
			'wrapper_class',
			'element_class',
			'currency',
		) as $key
	) {
		if ( array_key_exists( $key, $input ) ) {
			$normalized[ $key ] = sanitize_text_field( $input[ $key ] );
		}
	}

	return $normalized;
}

/**
 * Project form settings to the values an external provider needs to edit.
 *
 * @param array $settings Stored form settings.
 * @return array
 */
function ninja_forms_ability_project_form_settings( array $settings ): array {
	$allowed = array(
		'title',
		'show_title',
		'form_title_heading_level',
		'default_label_pos',
		'clear_complete',
		'hide_complete',
		'ajax_submit',
		'logged_in',
		'not_logged_in_msg',
		'allow_public_link',
		'sub_limit_number',
		'sub_limit_msg',
		'wrapper_class',
		'element_class',
		'currency',
	);

	return array_intersect_key( $settings, array_flip( $allowed ) );
}

/**
 * Project a field row to an explicit provider-facing allowlist.
 *
 * @param array $field Stored or draft field settings.
 * @return array
 */
function ninja_forms_ability_project_field( array $field ): array {
	$allowed = array(
		'id',
		'type',
		'label',
		'key',
		'order',
		'required',
		'placeholder',
		'default',
		'help_text',
		'admin_label',
		'label_pos',
		'options',
		'personally_identifiable',
		'number_of_stars',
		'wrapper_class',
		'element_class',
		'container_class',
		'desc_text',
	);

	$projected = array_intersect_key( $field, array_flip( $allowed ) );
	if ( isset( $projected['options'] ) && is_array( $projected['options'] ) ) {
		$options = array();
		foreach ( $projected['options'] as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}
			$row = array(
				'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '',
				'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
			);
			if ( array_key_exists( 'calc', $option ) ) {
				$row['calc'] = sanitize_text_field( $option['calc'] );
			}
			$options[] = $row;
		}
		$projected['options'] = $options;
	}

	return $projected;
}

/**
 * Project calculations to the three public schema settings.
 *
 * @param array $calculations Stored calculation rows.
 * @return array
 */
function ninja_forms_ability_project_calculations( array $calculations ): array {
	$projected = array();
	foreach ( $calculations as $calculation ) {
		if ( ! is_array( $calculation ) ) {
			continue;
		}
		$row = array();
		if ( array_key_exists( 'name', $calculation ) ) {
			$row['name'] = sanitize_key( $calculation['name'] );
		}
		if ( array_key_exists( 'eq', $calculation ) ) {
			$row['eq'] = sanitize_text_field( $calculation['eq'] );
		}
		if ( array_key_exists( 'dec', $calculation ) ) {
			$row['dec'] = (int) $calculation['dec'];
		}
		$projected[] = $row;
	}

	return $projected;
}

/**
 * Project an action row to an explicit provider-facing allowlist.
 *
 * Secret, authentication, header, token, and unknown add-on settings are
 * intentionally absent even when they exist in the stored action model.
 *
 * @param array $action Stored or draft action settings.
 * @return array
 */
function ninja_forms_ability_project_action( array $action ): array {
	$projected = array_intersect_key(
		$action,
		array_flip( array( 'id', 'type', 'label', 'active', 'order' ) )
	);
	$type = isset( $projected['type'] ) ? $projected['type'] : '';
	if ( 'email' === $type ) {
		foreach ( array( 'to', 'email_subject' ) as $key ) {
			if ( array_key_exists( $key, $action ) ) {
				$projected[ $key ] = $action[ $key ];
			}
		}
	} elseif ( 'redirect' === $type && array_key_exists( 'redirect_url', $action ) ) {
		$projected['redirect_url'] = $action['redirect_url'];
	} elseif ( 'successmessage' === $type && array_key_exists( 'success_msg', $action ) ) {
		$projected['success_msg'] = $action['success_msg'];
	}

	return $projected;
}
