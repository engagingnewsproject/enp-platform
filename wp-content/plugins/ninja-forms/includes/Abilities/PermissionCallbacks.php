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
 * @return bool|WP_Error
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

/**
 * Check if current user can manage plugin settings
 *
 * Requires manage_options capability. Plugin settings contain sensitive data
 * such as reCAPTCHA secret keys.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_manage_settings() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you must be an administrator to manage plugin settings.', 'ninja-forms' )
	);
}

/**
 * Check if current user can read submissions
 *
 * Requires manage_options capability. Submission data may contain
 * sensitive user information.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_read_submissions() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you are not allowed to read submissions.', 'ninja-forms' )
	);
}

/**
 * Check if current user can modify submissions
 *
 * Requires manage_options capability.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_edit_submissions() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you are not allowed to modify submissions.', 'ninja-forms' )
	);
}

/**
 * Check if current user can delete submissions
 *
 * Requires manage_options capability.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_delete_submissions() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you are not allowed to delete submissions.', 'ninja-forms' )
	);
}

/**
 * Check if current user can embed forms into posts
 *
 * Requires manage_options capability. The embed-form ability writes to
 * WordPress posts/pages which is an administrative action outside the
 * form builder scope.
 *
 * Note: Per-object authorization checks for specific posts are performed
 * in the execute callback when the target post_id is known.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_embed_form() {
	// Embedding forms into posts is an administrative action.
	// nf_edit_forms is for form building only, not content management.
	if ( ! current_user_can( 'manage_options' ) ) {
		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you must be an administrator to embed forms into posts.', 'ninja-forms' )
		);
	}

	// Must be able to edit posts (general capability check).
	// Per-object check happens in execute callback when post_id is known.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to edit posts.', 'ninja-forms' )
		);
	}

	return true;
}

/**
 * Check if current user can manage form lifecycle (create, delete, duplicate, import)
 *
 * Requires manage_options capability. Form lifecycle operations are
 * administrative actions that go beyond editing an existing form.
 * The nf_edit_forms capability is for editing existing forms only.
 *
 * @since 3.8.23
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8124
 *
 * @return bool|WP_Error
 */
function ninja_forms_ability_can_manage_form_lifecycle() {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return new \WP_Error(
		'rest_forbidden',
		__( 'Sorry, you must be an administrator to create, delete, duplicate, or import forms.', 'ninja-forms' )
	);
}