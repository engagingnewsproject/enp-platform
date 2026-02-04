<?php
/**
 * Validation Helper for Ninja Forms Abilities API
 *
 * Provides comprehensive input validation and sanitization functions
 * for secure handling of user input across all abilities.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load response helper
require_once __DIR__ . '/ResponseHelper.php';

/**
 * Class NF_Abilities_Validation
 *
 * Centralized validation for all abilities input
 */
class NF_Abilities_Validation {

	/**
	 * Validate calculation formula for security
	 *
	 * @param string $formula The calculation formula
	 * @return string|WP_Error Validated formula or error
	 */
	public static function validate_calculation_formula( $formula ) {
		if ( empty( $formula ) ) {
			return NF_Abilities_Response::required_field( 'formula' );
		}
		
		// Clean the formula for analysis
		$clean_formula = trim( $formula );
		
		// Check for dangerous PHP functions and constructs
		$dangerous_patterns = array(
			'/\b(eval|exec|system|shell_exec|passthru|file_get_contents|file_put_contents|fopen|fwrite|include|require)\s*\(/i',
			'/\$\w+/', // PHP variables
			'/\bfunction\s*\(/i', // Function definitions
			'/\bnew\s+\w+/i', // Object instantiation
			'/\<\?php/i', // PHP tags
			'/\<script/i', // Script tags
			'/javascript:/i', // JavaScript protocol
			'/on\w+\s*=/i', // Event handlers
		);
		
		foreach ( $dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $clean_formula ) ) {
				return NF_Abilities_Response::error(
					'dangerous_formula',
					__( 'Formula contains potentially dangerous code and has been rejected.', 'ninja-forms' ),
					array( 'formula' => $clean_formula )
				);
			}
		}
		
		// Validate allowed characters and constructs for calculation formulas
		$allowed_pattern = '/^[\d\+\-\*\/\(\)\{\}\:\w\s\._,]+$/';
		if ( ! preg_match( $allowed_pattern, $clean_formula ) ) {
			return NF_Abilities_Response::validation_error(
				'formula',
				__( 'Formula contains invalid characters. Only numbers, operators (+, -, *, /), parentheses, and field references {field:key} are allowed.', 'ninja-forms' )
			);
		}
		
		// Validate balanced parentheses
		if ( ! self::validate_balanced_parentheses( $clean_formula ) ) {
			return NF_Abilities_Response::validation_error(
				'formula',
				__( 'Formula has unbalanced parentheses.', 'ninja-forms' )
			);
		}
		
		// Validate merge tag syntax
		if ( ! self::validate_merge_tags( $clean_formula ) ) {
			return NF_Abilities_Response::validation_error(
				'formula',
				__( 'Formula contains invalid merge tag syntax. Use {field:key} or {calc:name} format.', 'ninja-forms' )
			);
		}
		
		return sanitize_text_field( $clean_formula );
	}

	/**
	 * Validate balanced parentheses in formula
	 *
	 * @param string $formula Formula to check
	 * @return bool
	 */
	private static function validate_balanced_parentheses( $formula ) {
		$count = 0;
		$length = strlen( $formula );
		
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $formula[ $i ];
			if ( '(' === $char ) {
				$count++;
			} elseif ( ')' === $char ) {
				$count--;
				if ( $count < 0 ) {
					return false;
				}
			}
		}
		
		return $count === 0;
	}

	/**
	 * Validate merge tag syntax
	 *
	 * @param string $formula Formula to check
	 * @return bool
	 */
	private static function validate_merge_tags( $formula ) {
		// Find all merge tags
		preg_match_all( '/\{[^}]+\}/', $formula, $matches );
		
		if ( empty( $matches[0] ) ) {
			return true; // No merge tags is valid
		}
		
		foreach ( $matches[0] as $tag ) {
			// Valid formats: {field:key}, {calc:name}, {form:property}
			if ( ! preg_match( '/^\{(field|calc|form):[a-zA-Z0-9_]+\}$/', $tag ) ) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Validate form ID
	 *
	 * @param mixed $form_id Form ID to validate
	 * @return int|WP_Error Valid form ID or error
	 */
	public static function validate_form_id( $form_id ) {
		if ( empty( $form_id ) ) {
			return NF_Abilities_Response::required_field( 'form_id' );
		}
		
		$form_id = (int) $form_id;
		if ( $form_id <= 0 ) {
			return NF_Abilities_Response::validation_error( 'form_id', __( 'Form ID must be a positive integer.', 'ninja-forms' ) );
		}
		
		// Check if form exists
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}nf3_forms WHERE id = %d",
			$form_id
		) );
		
		if ( ! $exists ) {
			return NF_Abilities_Response::not_found( 'form', $form_id );
		}
		
		return $form_id;
	}

	/**
	 * Validate field data
	 *
	 * @param array $field_data Field data to validate
	 * @return array|WP_Error Sanitized field data or error
	 */
	public static function validate_field_data( $field_data ) {
		if ( ! is_array( $field_data ) ) {
			return NF_Abilities_Response::validation_error( 'field_data', __( 'Field data must be an array.', 'ninja-forms' ) );
		}
		
		// Required field type
		if ( empty( $field_data['type'] ) ) {
			return NF_Abilities_Response::required_field( 'type' );
		}
		
		// Validate field type
		$allowed_field_types = array(
			'textbox', 'textarea', 'email', 'number', 'tel', 'url', 'password',
			'select', 'radio', 'checkbox', 'listcheckbox', 'listradio',
			'date', 'hidden', 'html', 'hr', 'submit', 'starrating', 'rating',
			'spam', 'hcaptcha', 'recaptcha', 'file_upload'
		);
		
		if ( ! in_array( $field_data['type'], $allowed_field_types, true ) ) {
			return NF_Abilities_Response::validation_error(
				'type',
				sprintf(
					/* translators: %s: field type */
					__( 'Invalid field type: %s', 'ninja-forms' ),
					sanitize_text_field( $field_data['type'] )
				)
			);
		}
		
		// Sanitize all field data
		$sanitized = array();
		$sanitized['type'] = sanitize_text_field( $field_data['type'] );
		
		if ( isset( $field_data['label'] ) ) {
			$sanitized['label'] = sanitize_text_field( $field_data['label'] );
		}
		
		if ( isset( $field_data['key'] ) ) {
			$sanitized['key'] = sanitize_key( $field_data['key'] );
		}
		
		if ( isset( $field_data['required'] ) ) {
			$sanitized['required'] = (bool) $field_data['required'];
		}
		
		if ( isset( $field_data['placeholder'] ) ) {
			$sanitized['placeholder'] = sanitize_text_field( $field_data['placeholder'] );
		}
		
		// Sanitize HTML content for HTML field type with restricted tags
		if ( 'html' === $field_data['type'] && isset( $field_data['default'] ) ) {
			$allowed_html = array(
				'p' => array(),
				'br' => array(),
				'strong' => array(),
				'b' => array(),
				'em' => array(),
				'i' => array(),
				'h1' => array(),
				'h2' => array(),
				'h3' => array(),
				'h4' => array(),
				'h5' => array(),
				'h6' => array(),
				'span' => array( 'class' => true ),
			);
			$sanitized['default'] = wp_kses( $field_data['default'], $allowed_html );
		} elseif ( isset( $field_data['default'] ) ) {
			$sanitized['default'] = sanitize_text_field( $field_data['default'] );
		}
		
		// Handle options for select/radio/checkbox fields
		if ( isset( $field_data['options'] ) && is_array( $field_data['options'] ) ) {
			$sanitized['options'] = array();
			foreach ( $field_data['options'] as $option ) {
				if ( is_array( $option ) ) {
					$sanitized_option = array();
					if ( isset( $option['label'] ) ) {
						$sanitized_option['label'] = sanitize_text_field( $option['label'] );
					}
					if ( isset( $option['value'] ) ) {
						$sanitized_option['value'] = sanitize_text_field( $option['value'] );
					}
					if ( isset( $option['calc'] ) ) {
						$sanitized_option['calc'] = sanitize_text_field( $option['calc'] );
					}
					$sanitized['options'][] = $sanitized_option;
				}
			}
		}
		
		return $sanitized;
	}

	/**
	 * Validate and sanitize action settings
	 *
	 * @param array $settings Action settings
	 * @param string $action_type Action type
	 * @return array Sanitized settings
	 */
	public static function validate_action_settings( $settings, $action_type ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}
		
		$sanitized = array();
		
		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( $key );
			
			if ( is_string( $value ) ) {
				// HTML fields for email messages
				if ( in_array( $key, array( 'message', 'email_message', 'success_message' ), true ) ) {
					$allowed_html = array(
						'p' => array(),
						'br' => array(),
						'strong' => array(),
						'b' => array(),
						'em' => array(),
						'i' => array(),
						'a' => array( 'href' => true, 'target' => true ),
						'ul' => array(),
						'ol' => array(),
						'li' => array(),
					);
					$sanitized[ $key ] = wp_kses( $value, $allowed_html );
				} else {
					$sanitized[ $key ] = sanitize_text_field( $value );
				}
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $key ] = $value;
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $key ] = is_float( $value ) ? floatval( $value ) : intval( $value );
			}
		}
		
		return $sanitized;
	}

	/**
	 * Validate limit parameter
	 *
	 * @param mixed $limit Limit value
	 * @param int $max_limit Maximum allowed limit
	 * @return int|WP_Error Valid limit or error
	 */
	public static function validate_limit( $limit, $max_limit = 100 ) {
		if ( empty( $limit ) ) {
			return 0; // No limit
		}
		
		$limit = (int) $limit;
		
		if ( $limit < 0 ) {
			return NF_Abilities_Response::validation_error( 'limit', __( 'Limit cannot be negative.', 'ninja-forms' ) );
		}
		
		if ( $limit > $max_limit ) {
			return NF_Abilities_Response::validation_error(
				'limit',
				sprintf(
					/* translators: %d: maximum limit */
					__( 'Limit cannot exceed %d.', 'ninja-forms' ),
					$max_limit
				)
			);
		}
		
		return $limit;
	}
}