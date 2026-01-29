<?php
/**
 * Execute Callback Functions for Ninja Forms Abilities
 *
 * This file loads utility files containing execute callback functions for Ninja Forms abilities.
 * Functions have been organized into logical groups for better maintainability.
 *
 * @package NinjaForms
 * @subpackage Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load utility files containing organized function groups
require_once __DIR__ . '/Utils/FormUtils.php';
require_once __DIR__ . '/Utils/FieldUtils.php';
require_once __DIR__ . '/Utils/SubmissionUtils.php';
require_once __DIR__ . '/Utils/ActionUtils.php';
require_once __DIR__ . '/Utils/CalculationUtils.php';
require_once __DIR__ . '/Utils/PluginUtils.php';

/**
 * All execute callback functions are now loaded from their respective utility files:
 *
 * FormUtils.php:
 * - ninja_forms_ability_create_form
 * - ninja_forms_ability_list_forms
 * - ninja_forms_ability_get_form
 * - ninja_forms_ability_update_form
 * - ninja_forms_ability_delete_form
 * - ninja_forms_ability_duplicate_form
 * - ninja_forms_ability_import_form
 * - ninja_forms_ability_export_form_definition
 * - ninja_forms_ability_embed_form
 * - ninja_forms_ability_get_public_link
 *
 * FieldUtils.php:
 * - ninja_forms_ability_add_field
 * - ninja_forms_ability_add_field_internal
 * - ninja_forms_ability_update_field
 * - ninja_forms_ability_remove_field
 * - ninja_forms_ability_reorder_fields
 * - ninja_forms_ability_list_field_types
 * - ninja_forms_ensure_field_key_timestamp
 *
 * SubmissionUtils.php:
 * - ninja_forms_ability_get_submissions
 * - ninja_forms_ability_get_submission
 * - ninja_forms_ability_get_submission_fields
 * - ninja_forms_ability_update_submission
 * - ninja_forms_ability_delete_submission
 * - ninja_forms_ability_export_submissions
 * - ninja_forms_ability_process_submission
 *
 * ActionUtils.php:
 * - ninja_forms_ability_add_action
 * - ninja_forms_ability_update_action
 * - ninja_forms_ability_delete_action
 * - ninja_forms_ability_list_actions
 *
 * CalculationUtils.php:
 * - ninja_forms_ability_add_calculation
 * - ninja_forms_ability_update_calculation
 * - ninja_forms_ability_delete_calculation
 * - ninja_forms_ability_list_calculations
 *
 * PluginUtils.php:
 * - ninja_forms_ability_get_plugin_settings
 * - ninja_forms_ability_update_plugin_settings
 */