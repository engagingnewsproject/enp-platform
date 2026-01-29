<?php
/**
 * Response Helper for Ninja Forms Abilities API
 *
 * Provides standardized response formatting for consistent error handling
 * and success responses across all abilities utilities.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_Abilities_Response
 *
 * Standardizes all response formatting for the abilities API
 */
class NF_Abilities_Response {

	/**
	 * Create a standardized error response
	 *
	 * @param string $code Error code
	 * @param string $message Error message
	 * @param array $data Optional additional error data
	 * @return WP_Error
	 */
	public static function error( $code, $message, $data = null ) {
		$error_data = array( 'status' => 400 );
		
		if ( is_array( $data ) ) {
			$error_data = array_merge( $error_data, $data );
		}
		
		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Create a standardized success response
	 *
	 * @param mixed $data Response data
	 * @param string $message Optional success message
	 * @return array
	 */
	public static function success( $data = null, $message = '' ) {
		$response = array(
			'success' => true,
		);
		
		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}
		
		if ( ! is_null( $data ) ) {
			$response['data'] = $data;
		}
		
		return $response;
	}

	/**
	 * Create a permission denied error
	 *
	 * @param string $resource Resource being accessed
	 * @return WP_Error
	 */
	public static function permission_denied( $resource = 'Ninja Forms' ) {
		return self::error(
			'rest_forbidden',
			sprintf(
				/* translators: %s: resource name */
				__( 'Sorry, you are not allowed to manage %s.', 'ninja-forms' ),
				$resource
			),
			array( 'status' => 403 )
		);
	}

	/**
	 * Create a validation error
	 *
	 * @param string $field Field name
	 * @param string $message Custom validation message
	 * @return WP_Error
	 */
	public static function validation_error( $field, $message = '' ) {
		if ( empty( $message ) ) {
			$message = sprintf(
				/* translators: %s: field name */
				__( 'Invalid value for field: %s', 'ninja-forms' ),
				$field
			);
		}
		
		return self::error(
			'validation_failed',
			$message,
			array( 'field' => $field )
		);
	}

	/**
	 * Create a database error response
	 *
	 * @param string $operation Operation that failed
	 * @param string $details Error details (optional, for logging)
	 * @return WP_Error
	 */
	public static function database_error( $operation, $details = '' ) {
		if ( ! empty( $details ) ) {
			error_log( "NF Abilities DB Error [{$operation}]: {$details}" );
		}
		
		return self::error(
			'database_error',
			sprintf(
				/* translators: %s: operation name */
				__( 'Database error occurred during %s operation.', 'ninja-forms' ),
				$operation
			),
			array( 'status' => 500 )
		);
	}

	/**
	 * Create a not found error
	 *
	 * @param string $resource Resource type (form, field, submission, etc.)
	 * @param int|string $id Resource ID
	 * @return WP_Error
	 */
	public static function not_found( $resource, $id = '' ) {
		$message = sprintf(
			/* translators: %s: resource type */
			__( '%s not found.', 'ninja-forms' ),
			ucfirst( $resource )
		);
		
		if ( ! empty( $id ) ) {
			$message = sprintf(
				/* translators: %1$s: resource type, %2$s: resource ID */
				__( '%1$s with ID %2$s not found.', 'ninja-forms' ),
				ucfirst( $resource ),
				$id
			);
		}
		
		return self::error(
			'not_found',
			$message,
			array(
				'status' => 404,
				'resource' => $resource,
				'id' => $id
			)
		);
	}

	/**
	 * Create a required field error
	 *
	 * @param string $field Required field name
	 * @return WP_Error
	 */
	public static function required_field( $field ) {
		return self::error(
			'required_field',
			sprintf(
				/* translators: %s: field name */
				__( '%s is required.', 'ninja-forms' ),
				ucfirst( str_replace( '_', ' ', $field ) )
			),
			array( 'field' => $field )
		);
	}

	/**
	 * Check if response is a WP_Error
	 *
	 * @param mixed $response Response to check
	 * @return bool
	 */
	public static function is_error( $response ) {
		return is_wp_error( $response );
	}

	/**
	 * Get error code from WP_Error response
	 *
	 * @param WP_Error $response Error response
	 * @return string
	 */
	public static function get_error_code( $response ) {
		if ( self::is_error( $response ) ) {
			return $response->get_error_code();
		}
		return '';
	}

	/**
	 * Get error message from WP_Error response
	 *
	 * @param WP_Error $response Error response
	 * @return string
	 */
	public static function get_error_message( $response ) {
		if ( self::is_error( $response ) ) {
			return $response->get_error_message();
		}
		return '';
	}
}