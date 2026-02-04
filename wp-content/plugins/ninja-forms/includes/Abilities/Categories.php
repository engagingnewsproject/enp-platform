<?php
/**
 * Abilities API: Category Registration
 *
 * Registers ability categories for Ninja Forms.
 * Categories must be registered before abilities that reference them.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Ninja Forms ability categories.
 *
 * This function registers all ability categories used by Ninja Forms.
 * It runs on the wp_abilities_api_categories_init action hook.
 *
 * @since 3.13.0
 * @return void
 */
function ninja_forms_register_ability_categories() {
	// Verify the Abilities API function exists
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	/**
	 * Forms Category
	 *
	 * Groups abilities related to form creation, management, and configuration.
	 * Abilities in this category are composable - use ninjaforms/create-form to
	 * create a form, then use ninjaforms/add-field multiple times to add fields.
	 */
	wp_register_ability_category(
		'forms',
		array(
			'label'       => __( 'Forms', 'ninja-forms' ),
			'description' => __( 'Abilities for creating and managing forms. To build a complete form: first use ninjaforms/create-form to create the form, then use ninjaforms/add-field multiple times to add each field.', 'ninja-forms' ),
			'meta'        => array(
				'plugin' => 'ninja-forms',
				'version' => Ninja_Forms::VERSION,
			),
		)
	);
}

// Register categories on the appropriate hook
add_action( 'wp_abilities_api_categories_init', 'ninja_forms_register_ability_categories' );
