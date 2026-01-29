<?php
/**
 * Permission Callback Functions for Ninja Forms Abilities
 *
 * This file contains all the permission callback functions for Ninja Forms abilities.
 * These functions are moved here to reduce the size of the main Abilities.php file.
 *
 * @package NinjaForms
 * @subpackage Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if current user can manage forms
 *
 * @return bool
 */
function ninja_forms_ability_can_manage_forms() {
	if ( current_user_can( 'manage_options' ) || current_user_can( 'nf_edit_forms' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you are not allowed to manage Ninja Forms.', 'ninja-forms' )
	);
}