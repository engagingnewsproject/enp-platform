<?php
/**
 * Calculation Utility Functions for Ninja Forms Abilities
 *
 * This file contains all calculation-related execute callback functions.
 * These functions handle calculation CRUD operations and management.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load validation and response helpers
require_once __DIR__ . '/ValidationHelper.php';
require_once __DIR__ . '/ResponseHelper.php';

function ninja_forms_ability_add_calculation( $input ) {
	// Validate form ID
	$form_id = NF_Abilities_Validation::validate_form_id( $input['form_id'] ?? '' );
	if ( NF_Abilities_Response::is_error( $form_id ) ) {
		return $form_id;
	}
	
	// Validate name
	if ( empty( $input['name'] ) ) {
		return NF_Abilities_Response::required_field( 'name' );
	}
	$name = sanitize_text_field( $input['name'] );
	
	// Validate formula with security checks
	$formula = NF_Abilities_Validation::validate_calculation_formula( $input['formula'] ?? '' );
	if ( NF_Abilities_Response::is_error( $formula ) ) {
		return $formula;
	}

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get existing calculations
	$calculations = $form->get_setting( 'calculations' );
	if ( ! is_array( $calculations ) ) {
		$calculations = array();
	}

	// Check if calculation name already exists
	foreach ( $calculations as $calc ) {
		if ( isset( $calc['name'] ) && $calc['name'] === $name ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Calculation with name "%s" already exists in form %d', 'ninja-forms' ), $name, $form_id ),
			);
		}
	}

	// Create new calculation
	$new_calculation = array(
		'name'          => $name,
		'eq'            => $formula,
		'dec'           => isset( $input['decimal_places'] ) ? (int) $input['decimal_places'] : 2,
		'dec_point'     => isset( $input['decimal_point'] ) ? sanitize_text_field( $input['decimal_point'] ) : '.',
		'thousands_sep' => isset( $input['thousands_sep'] ) ? sanitize_text_field( $input['thousands_sep'] ) : ',',
	);

	// Add to calculations array
	$calculations[] = $new_calculation;

	// Save calculations to form
	$form->update_setting( 'calculations', $calculations );
	$form->save();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'     => true,
		'form_id'     => $form_id,
		'name'        => $name,
		'calculation' => $new_calculation,
		'message'     => sprintf( __( 'Successfully added calculation "%s" to form %d.', 'ninja-forms' ), $name, $form_id ),
	);
}

function ninja_forms_ability_update_calculation( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['name'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Calculation name is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$name = sanitize_text_field( $input['name'] );

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get existing calculations
	$calculations = $form->get_setting( 'calculations' );
	if ( ! is_array( $calculations ) ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'No calculations found in form %d', 'ninja-forms' ), $form_id ),
		);
	}

	// Find and update the calculation
	$found = false;
	$updated_count = 0;

	foreach ( $calculations as &$calc ) {
		if ( isset( $calc['name'] ) && $calc['name'] === $name ) {
			$found = true;

			if ( isset( $input['formula'] ) ) {
				$calc['eq'] = sanitize_text_field( $input['formula'] );
				$updated_count++;
			}
			if ( isset( $input['decimal_places'] ) ) {
				$calc['dec'] = (int) $input['decimal_places'];
				$updated_count++;
			}
			if ( isset( $input['decimal_point'] ) ) {
				$calc['dec_point'] = sanitize_text_field( $input['decimal_point'] );
				$updated_count++;
			}
			if ( isset( $input['thousands_sep'] ) ) {
				$calc['thousands_sep'] = sanitize_text_field( $input['thousands_sep'] );
				$updated_count++;
			}

			break;
		}
	}

	if ( ! $found ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Calculation "%s" not found in form %d', 'ninja-forms' ), $name, $form_id ),
		);
	}

	// Save updated calculations
	$form->update_setting( 'calculations', $calculations );
	$form->save();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success' => true,
		'form_id' => $form_id,
		'name'    => $name,
		'updated' => $updated_count,
		'message' => sprintf( __( 'Successfully updated calculation "%s" in form %d. Updated %d setting(s).', 'ninja-forms' ), $name, $form_id, $updated_count ),
	);
}

function ninja_forms_ability_delete_calculation( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['name'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Calculation name is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$name = sanitize_text_field( $input['name'] );

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get existing calculations
	$calculations = $form->get_setting( 'calculations' );
	if ( ! is_array( $calculations ) ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'No calculations found in form %d', 'ninja-forms' ), $form_id ),
		);
	}

	// Find and remove the calculation
	$found = false;
	$new_calculations = array();

	foreach ( $calculations as $calc ) {
		if ( isset( $calc['name'] ) && $calc['name'] === $name ) {
			$found = true;
			// Skip this one (delete it)
			continue;
		}
		$new_calculations[] = $calc;
	}

	if ( ! $found ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Calculation "%s" not found in form %d', 'ninja-forms' ), $name, $form_id ),
		);
	}

	// Save updated calculations
	$form->update_setting( 'calculations', $new_calculations );
	$form->save();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success' => true,
		'form_id' => $form_id,
		'name'    => $name,
		'message' => sprintf( __( 'Successfully deleted calculation "%s" from form %d.', 'ninja-forms' ), $name, $form_id ),
	);
}

function ninja_forms_ability_list_calculations( $input ) {
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

	// Get calculations
	$calculations = $form->get_setting( 'calculations' );
	if ( ! is_array( $calculations ) ) {
		$calculations = array();
	}

	return array(
		'success'      => true,
		'form_id'      => $form_id,
		'count'        => count( $calculations ),
		'calculations' => $calculations,
		'message'      => sprintf( __( 'Found %d calculation(s) for form %d.', 'ninja-forms' ), count( $calculations ), $form_id ),
	);
}