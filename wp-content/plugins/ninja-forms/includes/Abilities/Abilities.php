<?php
/**
 * Abilities API: Ability Registration
 *
 * Registers abilities for Ninja Forms.
 * This file contains the POC implementation of 3 core abilities.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Ninja Forms abilities with WordPress.
 *
 * @return void
 */
function ninja_forms_register_abilities() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	// Load callback functions
	require_once __DIR__ . '/PermissionCallbacks.php';
	require_once __DIR__ . '/ExecuteCallbacks.php';

	// Register create-form ability
	wp_register_ability(
		'ninjaforms/create-form',
		array(
			'label'              => 'Create Form',
			'description'        => 'Creates a new Ninja Forms form with specified settings. Optionally include fields array to create a complete form in one call. Returns a form_id that can be used with ninjaforms/add-field to add more fields later. Automatically adds a submit button and default actions (Success Message, Admin Email, Record Submission) unless custom actions are specified. DEFAULT SAVE ACTION: The default "Record Submission" save action is automatically created and configured correctly - form submissions will be saved to the database. No workarounds or manual fixes are needed. The save action is properly initialized with all required settings including parent_id association. CRITICAL MERGE TAG FORMATTING: When using merge tags like {field:key} or {calc:name} in success_message or any text content, always add a space before { and after }. Example: "Thank you, {field:name} !" not "Thank you, {field:name}!". Merge tags that touch other characters will fail to render. CRITICAL CALCULATION DISPLAY: For calculator forms, use HTML field type (NOT textbox) to display calculation results. Use ONE merge tag per HTML field for real-time updates. Example: {"type":"html","default":"<h3>Total: $ {calc:total} </h3>"} not multiple calculations in one field. Keep HTML simple with basic tags (h3, p, strong). Avoid complex nested divs with multiple merge tags as they will not update in real-time. CALCULATION STRUCTURE: For calculator forms, define all calculations in the calculations array. Each calculation must have: "name" (string identifier), "eq" (equation using merge tags), and optionally "dec" (decimal places). Equation syntax: use {field:field_key} for field references, {calc:name} for calculation references, and operators +, -, *, /, (). Note: Merge tags inside equations do NOT need spacing (they are parsed differently than text content). Display results using HTML fields with properly spaced merge tags. Store calculation results in hidden fields if needed for submission records. FIELD KEY CONSISTENCY: When creating forms with fields, calculations, and actions in one call, ensure field keys match exactly across all components. If you specify "key":"email_1234567890123" in the fields array, use that exact key in calculations {"eq":"{field:email_1234567890123}"} and actions/success_message merge tags {field:email_1234567890123}. Field keys typically follow the pattern "descriptive_name_1234567890123" with a 13-digit timestamp. Inconsistent keys will cause merge tags and calculations to fail silently.',
			'category'           => 'forms',
			'input_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'title' => array(
						'type'        => 'string',
						'description' => 'Form title (required)',
					),
					'fields' => array(
						'type'        => 'array',
						'description' => 'Array of field definitions to add to the form. FIELD BEST PRACTICES: Email, phone, and address fields MUST include "personally_identifiable":true for GDPR compliance. Spam protection fields (hCaptcha, reCAPTCHA) must have "label_pos":"hidden" and "required":false. Default required to false if not specified. Use HTML fields for section headers: {"type":"html","default":"<p><b>Section Name</b></p>"}. Use HR fields for dividers. For two-column layouts: first field "container_class":"one-half first", second field "container_class":"one-half". Default placeholder to empty string - only add when field purpose is ambiguous. FIELD-TYPE-SPECIFIC SETTINGS: Star rating fields (type: "starrating") require "number_of_stars" setting (typically "5") or stars will not render.',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'type'          => array(
									'type'        => 'string',
									'description' => 'Field type. Must be a registered Ninja Forms field type - never invent one; unknown types are coerced to textbox. Core types: textbox, textarea, email, number, phone, date, checkbox, listcheckbox, listradio, listselect, listmultiselect, liststate, listcountry, address, city, zip, firstname, lastname, hidden, html, hr, starrating. For US states use "liststate", for countries use "listcountry", for a dropdown use "listselect". If no specific type fits, use "textbox". Use ninjaforms/list-field-types for the full list on this site (add-ons register more, e.g. file_upload).',
								),
								'label'         => array( 'type' => 'string' ),
								'required'      => array( 'type' => 'boolean' ),
								'placeholder'   => array( 'type' => 'string' ),
								'default_value' => array( 'type' => 'string' ),
								'help_text'     => array( 'type' => 'string' ),
								'admin_label'   => array( 'type' => 'string' ),
								'key'           => array( 'type' => 'string' ),
								'personally_identifiable' => array(
									'type'        => 'boolean',
									'description' => 'REQUIRED (true) for email, phone, and address fields for GDPR compliance.',
								),
								'number_of_stars' => array(
									'type'        => 'integer',
									'description' => 'REQUIRED for starrating fields (typically 5); stars will not render without it.',
								),
								'options'       => array(
									'type'  => 'array',
									'items' => array(
										'type'       => 'object',
										'properties' => array(
											'label' => array( 'type' => 'string' ),
											'value' => array( 'type' => 'string' ),
											'calc'  => array( 'type' => 'string' ),
										),
									),
								),
							),
							'required' => array( 'type' ),
						),
					),
					'actions' => array(
						'type'        => 'array',
						'description' => 'Custom actions for the form. If not provided, defaults will be added (Success Message, Admin Email, Record Submission). When provided, a Record Submission (save) and Success Message action are still guaranteed - you may omit them. Supported types are "email", "redirect", "successmessage", and "save". Core settings: "email" (to, email_subject, email_message, email_format ["html"|"plain"], reply_to, from_name, from_address, cc, bcc), "successmessage" (success_msg), "redirect" (redirect_url), "save" (none). ADMIN NOTIFICATION: an email action with to={wp:admin_email}. AUTORESPONDER / CONFIRMATION EMAIL to the submitter: an email action with "to" set to the email field merge tag, e.g. to={field:email_1234567890123} using that field\'s exact key. MERGE TAGS in email_message text need a space before { and after }.',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'type'     => array(
									'type' => 'string',
									'enum' => array( 'email', 'redirect', 'successmessage', 'save' ),
								),
								'label'    => array( 'type' => 'string' ),
								'active'   => array( 'type' => 'boolean', 'default' => true ),
								'settings' => array(
									'type'                 => 'object',
									'properties'           => array(
										'to'            => array( 'type' => 'string' ),
										'email_subject' => array( 'type' => 'string' ),
										'email_message' => array( 'type' => 'string' ),
										'email_format'  => array(
											'type' => 'string',
											'enum' => array( 'html', 'plain' ),
										),
										'reply_to'      => array( 'type' => 'string' ),
										'from_name'     => array( 'type' => 'string' ),
										'from_address'  => array( 'type' => 'string' ),
										'cc'            => array( 'type' => 'string' ),
										'bcc'           => array( 'type' => 'string' ),
										'success_msg'   => array( 'type' => 'string' ),
										'redirect_url'  => array( 'type' => 'string' ),
									),
									'additionalProperties' => false,
								),
							),
							'required' => array( 'type' ),
						),
					),
					'show_title' => array(
						'type'        => 'boolean',
						'description' => 'Show form title',
						'default'     => true,
					),
					'form_title_heading_level' => array(
						'type'        => 'string',
						'description' => 'Heading level for form title (1-6)',
						'enum'        => array( '1', '2', '3', '4', '5', '6' ),
						'default'     => '3',
					),
					'default_label_pos' => array(
						'type'        => 'string',
						'description' => 'Default label position',
						'enum'        => array( 'above', 'below', 'left', 'right', 'hidden' ),
						'default'     => 'above',
					),
					'clear_complete' => array(
						'type'        => 'boolean',
						'description' => 'Clear form after successful submission',
						'default'     => true,
					),
					'hide_complete' => array(
						'type'        => 'boolean',
						'description' => 'Hide form after successful submission',
						'default'     => true,
					),
					'ajax_submit' => array(
						'type'        => 'boolean',
						'description' => 'Enable AJAX form submission',
						'default'     => true,
					),
					'logged_in' => array(
						'type'        => 'boolean',
						'description' => 'Require user to be logged in',
						'default'     => false,
					),
					'not_logged_in_msg' => array(
						'type'        => 'string',
						'description' => 'Message to show when user is not logged in',
					),
					'allow_public_link' => array(
						'type'        => 'boolean',
						'description' => 'Allow public link access',
						'default'     => false,
					),
					'sub_limit_number' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of submissions allowed',
					),
					'sub_limit_msg' => array(
						'type'        => 'string',
						'description' => 'Message when submission limit is reached',
						'default'     => __( 'The form has reached its submission limit.', 'ninja-forms' ),
					),
					'wrapper_class' => array(
						'type'        => 'string',
						'description' => 'CSS class for form wrapper',
					),
					'element_class' => array(
						'type'        => 'string',
						'description' => 'CSS class for form element',
					),
					'calculations' => array(
						'type'        => 'array',
						'description' => 'Calculation definitions for calculator forms. Each calculation requires "name" (unique identifier used in {calc:name} merge tags), "eq" (equation using {field:key} references and operators +, -, *, /, ()), and optionally "dec" (decimal places for result). Example: [{"name":"total","eq":"{field:price}*{field:quantity}","dec":2}]. Merge tags in equations do NOT need spacing. Display results using HTML fields with spaced merge tags: {"type":"html","default":"<h3>Total: $ {calc:total} </h3>"}',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'name' => array( 'type' => 'string' ),
								'eq'   => array( 'type' => 'string' ),
							),
						),
					),
					'currency' => array(
						'type'        => 'string',
						'description' => 'Currency code for payment forms',
					),
					'conditions' => array(
						'type'        => 'array',
						'description' => 'Form-level conditional logic',
					),
					'admin_email_to' => array(
						'type'        => 'string',
						'description' => 'Admin notification recipient email (only used with default actions)',
						'default'     => '{wp:admin_email}',
					),
					'admin_email_subject' => array(
						'type'        => 'string',
						'description' => 'Admin notification subject (only used with default actions)',
					),
					'success_message' => array(
						'type'        => 'string',
						'description' => 'Success message to display (only used with default actions). Default is "Your form has been successfully submitted." Customize to match your form\'s purpose (e.g., "Thank you for your registration!" or "Your message has been sent!"). Remember to add spaces before { and after } when using merge tags like {field:name}.',
					),
					'submit_label' => array(
						'type'        => 'string',
						'description' => 'Label for the submit button (default: "Submit"). A submit button is automatically added to every form.',
						'default'     => 'Submit',
					),
					'honeypot_enabled' => array(
						'type'        => 'boolean',
						'description' => 'Enable honeypot spam protection',
					),
				),
				'required' => array( 'title' ),
			),
			'output_schema'      => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'field_count' => array( 'type' => 'integer' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'   => 'ninja_forms_ability_create_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_form_lifecycle',
			'meta'               => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_create_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register add-field ability
	wp_register_ability(
		'ninjaforms/add-field',
		array(
			'label'              => 'Add Field',
			'description'        => 'Adds a field to an existing Ninja Forms form. Use the form_id returned by ninjaforms/create-form to add fields. Call this ability multiple times to add multiple fields to a form. POSITIONING: order is a one-based insertion position, not a final sort hint. Existing fields at that position and below move down. For requests like "before Message" or "between Email and Message", call ninjaforms/get-form first and use the current order of Message. If order is omitted, the field is inserted immediately before Submit. CRITICAL MERGE TAG FORMATTING: When adding HTML fields (type: html) that display calculations or field values using merge tags like {field:key} or {calc:name}, always add a space before { and after }. Example: "<h3>Total: $ {calc:total} </h3>" not "<h3>Total: ${calc:total}</h3>". Merge tags that touch other characters will fail to render. CRITICAL CALCULATION DISPLAY: For calculator forms, use HTML field type (NOT textbox) to display calculation results for real-time updates. Use ONE merge tag per HTML field. Example: Add separate fields like {"type":"html","label":"Monthly Payment","default":"<h3>$ {calc:monthly} </h3>"} and {"type":"html","label":"Total Cost","default":"<p>$ {calc:total} </p>"} rather than combining multiple calculations in one field. Keep HTML simple with basic tags (h3, p, strong). Complex nested structures with multiple merge tags will not update in real-time. PERSONALLY IDENTIFIABLE FIELDS: Email, phone, and address field types MUST include "personally_identifiable":true in field settings for GDPR compliance. SPAM PROTECTION: hCaptcha and reCAPTCHA fields must have "label_pos":"hidden" (the widget itself is sufficient) and should NOT be marked as required (set "required":false) - spam protection fields should never display required asterisks. Place spam protection fields before the submit button. REQUIRED FIELDS: Set "required":true for required fields, "required":false for optional fields. Default is false if not specified. FORM ORGANIZATION: Use HTML fields with bold text for section headers: {"type":"html","default":"<p><b>Section Name</b></p>"}. Use HR (divider) fields between major sections. Group related fields together. TWO-COLUMN LAYOUTS: First field in two-column set: "container_class":"one-half first". Second field: "container_class":"one-half". After the pair, return to full width or start another two-column set. PLACEHOLDER POLICY: Default to empty placeholder ("placeholder":""). Only add placeholder text when the field purpose is ambiguous or users need format examples. DO NOT add placeholders for standard fields like Name, Email, Phone, Number, or Address where the label is self-explanatory. FIELD-TYPE-SPECIFIC SETTINGS: Star rating fields (type: "starrating") require the "number_of_stars" setting (typically set to "5"). Without this setting, the stars will not render on the form. Example: {"type":"starrating","label":"Overall Satisfaction","required":true,"number_of_stars":"5"}.',
			'category'           => 'forms',
			'input_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'type' => array(
						'type'        => 'string',
						'description' => 'Field type (required). Must be a registered Ninja Forms field type - never invent one; unknown types are coerced to textbox. Core types: textbox, textarea, email, number, phone, date, checkbox, listcheckbox, listradio, listselect, listmultiselect, liststate, listcountry, address, city, zip, firstname, lastname, hidden, html, hr, starrating, submit. For US states use liststate, for countries use listcountry, for a dropdown use listselect. If no specific type fits, use textbox. Use ninjaforms/list-field-types for the full list on this site (add-ons register more, e.g. file_upload).',
					),
					'label' => array(
						'type'        => 'string',
						'description' => 'Field label',
					),
					'required' => array(
						'type'        => 'boolean',
						'description' => 'Whether field is required. Set true for required fields, false for optional. Default is false. NOTE: Spam protection fields (hCaptcha, reCAPTCHA) should always be false - they should not display required asterisks.',
					),
					'placeholder' => array(
						'type'        => 'string',
						'description' => 'Placeholder text. Default to empty string (""). Only add placeholder text when field purpose is ambiguous or users need format examples. DO NOT add for standard fields (Name, Email, Phone, Number, Address) where label is self-explanatory.',
					),
					'default_value' => array(
						'type'        => 'string',
						'description' => 'Default value',
					),
					'help_text' => array(
						'type'        => 'string',
						'description' => 'Help text',
					),
					'order' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'Optional one-based insertion position. Existing fields at this position and below shift down. To add before a named field, first read the form and use that field\'s current order. Omit to insert immediately before Submit.',
					),
					'personally_identifiable' => array(
						'type'        => 'boolean',
						'description' => 'REQUIRED (true) for email, phone, and address fields for GDPR compliance.',
					),
					'number_of_stars' => array(
						'type'        => 'integer',
						'description' => 'REQUIRED for starrating fields (typically 5); stars will not render without it.',
					),
				),
				'required' => array( 'form_id', 'type' ),
			),
			'output_schema'      => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'field_id' => array( 'type' => 'integer' ),
					'form_id'  => array( 'type' => 'integer' ),
					'type'     => array( 'type' => 'string' ),
					'order'    => array( 'type' => 'integer' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'   => 'ninja_forms_ability_add_field',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'               => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_add_field', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register list-forms ability
	wp_register_ability(
		'ninjaforms/list-forms',
		array(
			'label'              => 'List Forms',
			'description'        => 'Retrieves a list of all Ninja Forms forms with their metadata. Optionally filter by title and include field/action details.',
			'category'           => 'forms',
			'input_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'title' => array(
						'type'        => 'string',
						'description' => 'Filter forms by title (partial match)',
					),
					'include_fields' => array(
						'type'        => 'boolean',
						'description' => 'Include field count for each form',
						'default'     => true,
					),
					'include_actions' => array(
						'type'        => 'boolean',
						'description' => 'Include action count for each form',
						'default'     => true,
					),
					'limit' => array(
						'type'        => 'integer',
						'description' => 'Maximum number of forms to return (0 = no limit)',
						'default'     => 0,
					),
				),
			),
			'output_schema'      => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'forms'   => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'           => array( 'type' => 'integer' ),
								'title'        => array( 'type' => 'string' ),
								'created_at'   => array( 'type' => 'string' ),
								'field_count'  => array( 'type' => 'integer' ),
								'action_count' => array( 'type' => 'integer' ),
								'settings'     => array( 'type' => 'object' ),
							),
						),
					),
					'count'   => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'   => 'ninja_forms_ability_list_forms',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'               => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_list_forms', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register list-field-types ability
	wp_register_ability(
		'ninjaforms/list-field-types',
		array(
			'label'               => 'List Field Types',
			'description'         => 'Returns all available Ninja Forms field types with their basic information. Useful for discovering what field types can be added to forms.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'format' => array(
						'type'        => 'string',
						'description' => 'Output format (optional, defaults to "simple")',
						'enum'        => array( 'simple', 'detailed' ),
						'default'     => 'simple',
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'field_types' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'         => array( 'type' => 'string' ),
								'nicename'     => array( 'type' => 'string' ),
								'section'      => array( 'type' => 'string' ),
								'icon'         => array( 'type' => 'string' ),
								'default_label' => array( 'type' => 'string' ),
							),
						),
					),
					'count'       => array( 'type' => 'integer' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_list_field_types',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_list_field_types', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register get-form ability
	wp_register_ability(
		'ninjaforms/get-form',
		array(
			'label'               => 'Get Form',
			'description'         => 'Retrieves complete form configuration including all settings, fields, actions, and calculations. Returns full form data that can be used to understand the form structure or prepare for updates.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form to retrieve (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify. Never assume which form when ambiguity exists.',
					),
					'include_fields' => array(
						'type'        => 'boolean',
						'description' => 'Include field details in response (default: true)',
					),
					'include_actions' => array(
						'type'        => 'boolean',
						'description' => 'Include action details in response (default: true)',
					),
					'include_calculations' => array(
						'type'        => 'boolean',
						'description' => 'Include calculations in response (default: true)',
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'      => array( 'type' => 'boolean' ),
					'form'         => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array( 'type' => 'integer' ),
							'title'        => array( 'type' => 'string' ),
							'created_at'   => array( 'type' => 'string' ),
							'settings'     => array( 'type' => 'object' ),
							'fields'       => array( 'type' => 'array' ),
							'actions'      => array( 'type' => 'array' ),
							'calculations' => array( 'type' => 'array' ),
						),
					),
					'message'      => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_get_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-form ability
	wp_register_ability(
		'ninjaforms/update-form',
		array(
			'label'               => 'Update Form',
			'description'         => 'Updates an existing form\'s settings. Only provided settings will be updated, all others remain unchanged. Supports all form configuration options available in the Ninja Forms dashboard.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form to update (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify. Never assume which form when ambiguity exists.',
					),
					'title' => array(
						'type'        => 'string',
						'description' => 'Form title',
					),
					'show_title' => array(
						'type'        => 'boolean',
						'description' => 'Show form title',
					),
					'form_title_heading_level' => array(
						'type'        => 'string',
						'description' => 'Heading level for form title (1-6)',
						'enum'        => array( '1', '2', '3', '4', '5', '6' ),
					),
					'default_label_pos' => array(
						'type'        => 'string',
						'description' => 'Default label position',
						'enum'        => array( 'above', 'below', 'left', 'right', 'hidden' ),
					),
					'clear_complete' => array(
						'type'        => 'boolean',
						'description' => 'Clear form after successful submission',
					),
					'hide_complete' => array(
						'type'        => 'boolean',
						'description' => 'Hide form after successful submission',
					),
					'allow_public_link' => array(
						'type'        => 'boolean',
						'description' => 'Allow public preview link',
					),
					'logged_in' => array(
						'type'        => 'boolean',
						'description' => 'Require users to be logged in',
					),
					'wrapper_class' => array(
						'type'        => 'string',
						'description' => 'Custom CSS class for wrapper',
					),
					'element_class' => array(
						'type'        => 'string',
						'description' => 'Custom CSS class for elements',
					),
					'currency' => array(
						'type'        => 'string',
						'description' => 'Currency code (USD, EUR, etc)',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'form_id'  => array( 'type' => 'integer' ),
					'updated'  => array( 'type' => 'object' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_update_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-field ability
	wp_register_ability(
		'ninjaforms/update-field',
		array(
			'label'               => 'Update Field',
			'description'         => 'REQUIRED PARAMETERS: This ability requires BOTH form_id and field_id - you cannot update a field by providing only the field_id. If you don\'t know the form_id, use ninjaforms/list-forms or ninjaforms/get-form first to identify it. Updates an existing field\'s settings. Only provided settings will be updated, all others remain unchanged. Supports all field configuration options available in the Ninja Forms dashboard. CRITICAL CALCULATION DISPLAY: When updating HTML fields that display calculations, use ONE merge tag per field for real-time updates. Example: {"default":"<h3>Total: $ {calc:total} </h3>"} works in real-time. Avoid multiple merge tags in one HTML field like "<div><p>{calc:a}</p><p>{calc:b}</p></div>" as this will not update in real-time. Keep HTML simple with basic tags (h3, p, strong). TWO-COLUMN LAYOUTS: First field in two-column set: "container_class":"one-half first". Second field: "container_class":"one-half". After the pair, return to full width or start another two-column set. PLACEHOLDER POLICY: Default to empty placeholder. Only add placeholder text when field purpose is ambiguous or users need format examples. DO NOT add for standard fields (Name, Email, Phone, Number, Address) where label is self-explanatory.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'Form ID containing the field (required)',
					),
					'field_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the field (required). If the user referenced a field by label or description, ALWAYS use ninjaforms/get-form first to identify the correct field_id. If multiple fields match, ASK the user to clarify. Never assume which field when ambiguity exists.',
					),
					'label' => array(
						'type'        => 'string',
						'description' => 'Field label',
					),
					'key' => array(
						'type'        => 'string',
						'description' => 'Field key/name',
					),
					'required' => array(
						'type'        => 'boolean',
						'description' => 'Field is required. Set true for required fields, false for optional. Default is false. NOTE: Spam protection fields should always be false.',
					),
					'placeholder' => array(
						'type'        => 'string',
						'description' => 'Placeholder text. Default to empty string. Only add when field purpose is ambiguous or users need format examples. DO NOT add for standard fields where label is self-explanatory.',
					),
					'default_value' => array(
						'type'        => 'string',
						'description' => 'Default value',
					),
					'help_text' => array(
						'type'        => 'string',
						'description' => 'Help text',
					),
					'admin_label' => array(
						'type'        => 'string',
						'description' => 'Admin label',
					),
					'order' => array(
						'type'        => 'integer',
						'description' => 'Field order/position',
					),
					'options' => array(
						'type'        => 'array',
						'description' => 'Field options (for select, radio, checkbox fields)',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'label' => array( 'type' => 'string' ),
								'value' => array( 'type' => 'string' ),
								'calc'  => array( 'type' => 'string' ),
							),
						),
					),
				),
				'required' => array( 'form_id', 'field_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'form_id'  => array( 'type' => 'integer' ),
					'field_id' => array( 'type' => 'integer' ),
					'updated'  => array( 'type' => 'object' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_update_field',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_field', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register delete-form ability
	wp_register_ability(
		'ninjaforms/delete-form',
		array(
			'label'               => 'Delete Form',
			'description'         => 'Permanently deletes a form and all associated data including fields, actions, submissions, and metadata. This action cannot be undone. CRITICAL: Before executing this ability, you MUST ask the user for explicit confirmation and WAIT for their positive response. Do not proceed with deletion unless the user explicitly confirms.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form to delete (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify. Never assume which form when ambiguity exists.',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'form_id' => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_delete_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_form_lifecycle',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => true,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_delete_form', false ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register remove-field ability
	wp_register_ability(
		'ninjaforms/remove-field',
		array(
			'label'               => 'Remove Field',
			'description'         => 'Removes a field from a form with mandatory data protection workflow. CRITICAL: This ability can ONLY process ONE field at a time. Even if the user requests removing multiple fields, you must process them individually with full confirmation workflow for each. REQUIRED WORKFLOW (MUST FOLLOW EXACTLY): STEP 1 - Check for Submission Data: When this ability is called, it automatically checks if the field has submission data. STEP 2 - Stop and Inform User: If the field has submission data, the ability will NOT proceed with removal. Instead, it returns field name, number of submissions with data, WARNING about permanent deletion, and request for user confirmation to proceed. Do NOT proceed until user explicitly confirms. STEP 3 - Export Data Automatically: Once user confirms, call this ability again. It will automatically export all field data to CSV and return export file location, sample data preview, and required confirmation phrase. STEP 4 - Require Typed Confirmation: User must type EXACTLY: "DELETE [FIELD_NAME] DATA FROM [COUNT] SUBMISSIONS". Do NOT accept "yes", "ok", "confirmed", or any variation. STEP 5 - Execute After Valid Confirmation: Call this ability a third time with the exact confirmation phrase. Only then will the field be removed. HANDLING BULK REQUESTS: If user requests removing multiple fields (e.g., "Remove all unused fields"): identify all fields, tell user you will process each individually, process FIRST field through complete workflow (Steps 1-5), after completion move to second field, repeat full workflow for each field, NEVER batch process. NEVER: process multiple fields in one call, skip warnings and confirmation, proceed without export, accept vague confirmations, or assume user intent. This ability prevents accidental data loss. Follow the workflow exactly, even if it seems repetitive.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'Form ID containing the field (required)',
					),
					'field_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the field (required). If the user referenced a field by label or description, ALWAYS use ninjaforms/get-form first to identify the correct field_id. If multiple fields match, ASK the user to clarify. Never assume which field when ambiguity exists.',
					),
					'export_completed' => array(
						'type'        => 'boolean',
						'description' => 'Set to true after export has been completed and user has reviewed the data',
					),
					'user_confirmation_phrase' => array(
						'type'        => 'string',
						'description' => 'Must be: DELETE [FIELD_NAME] DATA FROM [COUNT] SUBMISSIONS',
					),
				),
				'required' => array( 'form_id', 'field_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'form_id'  => array( 'type' => 'integer' ),
					'field_id' => array( 'type' => 'integer' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_remove_field',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => true,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_remove_field', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register reorder-fields ability
	wp_register_ability(
		'ninjaforms/reorder-fields',
		array(
			'label'               => 'Reorder Fields',
			'description'         => 'Changes the order of fields in a form. Accepts an array of field_id => order mappings or a simple array of field IDs in the desired order.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'Form ID containing the fields (required)',
					),
					'field_order' => array(
						'type'        => 'object',
						'description' => 'Object mapping field_id to order position (e.g., {"69": 1, "70": 2})',
					),
				),
				'required' => array( 'form_id', 'field_order' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'reordered'   => array( 'type' => 'integer' ),
					'field_order' => array( 'type' => 'object' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_reorder_fields',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_reorder_fields', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register duplicate-form ability
	wp_register_ability(
		'ninjaforms/duplicate-form',
		array(
			'label'               => 'Duplicate Form',
			'description'         => 'Creates an exact copy of a form including all fields, actions, and calculations. The duplicated form will have " (Copy)" appended to its title.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form to duplicate (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify. Never assume which form when ambiguity exists.',
					),
					'new_title' => array(
						'type'        => 'string',
						'description' => 'Optional custom title for the duplicated form',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'new_form_id' => array( 'type' => 'integer' ),
					'title'       => array( 'type' => 'string' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_duplicate_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_form_lifecycle',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_duplicate_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// ================================================================
	// PHASE 4: Actions & Calculations Abilities
	// ================================================================

	// Register add-action ability
	wp_register_ability(
		'ninjaforms/add-action',
		array(
			'label'               => 'Add Action',
			'description'         => 'Adds an action to a form (email, redirect, success message, etc.). Each action executes after form submission. SAVE ACTIONS: Save actions (type: "save") work correctly when created via this ability - they properly save form submissions to the database. You do not need to set any special parameters for save actions beyond type and label. The system automatically handles all required configuration including parent_id association. CRITICAL ACTION FIELD NAMES: Different action types use different field names for their content: "email" actions use "message" or "email_message", but "successmessage" actions use "success_msg" (NOT "message"). Using the wrong field name will cause the action to fail silently. CRITICAL MERGE TAG FORMATTING: When using merge tags like {field:key} or {calc:name} in email messages (message/email_message parameters) or success messages (success_msg parameter), always add a space before { and after }. Example: "Dear {field:name} ," not "Dear {field:name},". Merge tags that touch other characters will fail to render. EMAIL NOTIFICATIONS: Admin email notification is automatically included in default actions. For user confirmation emails, add an additional email action with "to":"{field:email_field_key}" and customize the subject and message. SUCCESS MESSAGES: Customize success messages in successmessage action type using the success_msg parameter. Default message is "Your form has been successfully submitted." - customize to match your form\'s purpose. FIELD KEY CONSISTENCY: When using merge tags like {field:key}, use the EXACT field key from the form. If you don\'t know the field keys, use ninjaforms/get-form first to retrieve all field information. Field keys typically follow the pattern "descriptive_name_1234567890123" with a 13-digit timestamp. Incorrect field keys will cause merge tags to fail silently.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id'      => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'type'         => array(
						'type'        => 'string',
						'description' => 'Action type: email, redirect, successmessage, save, etc. (required)',
					),
					'label'        => array(
						'type'        => 'string',
						'description' => 'Action label',
					),
					'active'       => array(
						'type'        => 'integer',
						'description' => 'Whether action is active (1) or inactive (0)',
					),
					'to'           => array(
						'type'        => 'string',
						'description' => 'Email recipient (for email actions). For admin notifications, use static email or {wp:admin_email}. For user confirmation emails, use {field:email_field_key} to send to the user who submitted the form.',
					),
					'subject'      => array(
						'type'        => 'string',
						'description' => 'Email subject (for email actions)',
					),
					'message'      => array(
						'type'        => 'string',
						'description' => 'Email message (for email actions)',
					),
					'redirect_url' => array(
						'type'        => 'string',
						'description' => 'Redirect URL (for redirect actions)',
					),
					'success_msg'  => array(
						'type'        => 'string',
						'description' => 'Success message (for successmessage actions). Default is "Your form has been successfully submitted." Customize to match your form\'s purpose. Remember to add spaces before { and after } when using merge tags.',
					),
				),
				'required' => array( 'form_id', 'type' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'   => array( 'type' => 'boolean' ),
					'form_id'   => array( 'type' => 'integer' ),
					'action_id' => array( 'type' => 'integer' ),
					'type'      => array( 'type' => 'string' ),
					'message'   => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_add_action',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_add_action', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-action ability
	wp_register_ability(
		'ninjaforms/update-action',
		array(
			'label'               => 'Update Action',
			'description'         => 'REQUIRED PARAMETERS: This ability requires BOTH form_id and action_id - you cannot update an action by providing only the action_id. If you don\'t know the form_id or action_id, use ninjaforms/list-actions first. PARAMETER FORMAT: Provide settings as top-level parameters, NOT nested in a \'settings\' object. Correct: {"form_id": 70, "action_id": 229, "to": "test@example.com", "subject": "New Subject"}. Incorrect: {"form_id": 70, "action_id": 229, "settings": {"to": "test@example.com"}} - this will report \'Updated 0 setting(s)\' even though the action saves. Updates an existing action\'s settings. Use the "active" parameter to enable (active: 1) or disable (active: 0) an action without deleting it. When a user asks to "turn off", "disable", or "deactivate" an action, use this ability with active: 0 instead of deleting the action. Only use delete-action when the user explicitly requests permanent removal. IMPORTANT: If the user is attempting to disable (active: 0) a "save" type action (Record Submission), you MUST warn them: "With this action disabled, submissions of this form will no longer be saved to the WordPress database or available under Ninja Forms > Submissions. Are you sure you want to proceed?" Wait for explicit confirmation before proceeding. CRITICAL ACTION FIELD NAMES: Different action types use different field names for their content: "email" actions use "message" or "email_message", but "successmessage" actions use "success_msg" (NOT "message"). Using the wrong field name will cause the action to fail silently. CRITICAL MERGE TAG FORMATTING: When updating email messages (message/email_message parameters) or success messages (success_msg parameter) with merge tags like {field:key} or {calc:name}, always add a space before { and after }. Example: "Total: $ {calc:price} " not "Total: ${calc:price}". Merge tags that touch other characters will fail to render. FIELD KEY CONSISTENCY: When using merge tags like {field:key}, use the EXACT field key from the form. If you don\'t know the field keys, use ninjaforms/get-form first to retrieve all field information. Field keys typically follow the pattern "descriptive_name_1234567890123" with a 13-digit timestamp. Incorrect field keys will cause merge tags to fail silently.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id'      => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'action_id'    => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the action (required). If the user referenced an action by type or label, ALWAYS use ninjaforms/list-actions first to identify the correct action_id. If multiple actions match, ASK the user to clarify. Never assume which action when ambiguity exists.',
					),
					'label'        => array(
						'type'        => 'string',
						'description' => 'Action label',
					),
					'active'       => array(
						'type'        => 'integer',
						'description' => 'Whether action is active (1) or inactive (0)',
					),
					'to'           => array(
						'type'        => 'string',
						'description' => 'Email recipient (for email actions). For admin notifications, use static email or {wp:admin_email}. For user confirmation emails, use {field:email_field_key} to send to the user who submitted the form.',
					),
					'subject'      => array(
						'type'        => 'string',
						'description' => 'Email subject (for email actions)',
					),
					'message'      => array(
						'type'        => 'string',
						'description' => 'Email message (for email actions)',
					),
					'redirect_url' => array(
						'type'        => 'string',
						'description' => 'Redirect URL (for redirect actions)',
					),
					'success_msg'  => array(
						'type'        => 'string',
						'description' => 'Success message (for successmessage actions). Default is "Your form has been successfully submitted." Customize to match your form\'s purpose. Remember to add spaces before { and after } when using merge tags.',
					),
				),
				'required' => array( 'form_id', 'action_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'   => array( 'type' => 'boolean' ),
					'action_id' => array( 'type' => 'integer' ),
					'form_id'   => array( 'type' => 'integer' ),
					'updated'   => array( 'type' => 'integer' ),
					'message'   => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_update_action',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_action', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register delete-action ability
	wp_register_ability(
		'ninjaforms/delete-action',
		array(
			'label'               => 'Delete Action',
			'description'         => 'REQUIRED PARAMETERS: This ability requires BOTH form_id and action_id - you cannot delete an action by providing only the action_id. If you don\'t know the form_id or action_id, use ninjaforms/list-actions first. Permanently deletes an action from a form. This action cannot be undone. CRITICAL: Before executing this ability, you MUST ask the user for explicit confirmation and WAIT for their positive response. Do not proceed with deletion unless the user explicitly confirms. If the user wants to temporarily disable an action instead of permanently deleting it, use ninjaforms/update-action with active: 0. IMPORTANT: If the user is attempting to delete a "save" type action (Record Submission), you MUST warn them: "With this action removed, submissions of this form will no longer be saved to the WordPress database or available under Ninja Forms > Submissions. Are you sure you want to proceed?" Wait for explicit confirmation before proceeding.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id'   => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'action_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the action (required). If the user referenced an action by type or label, ALWAYS use ninjaforms/list-actions first to identify the correct action_id. If multiple actions match, ASK the user to clarify. Never assume which action when ambiguity exists.',
					),
				),
				'required' => array( 'form_id', 'action_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'   => array( 'type' => 'boolean' ),
					'action_id' => array( 'type' => 'integer' ),
					'form_id'   => array( 'type' => 'integer' ),
					'message'   => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_delete_action',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => true,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_delete_action', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register list-actions ability
	wp_register_ability(
		'ninjaforms/list-actions',
		array(
			'label'               => 'List Actions',
			'description'         => 'Lists all actions configured for a form.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'form_id' => array( 'type' => 'integer' ),
					'count'   => array( 'type' => 'integer' ),
					'actions' => array( 'type' => 'array' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_list_actions',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_list_actions', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register add-calculation ability
	wp_register_ability(
		'ninjaforms/add-calculation',
		array(
			'label'               => 'Add Calculation',
			'description'         => 'Adds a calculation to a form. Calculations can be used in calc fields and conditional logic. Each calculation requires "name" (unique identifier used in {calc:name} merge tags), "formula" (equation using {field:key} references and operators +, -, *, /, ()), and optionally "decimal_places" for result precision. Equation syntax: {field:field_key} for field values, {calc:name} for other calculation results, standard math operators. Note: Merge tags inside formulas do NOT need spacing (parsed differently than text content). Display calculation results using HTML fields with properly spaced merge tags: {"type":"html","default":"<h3>Result: $ {calc:total} </h3>"}. Store results in hidden fields if needed for submission records. FIELD KEY CONSISTENCY: When creating calculation formulas with {field:key} references, use the EXACT field keys from the form. If you don\'t know the field keys, use ninjaforms/get-form first to retrieve all field information. Field keys typically follow the pattern "descriptive_name_1234567890123" with a 13-digit timestamp. Incorrect field keys will cause calculations to fail or return zero.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id'         => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'name'            => array(
						'type'        => 'string',
						'description' => 'The name/key of the calculation (required). If the user referenced a calculation by description, ALWAYS use ninjaforms/list-calculations first to identify the correct calculation name. If multiple calculations match, ASK the user to clarify. Never assume which calculation when ambiguity exists.',
					),
					'formula'         => array(
						'type'        => 'string',
						'description' => 'Calculation formula using merge tags (required). Use {field:field_key} to reference field values, {calc:name} to reference other calculations, and operators +, -, *, /, (). Example: "{field:price_123}*{field:quantity_456}" or "({field:subtotal_789}+{calc:tax})*1.1". Merge tags in formulas do NOT need spacing.',
					),
					'decimal_places'  => array(
						'type'        => 'integer',
						'description' => 'Number of decimal places',
					),
					'decimal_point'   => array(
						'type'        => 'string',
						'description' => 'Decimal point character',
					),
					'thousands_sep'   => array(
						'type'        => 'string',
						'description' => 'Thousands separator character',
					),
				),
				'required' => array( 'form_id', 'name', 'formula' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'name'        => array( 'type' => 'string' ),
					'calculation' => array( 'type' => 'object' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_add_calculation',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_add_calculation', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-calculation ability
	wp_register_ability(
		'ninjaforms/update-calculation',
		array(
			'label'               => 'Update Calculation',
			'description'         => 'Updates an existing calculation\'s settings. Modify the calculation "formula" (equation using {field:key} references and operators +, -, *, /, ()), "decimal_places" for result precision, or formatting options. Equation syntax: {field:field_key} for field values, {calc:name} for other calculation results, standard math operators. Note: Merge tags inside formulas do NOT need spacing (parsed differently than text content). After updating, ensure calculation results are displayed using HTML fields with properly spaced merge tags: {"type":"html","default":"<h3>Result: $ {calc:total} </h3>"}. Store results in hidden fields if needed for submission records. FIELD KEY CONSISTENCY: When updating calculation formulas with {field:key} references, use the EXACT field keys from the form. If you don\'t know the field keys, use ninjaforms/get-form first to retrieve all field information. Field keys typically follow the pattern "descriptive_name_1234567890123" with a 13-digit timestamp. Incorrect field keys will cause calculations to fail or return zero.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id'         => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'name'            => array(
						'type'        => 'string',
						'description' => 'The name/key of the calculation (required). If the user referenced a calculation by description, ALWAYS use ninjaforms/list-calculations first to identify the correct calculation name. If multiple calculations match, ASK the user to clarify. Never assume which calculation when ambiguity exists.',
					),
					'formula'         => array(
						'type'        => 'string',
						'description' => 'Calculation formula using merge tags. Use {field:field_key} to reference field values, {calc:name} to reference other calculations, and operators +, -, *, /, (). Example: "{field:price_123}*{field:quantity_456}" or "({field:subtotal_789}+{calc:tax})*1.1". Merge tags in formulas do NOT need spacing.',
					),
					'decimal_places'  => array(
						'type'        => 'integer',
						'description' => 'Number of decimal places',
					),
					'decimal_point'   => array(
						'type'        => 'string',
						'description' => 'Decimal point character',
					),
					'thousands_sep'   => array(
						'type'        => 'string',
						'description' => 'Thousands separator character',
					),
				),
				'required' => array( 'form_id', 'name' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'form_id' => array( 'type' => 'integer' ),
					'name'    => array( 'type' => 'string' ),
					'updated' => array( 'type' => 'integer' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_update_calculation',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_calculation', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register delete-calculation ability
	wp_register_ability(
		'ninjaforms/delete-calculation',
		array(
			'label'               => 'Delete Calculation',
			'description'         => 'Permanently deletes a calculation from a form. This action cannot be undone. CRITICAL: Before executing this ability, you MUST ask the user for explicit confirmation and WAIT for their positive response. Do not proceed with deletion unless the user explicitly confirms.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'name'    => array(
						'type'        => 'string',
						'description' => 'The name/key of the calculation (required). If the user referenced a calculation by description, ALWAYS use ninjaforms/list-calculations first to identify the correct calculation name. If multiple calculations match, ASK the user to clarify. Never assume which calculation when ambiguity exists.',
					),
				),
				'required' => array( 'form_id', 'name' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'form_id' => array( 'type' => 'integer' ),
					'name'    => array( 'type' => 'string' ),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_delete_calculation',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => true,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_delete_calculation', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register list-calculations ability
	wp_register_ability(
		'ninjaforms/list-calculations',
		array(
			'label'               => 'List Calculations',
			'description'         => 'Lists all calculations configured for a form.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'      => array( 'type' => 'boolean' ),
					'form_id'      => array( 'type' => 'integer' ),
					'count'        => array( 'type' => 'integer' ),
					'calculations' => array( 'type' => 'array' ),
					'message'      => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_list_calculations',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_list_calculations', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// ========================================================================
	// PHASE 5: SUBMISSION MANAGEMENT ABILITIES
	// ========================================================================

	// Register get-submissions ability
	wp_register_ability(
		'ninjaforms/get-submissions',
		array(
			'label'               => 'Get Submissions',
			'description'         => 'Retrieves all submissions for a form with optional filtering.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'where' => array(
						'type'        => 'object',
						'description' => 'Optional filtering criteria (field key => value pairs)',
					),
					'submission_ids' => array(
						'type'        => 'array',
						'description' => 'Optional array of specific submission IDs to retrieve',
						'items'       => array( 'type' => 'integer' ),
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'count'       => array( 'type' => 'integer' ),
					'submissions' => array( 'type' => 'array' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_get_submissions',
			'permission_callback' => 'ninja_forms_ability_can_read_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_submissions', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register get-submission ability
	wp_register_ability(
		'ninjaforms/get-submission',
		array(
			'label'               => 'Get Submission',
			'description'         => 'Retrieves detailed data for a single submission including all field values.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'submission_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the submission (required). If the user referenced a submission by description (e.g., "the one from John"), ALWAYS use ninjaforms/get-submissions first to identify the correct submission_id. If multiple submissions match, ASK the user to clarify. Never assume which submission when ambiguity exists.',
					),
				),
				'required' => array( 'submission_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'    => array( 'type' => 'boolean' ),
					'submission' => array( 'type' => 'object' ),
					'message'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_get_submission',
			'permission_callback' => 'ninja_forms_ability_can_read_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_submission', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register get-submission-fields ability
	wp_register_ability(
		'ninjaforms/get-submission-fields',
		array(
			'label'               => 'Get Submission Fields',
			'description'         => 'Retrieves field values for a submission. Optionally includes field labels and types.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'submission_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the submission (required). If the user referenced a submission by description (e.g., "the one from John"), ALWAYS use ninjaforms/get-submissions first to identify the correct submission_id. If multiple submissions match, ASK the user to clarify. Never assume which submission when ambiguity exists.',
					),
					'include_labels' => array(
						'type'        => 'boolean',
						'description' => 'Include field keys, labels, and types in response',
						'default'     => false,
					),
				),
				'required' => array( 'submission_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'       => array( 'type' => 'boolean' ),
					'submission_id' => array( 'type' => 'integer' ),
					'form_id'       => array( 'type' => 'integer' ),
					'count'         => array( 'type' => 'integer' ),
					'fields'        => array( 'type' => array( 'object', 'array' ) ),
					'message'       => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_get_submission_fields',
			'permission_callback' => 'ninja_forms_ability_can_read_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_submission_fields', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-submission ability
	wp_register_ability(
		'ninjaforms/update-submission',
		array(
			'label'               => __( 'Update Submission', 'ninja-forms' ),
			'description'         => 'Updates field values in an existing submission. Does not trigger actions.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'submission_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the submission (required). If the user referenced a submission by description (e.g., "the one from John"), ALWAYS use ninjaforms/get-submissions first to identify the correct submission_id. If multiple submissions match, ASK the user to clarify. Never assume which submission when ambiguity exists.',
					),
					'field_values' => array(
						'type'        => 'object',
						'description' => 'Field values to update (field key/ID => value pairs)',
					),
				),
				'required' => array( 'submission_id', 'field_values' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'       => array( 'type' => 'boolean' ),
					'submission_id' => array( 'type' => 'integer' ),
					'form_id'       => array( 'type' => 'integer' ),
					'updated'       => array( 'type' => 'integer' ),
					'message'       => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_update_submission',
			'permission_callback' => 'ninja_forms_ability_can_edit_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_submission', false ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register delete-submission ability
	wp_register_ability(
		'ninjaforms/delete-submission',
		array(
			'label'               => __( 'Delete Submission', 'ninja-forms' ),
			'description'         => 'Deletes a submission. By default moves to trash; use permanent=true for hard delete. IMPORTANT: Trashed submissions are stored for 30 days before being permanently deleted by WordPress. During this 30-day window, there is no UI method available to restore trashed submissions - recovery requires using the AI Assistant or programmatic database access. After 30 days, trashed submissions are permanently deleted and cannot be recovered. CRITICAL: Before executing this ability, you MUST ask the user for explicit confirmation and WAIT for their positive response. Do not proceed with deletion unless the user explicitly confirms.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'submission_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the submission (required). If the user referenced a submission by description (e.g., "the one from John"), ALWAYS use ninjaforms/get-submissions first to identify the correct submission_id. If multiple submissions match, ASK the user to clarify. Never assume which submission when ambiguity exists.',
					),
					'permanent' => array(
						'type'        => 'boolean',
						'description' => 'Permanently delete (true) or move to trash (false, default)',
						'default'     => false,
					),
				),
				'required' => array( 'submission_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'       => array( 'type' => 'boolean' ),
					'submission_id' => array( 'type' => 'integer' ),
					'form_id'       => array( 'type' => 'integer' ),
					'permanent'     => array( 'type' => 'boolean' ),
					'message'       => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_delete_submission',
			'permission_callback' => 'ninja_forms_ability_can_delete_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => true,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_delete_submission', false ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register export-submissions ability
	wp_register_ability(
		'ninjaforms/export-submissions',
		array(
			'label'               => __( 'Export Submissions', 'ninja-forms' ),
			'description'         => 'Exports submissions to a CSV file. Always use CSV format for end users (JSON is only for advanced technical use cases). CRITICAL: You MUST write the returned content to a local file - do NOT just return the CSV text to the user. After calling this ability: (1) Ask the user where to save the file (e.g., Downloads folder, Desktop), (2) Use your file writing tool to save the CSV content to that location with an appropriate .csv filename (e.g., contact-form-submissions.csv), (3) Confirm to the user that the CSV file has been saved. The user expects a downloadable .csv file, not raw text.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'format' => array(
						'type'        => 'string',
						'description' => 'Export format: Always use "csv" for end users (default). Only use "json" if the user explicitly requests it for technical/API purposes.',
						'enum'        => array( 'csv', 'json' ),
						'default'     => 'csv',
					),
					'submission_ids' => array(
						'type'        => 'array',
						'description' => 'Optional array of specific submission IDs to export',
						'items'       => array( 'type' => 'integer' ),
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'form_id'     => array( 'type' => 'integer' ),
					'format'      => array( 'type' => 'string' ),
					'count'       => array( 'type' => 'integer' ),
					'content'     => array( 'type' => 'string' ),
					'submissions' => array( 'type' => 'array' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_export_submissions',
			'permission_callback' => 'ninja_forms_ability_can_read_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_export_submissions', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register process-submission ability
	wp_register_ability(
		'ninjaforms/process-submission',
		array(
			'label'               => 'Process Submission',
			'description'         => 'Manually trigger action processing for an existing submission. Currently only supports email actions.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'submission_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the submission (required). If the user referenced a submission by description (e.g., "the one from John"), ALWAYS use ninjaforms/get-submissions first to identify the correct submission_id. If multiple submissions match, ASK the user to clarify. Never assume which submission when ambiguity exists.',
					),
					'action_type' => array(
						'type'        => 'string',
						'description' => 'Action type to process (currently only "email" supported)',
						'enum'        => array( 'email' ),
					),
					'action_settings' => array(
						'type'        => 'object',
						'description' => 'Action settings (e.g., email settings with "to", "email_subject", "email_message")',
					),
				),
				'required' => array( 'submission_id', 'action_type', 'action_settings' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'       => array( 'type' => 'boolean' ),
					'submission_id' => array( 'type' => 'integer' ),
					'form_id'       => array( 'type' => 'integer' ),
					'action_type'   => array( 'type' => 'string' ),
					'message'       => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_process_submission',
			'permission_callback' => 'ninja_forms_ability_can_edit_submissions',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_process_submission', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register import-form ability
	wp_register_ability(
		'ninjaforms/import-form',
		array(
			'label'               => __( 'Import Form', 'ninja-forms' ),
			'description'         => 'Import a form from .nff file content. Accepts JSON string or array containing form settings, fields, and actions.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'file_content' => array(
						'type'        => 'string',
						'description' => 'Content of .nff file (JSON string)',
					),
					'decode_utf8' => array(
						'type'        => 'boolean',
						'description' => 'Whether to decode UTF-8 (default: true)',
						'default'     => true,
					),
				),
				'required' => array( 'file_content' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'    => array( 'type' => 'boolean' ),
					'message'    => array( 'type' => 'string' ),
					'form_id'    => array( 'type' => 'integer' ),
					'form_title' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_import_form',
			'permission_callback' => 'ninja_forms_ability_can_manage_form_lifecycle',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_import_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register export-form-definition ability
	wp_register_ability(
		'ninjaforms/export-form-definition',
		array(
			'label'               => 'Export Form Definition',
			'description'         => 'Export a form to a .nff file (Ninja Forms export format containing form settings, fields, and actions). CRITICAL: You MUST write the returned content to a local file with .nff extension - do NOT just return the JSON to the user. After calling this ability: (1) Ask the user where to save the file (e.g., Downloads folder, Desktop), (2) Use your file writing tool to save the content to that location with a .nff extension using the suggested filename from the response, (3) Confirm to the user that the .nff file has been saved. The user expects a downloadable .nff file, not raw JSON text.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form to export (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify. Never assume which form when ambiguity exists.',
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'    => array( 'type' => 'boolean' ),
					'message'    => array( 'type' => 'string' ),
					'form_id'    => array( 'type' => 'integer' ),
					'form_title' => array( 'type' => 'string' ),
					'content'    => array( 'type' => 'string', 'description' => 'JSON string of form data' ),
					'filename'   => array( 'type' => 'string', 'description' => 'Suggested filename' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_export_form_definition',
			'permission_callback' => 'ninja_forms_ability_can_manage_forms',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_export_form_definition', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register embed-form ability
	wp_register_ability(
		'ninjaforms/embed-form',
		array(
			'label'               => 'Embed Form',
			'description'         => 'Embed a form in a post or page. This is a TWO-STEP process: STEP 1 (Discovery): Call with form_id and optionally post_title or post_id. If page exists, returns content for you to show user. Ask user which embed method (shortcode/block/metabox) and where to place it. If page not found, ask user to confirm creation. STEP 2 (Execution): Call with embed_method and placement details. For shortcode/block: parse returned content_blocks, present to user, ask for placement (before/after block X, at beginning/end). For metabox: no placement needed. The ability handles page search/creation automatically - you just guide the user through method and placement choices.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'ID of the form to embed',
					),
					'post_id' => array(
						'type'        => 'integer',
						'description' => 'ID of existing post to update (optional if creating new)',
					),
					'post_title' => array(
						'type'        => 'string',
						'description' => 'Title for new post/page. Only use this parameter if creating a NEW page. If a page with this title already exists and you want to update it, use post_id instead. Always search for existing pages with this title first to avoid duplicates.',
					),
					'post_content' => array(
						'type'        => 'string',
						'description' => 'Additional content for post (shortcode will be appended)',
					),
					'post_status' => array(
						'type'        => 'string',
						'description' => 'Post status',
						'enum'        => array( 'publish', 'draft', 'pending', 'private' ),
						'default'     => 'publish',
					),
					'post_type' => array(
						'type'        => 'string',
						'description' => 'Post type',
						'enum'        => array( 'post', 'page' ),
						'default'     => 'page',
					),
					'embed_method' => array(
						'type'        => 'string',
						'description' => 'How to embed the form: "shortcode" (manually placed in content), "block" (Gutenberg block), or "metabox" (auto-appears at bottom). If not provided on first call, ability will ask user to choose.',
						'enum'        => array( 'shortcode', 'block', 'metabox' ),
					),
					'placement' => array(
						'type'        => 'string',
						'description' => 'Where to place shortcode/block: "before", "after", "replace", "at_beginning", "at_end". Required for shortcode/block methods.',
						'enum'        => array( 'before', 'after', 'replace', 'at_beginning', 'at_end' ),
					),
					'placement_reference' => array(
						'type'        => 'string',
						'description' => 'Which content block to reference for placement (e.g., "block_3" for third block). Not needed for at_beginning/at_end.',
					),
					'confirm_create' => array(
						'type'        => 'boolean',
						'description' => 'Confirm creation of new page (when page not found). User must explicitly confirm before creating new pages.',
						'default'     => false,
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'              => array( 'type' => 'boolean' ),
					'message'              => array( 'type' => 'string' ),
					'post_id'              => array( 'type' => 'integer' ),
					'post_title'           => array( 'type' => 'string' ),
					'permalink'            => array( 'type' => 'string' ),
					'page_found'           => array( 'type' => 'boolean' ),
					'page_not_found'       => array( 'type' => 'boolean' ),
					'post_content'         => array( 'type' => 'string' ),
					'content_blocks'       => array( 'type' => 'array' ),
					'needs_embed_method'   => array( 'type' => 'boolean' ),
					'needs_confirmation'   => array( 'type' => 'boolean' ),
					'available_methods'    => array( 'type' => 'array' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_embed_form',
			'permission_callback' => 'ninja_forms_ability_can_embed_form',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_embed_form', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register get-public-link ability
	wp_register_ability(
		'ninjaforms/get-public-link',
		array(
			'label'               => 'Get Public Link',
			'description'         => 'Enable or disable public link for a form and return the URL. Public links allow anonymous access to the form without embedding. PRETTY URL FORMAT: This ability generates public links using the modern pretty URL format: /ninja-forms/{slug} where {slug} is a randomly generated 6-character alphanumeric code (lowercase letters and numbers). Example: /ninja-forms/k3caev. This matches the UI behavior and uses WordPress rewrite rules for clean, shareable URLs. The ability automatically generates both the short slug (public_link_key) and stores the full URL (public_link). IMPORTANT: After enabling the public link, you MUST provide the public_link URL from the response to the user so they can share it. Present the URL clearly and explain that they can share this link directly (via email, social media, etc.) and anyone can access the form without needing to visit a specific page on their website.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'form_id' => array(
						'type'        => 'integer',
						'description' => 'The numeric ID of the form (required). If the user referenced a form by name or description, ALWAYS use ninjaforms/list-forms first to identify the correct form_id. If multiple forms match, ASK the user to clarify by presenting form TITLES only (e.g., "I found Contact Form and Contact Me. Which one?"). Do NOT use numbered lists like "1. Contact Form, 2. Contact Me" as users may respond with the list position instead of the form ID. Ask the user to respond with the form title or ID directly. Never assume which form when ambiguity exists.',
					),
					'enable' => array(
						'type'        => 'boolean',
						'description' => 'Enable (true) or disable (false) public link',
						'default'     => true,
					),
				),
				'required' => array( 'form_id' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'         => array( 'type' => 'boolean' ),
					'message'         => array( 'type' => 'string' ),
					'public_link'     => array( 'type' => 'string', 'description' => 'Full URL to public form' ),
					'public_link_key' => array( 'type' => 'string', 'description' => 'Unique key for form' ),
					'enabled'         => array( 'type' => 'boolean', 'description' => 'Whether public link is enabled' ),
				),
			),
			'execute_callback'    => 'ninja_forms_ability_get_public_link',
			'permission_callback' => 'ninja_forms_ability_can_manage_settings',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_public_link', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register get-plugin-settings ability
	wp_register_ability(
		'ninjaforms/get-plugin-settings',
		array(
			'label'               => 'Get Plugin Settings',
			'description'         => 'Retrieve global Ninja Forms plugin settings including date format, currency, reCAPTCHA keys, and advanced options. IMPORTANT: You MUST present the actual VALUES of the settings to the user in a readable format organized by settings category. Use the "settings_by_group" field from the response which dynamically organizes settings by their actual category headers in the Ninja Forms Settings UI (e.g., "General Settings", "reCaptcha Settings", "hCaptcha Settings", "Turnstile Settings", "Advanced Settings"). Present each category as a header followed by its settings. CRITICAL: Do NOT present the "Other" category or any settings within it - skip this category entirely when displaying settings to users. For each setting, show the setting name and its current value. CRITICAL: Use these display labels when presenting settings (never use the database field names): "allow_tracking" must be displayed as "Allow Telemetry", "load_legacy_submissions" must be displayed as "Show Legacy Submissions Page". For API keys (reCAPTCHA, hCaptcha, Turnstile), clearly indicate if they are configured (e.g., "hCaptcha Site Key: [configured]" or "hCaptcha Site Key: [not set]"). For boolean/checkbox settings, present as "Checked" or "Unchecked" based on the value (1/true = Checked, 0/false = Unchecked) - do not interpret the semantic meaning as this can vary by setting. For settings with value "[Action Available]", present them using the format "[Action Available] - {descriptive text}" with the following descriptions: delete_on_uninstall: "Delete all Ninja Forms data when plugin is uninstalled", allow_tracking: "Allow anonymous telemetry to help improve Ninja Forms", trash_expired_submissions: "Automatically move expired form submissions to trash", remove_maintenance_mode: "Use if any of your forms are still in \'Maintenance Mode\' after performing any required updates". CRITICAL: If the user attempts to interact with the "Delete All Data" action (delete_on_uninstall), you MUST stop immediately, present this warning: "ALL Ninja Forms data will be removed from the database and the Ninja Forms plug-in will be deactivated. All form and submission data will be unrecoverable." Then wait for explicit user confirmation before proceeding with any deletion-related actions.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'keys' => array(
						'type'        => 'array',
						'description' => 'Optional array of specific setting keys to retrieve. If omitted, all settings are returned.',
						'items'       => array( 'type' => 'string' ),
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'           => array( 'type' => 'boolean', 'description' => 'Whether the operation succeeded' ),
					'message'           => array( 'type' => 'string', 'description' => 'Human-readable result message' ),
					'settings'          => array( 'type' => 'object', 'description' => 'Plugin settings as flat key-value pairs (for backward compatibility)' ),
					'settings_by_group' => array( 'type' => 'object', 'description' => 'Plugin settings organized by their settings category headers as they appear in the Ninja Forms Settings UI (e.g., "General Settings", "reCaptcha Settings", etc.)' ),
				),
				'required' => array( 'success', 'message', 'settings', 'settings_by_group' ),
			),
			'execute_callback'    => 'ninja_forms_ability_get_plugin_settings',
			'permission_callback' => 'ninja_forms_ability_can_manage_settings',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => true,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_get_plugin_settings', true ),
					'type'   => 'tool',
				),
			),
		)
	);

	// Register update-plugin-settings ability
	wp_register_ability(
		'ninjaforms/update-plugin-settings',
		array(
			'label'               => 'Update Plugin Settings',
			'description'         => 'Update one or more global Ninja Forms plugin settings. Supports all settings including date format, currency, spam protection keys, and advanced options.',
			'category'            => 'forms',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'settings' => array(
						'type'        => 'object',
						'description' => 'Settings to update as key-value pairs',
						'properties'  => array(
							'date_format'             => array( 'type' => 'string', 'description' => 'Date format (e.g., m/d/Y, d/m/Y)' ),
							'currency'                => array( 'type' => 'string', 'description' => 'Currency code (e.g., USD, EUR, GBP)' ),
							'show_welcome'            => array( 'type' => array( 'integer', 'boolean' ), 'description' => 'Show welcome page' ),
							'recaptcha_site_key'      => array( 'type' => 'string', 'description' => 'reCAPTCHA v2 site key' ),
							'recaptcha_secret_key'    => array( 'type' => 'string', 'description' => 'reCAPTCHA v2 secret key' ),
							'recaptcha_site_key_3'    => array( 'type' => 'string', 'description' => 'reCAPTCHA v3 site key' ),
							'recaptcha_secret_key_3'  => array( 'type' => 'string', 'description' => 'reCAPTCHA v3 secret key' ),
							'recaptcha_lang'          => array( 'type' => 'string', 'description' => 'reCAPTCHA language code' ),
							'recaptcha_theme'         => array( 'type' => 'string', 'description' => 'reCAPTCHA theme', 'enum' => array( 'light', 'dark' ) ),
							'turnstile_site_key'      => array( 'type' => 'string', 'description' => 'Cloudflare Turnstile site key' ),
							'turnstile_secret_key'    => array( 'type' => 'string', 'description' => 'Cloudflare Turnstile secret key' ),
							'turnstile_theme'         => array( 'type' => 'string', 'description' => 'Turnstile theme', 'enum' => array( 'light', 'dark', 'auto' ) ),
							'turnstile_size'          => array( 'type' => 'string', 'description' => 'Turnstile size', 'enum' => array( 'normal', 'compact' ) ),
							'hcaptcha_site_key'       => array( 'type' => 'string', 'description' => 'hCaptcha site key' ),
							'hcaptcha_secret_key'     => array( 'type' => 'string', 'description' => 'hCaptcha secret key' ),
							'hcaptcha_theme'          => array( 'type' => 'string', 'description' => 'hCaptcha theme', 'enum' => array( 'light', 'dark' ) ),
							'hcaptcha_size'           => array( 'type' => 'string', 'description' => 'hCaptcha size', 'enum' => array( 'normal', 'compact' ) ),
							'delete_on_uninstall'     => array( 'type' => array( 'integer', 'boolean' ), 'description' => 'Delete all data on uninstall' ),
							'disable_admin_notices'   => array( 'type' => array( 'integer', 'boolean' ), 'description' => 'Disable admin notices' ),
							'builder_dev_mode'        => array( 'type' => array( 'integer', 'boolean' ), 'description' => 'Enable form builder dev mode' ),
							'load_legacy_submissions' => array( 'type' => array( 'integer', 'boolean' ), 'description' => 'Show legacy submissions page' ),
							'opinionated_styles'      => array( 'type' => 'string', 'description' => 'Default styling', 'enum' => array( '', 'light', 'dark' ) ),
						),
					),
				),
				'required' => array( 'settings' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean', 'description' => 'Whether the operation succeeded' ),
					'message' => array( 'type' => 'string', 'description' => 'Human-readable result message' ),
					'updated' => array( 'type' => 'object', 'description' => 'Settings that were updated' ),
				),
				'required' => array( 'success', 'message', 'updated' ),
			),
			'execute_callback'    => 'ninja_forms_ability_update_plugin_settings',
			'permission_callback' => 'ninja_forms_ability_can_manage_settings',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'     => false,
					'destructive'  => false,
					'idempotent'   => false,
				),
				'mcp'          => array(
					'public' => apply_filters( 'ninjaforms_mcp_public_update_plugin_settings', true ),
					'type'   => 'tool',
				),
			),
		)
	);
}

add_action( 'wp_abilities_api_init', 'ninja_forms_register_abilities' );
