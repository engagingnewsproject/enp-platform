<?php
/**
 * Security Utilities for Ninja Forms Abilities API
 *
 * This file contains security utilities for CSRF protection and input validation
 * for the abilities API while maintaining existing permission structures.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verify CSRF nonce for abilities API requests
 *
 * @param WP_REST_Request $request The REST request object
 * @return bool|WP_Error True if valid, WP_Error if invalid
 */
function ninja_forms_ability_verify_nonce( $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	
	if ( empty( $nonce ) ) {
		$nonce = $request->get_param( '_wpnonce' );
	}
	
	if ( empty( $nonce ) ) {
		return new WP_Error(
			'rest_csrf_protection',
			__( 'CSRF token is missing. Please refresh the page and try again.', 'ninja-forms' ),
			array( 'status' => 403 )
		);
	}
	
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error(
			'rest_csrf_invalid',
			__( 'CSRF token is invalid. Please refresh the page and try again.', 'ninja-forms' ),
			array( 'status' => 403 )
		);
	}
	
	return true;
}

/**
 * Validate calculation formula for security
 *
 * @param string $formula The calculation formula
 * @return string|WP_Error Validated formula or error
 */
function ninja_forms_ability_validate_calculation_formula( $formula ) {
	if ( empty( $formula ) ) {
		return new WP_Error(
			'empty_formula',
			__( 'Calculation formula cannot be empty.', 'ninja-forms' ),
			array( 'status' => 400 )
		);
	}
	
	// Check for dangerous PHP functions and constructs
	$dangerous_patterns = array(
		'/\b(eval|exec|system|shell_exec|passthru|file_get_contents|file_put_contents|fopen|fwrite)\s*\(/',
		'/\$\w+/', // Variables
		'/\bfunction\s*\(/', // Anonymous functions
		'/\bnew\s+\w+/', // Object instantiation
		'/include|require/', // File inclusion
		'/\<\?php/', // PHP tags
		'/\<script/', // Script tags
	);
	
	foreach ( $dangerous_patterns as $pattern ) {
		if ( preg_match( $pattern, $formula ) ) {
			return new WP_Error(
				'dangerous_formula',
				__( 'Formula contains potentially dangerous code and has been rejected.', 'ninja-forms' ),
				array( 'status' => 400 )
			);
		}
	}
	
	// Validate that formula only contains allowed characters and constructs
	$allowed_pattern = '/^[\d\+\-\*\/\(\)\{\}\:\w\s\._]+$/';
	if ( ! preg_match( $allowed_pattern, $formula ) ) {
		return new WP_Error(
			'invalid_formula_chars',
			__( 'Formula contains invalid characters.', 'ninja-forms' ),
			array( 'status' => 400 )
		);
	}
	
	return sanitize_text_field( $formula );
}

/**
 * Enhanced permission callback with CSRF protection for destructive operations
 *
 * @param WP_REST_Request $request The REST request object
 * @return bool|WP_Error True if allowed, WP_Error if not
 */
function ninja_forms_ability_can_manage_forms_with_csrf( $request = null ) {
	// First check basic permissions (existing logic)
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'nf_edit_forms' ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to manage Ninja Forms.', 'ninja-forms' ),
			array( 'status' => 403 )
		);
	}
	
	// Add CSRF protection for destructive operations if request is provided
	if ( $request ) {
		$method = $request->get_method();
		$route = $request->get_route();
		
		// Check if this is a destructive operation
		$destructive_abilities = array(
			'delete-form', 'delete-submission', 'remove-field', 
			'delete-action', 'delete-calculation', 'update-form',
			'update-field', 'add-field', 'add-action', 'add-calculation',
			'create-form', 'process-submission'
		);
		
		$is_destructive = false;
		foreach ( $destructive_abilities as $ability ) {
			if ( strpos( $route, $ability ) !== false ) {
				$is_destructive = true;
				break;
			}
		}
		
		// Apply CSRF protection for destructive operations
		if ( $is_destructive && in_array( $method, array( 'POST', 'PUT', 'DELETE', 'PATCH' ), true ) ) {
			$nonce_check = ninja_forms_ability_verify_nonce( $request );
			if ( is_wp_error( $nonce_check ) ) {
				return $nonce_check;
			}
		}
	}
	
	return true;
}