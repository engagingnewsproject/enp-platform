<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_form_display_settings', array(

	/*
	* FORM TITLE
	*/

	'title' => array(
		'name' => 'title',
		'type' => 'textbox',
		'label' => esc_html__( 'Form Title', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'primary',
		'value' => '',
		'help'  => esc_html__( 'The name entered here will be used as this form’s title both in the Ninja Forms Dashboard for admin purposes, and on the published form itself if enabled by the Display Form Title Setting below.', 'ninja-forms' ),
	),

	/*
	* SHOW FORM TITLE
	*/

	'show_title' => array(
		'name' => 'show_title',
		'type' => 'toggle',
		'label' => esc_html__( 'Display Form Title', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'primary',
		'value' => 1,
		'help'  => esc_html__( 'Turn on or off the front end display of the Form Title on published forms.', 'ninja-forms' ),
	),

	/*
	* ALLOW PUBLIC LINK
	*/

	'allow_public_link' => array(
		'name' => 'allow_public_link',
		'type' => 'toggle',
		'label' => esc_html__( 'Allow a public link?', 'ninja-forms' ),
		'width' => 'full',
		'group' => '',
		'value' => 0,
		'help'  => esc_html__( 'If this box is checked, Ninja Forms will create a public link to access the form.', 'ninja-forms' ),
	),

	'public_link' => array(
		'name' => 'public_link',
		'type' => 'copyresettext',
		'label' => esc_html__( 'Link To Your Form', 'ninja-forms' ),
		'width' => 'full',
		'group' => '',
		'help'  => esc_html__( 'A public link to access the form.', 'ninja-forms' ),
		'deps' => array(
			'allow_public_link' => 1
		),
	),

	'embed_form' => array(
		'name' => 'embed_form',
		'type' => 'copytext',
		'label' => esc_html__( 'Embed Your Form', 'ninja-forms' ),
		'width' => 'full',
		'group' => '',
		'value' => '',
		'help'  => esc_html__( 'The shortcode you can use to embed this form on a page or post.', 'ninja-forms' ),
	),

	/*
	* CLEAR SUCCESSFULLY COMPLETED FORM
	*/

	'clear_complete' => array(
		'name' => 'clear_complete',
		'type' => 'toggle',
		'label' => esc_html__( 'Clear successfully completed form?', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'primary',
		'value' => 1,
		'help'  => esc_html__( 'If this box is checked, Ninja Forms will clear the form values after it has been successfully submitted.', 'ninja-forms' ),
	),

	/*
	* HIDE SUCCESSFULLY COMPLETED FORMS
	*/

	'hide_complete' => array(
		'name' => 'hide_complete',
		'type' => 'toggle',
		'label' => esc_html__( 'Hide successfully completed form?', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'primary',
		'value' => 1,
		'help'  => esc_html__( 'If this box is checked, Ninja Forms will hide the form after it has been successfully submitted.', 'ninja-forms' ),
	),

	/*
	* Default Label Position
	*/

	'default_label_pos' => array(
		'name' => 'default_label_pos',
		'type' => 'select',
		'label' => esc_html__( 'Default Label Position', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'advanced',
		'options' => array(
			array(
				'label' => esc_html__( 'Above Element', 'ninja-forms' ),
				'value' => 'above'
			),
			array(
				'label' => esc_html__( 'Below Element', 'ninja-forms' ),
				'value' => 'below'
			),
			array(
				'label' => esc_html__( 'Left of Element', 'ninja-forms' ),
				'value' => 'left'
			),
			array(
				'label' => esc_html__( 'Right of Element', 'ninja-forms' ),
				'value' => 'right'
			),
			array(
				'label' => esc_html__( 'Hidden', 'ninja-forms' ),
				'value' => 'hidden'
			),
		),
		'value' => 'above',
	),

	/*
	 * Classes
	 */

	'classes' => array(
		'name' => 'classes',
		'type' => 'fieldset',
		'label' => esc_html__( 'Custom Class Names', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'advanced',
		'settings' => array(
			array(
				'name' => 'wrapper_class',
				'type' => 'textbox',
				'placeholder' => '',
				'label' => esc_html__( 'Wrapper', 'ninja-forms' ),
				'width' => 'one-half',
				'value' => '',
				'use_merge_tags' => FALSE,
			),
			array(
				'name' => 'element_class',
				'type' => 'textbox',
				'label' => esc_html__( 'Element', 'ninja-forms' ),
				'placeholder' => '',
				'width' => 'one-half',
				'value' => '',
				'use_merge_tags' => FALSE,
			),
		),
	),

	/*
	* FORM TITLE HEADING LEVEL
	*/

	'form_title_heading_level' => array(
		'name' => 'form_title_heading_level',
		'type' => 'select',
		'label' => esc_html__( 'Form Title Heading Level', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'advanced',
		'options' => array(
			array(
				'label' => esc_html__( 'H1', 'ninja-forms' ),
				'value' => '1'
			),
			array(
				'label' => esc_html__( 'H2', 'ninja-forms' ),
				'value' => '2'
			),
			array(
				'label' => esc_html__( 'H3', 'ninja-forms' ),
				'value' => '3'
			),
			array(
				'label' => esc_html__( 'H4', 'ninja-forms' ),
				'value' => '4'
			),
			array(
				'label' => esc_html__( 'H5', 'ninja-forms' ),
				'value' => '5'
			),
			array(
				'label' => esc_html__( 'H6', 'ninja-forms' ),
				'value' => '6'
			),
		),
		'value' => '3',
	),

	/*
	 * KEY
	 */

	'key' => array(
		'name' => 'key',
		'type' => 'textbox',
		'label' => esc_html__( 'Form Key', 'ninja-forms'),
		'width' => 'full',
		'group' => 'administration',
		'value' => '',
		'help' => esc_html__( 'Programmatic name that can be used to reference this form.', 'ninja-forms' ),
	),

	/*
	 * ADD SUBMIT CHECKBOX
	 */

	'add_submit' => array(
		'name' => 'add_submit',
		'type' => 'toggle',
		'label' => esc_html__( 'Add Submit Button', 'ninja-forms'),
		'width' => 'full',
		'group' => '',
		'value' => 1,
		'help' => esc_html__( 'We have noticed that you do not have a submit button on your form. We can add one for you automatically.', 'ninja-forms' ),
	),

	/*
	 * Form Labels
	 */

	'custom_messages' => array(
		'name' => 'custom_messages',
		'type' => 'fieldset',
		'label' => esc_html__( 'Custom Labels', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'advanced',
		'settings' => array(
			array(
				'name' => 'changeEmailErrorMsg',
				'type' => 'textbox',
				'label' => esc_html__( 'Please enter a valid email address!', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Email field error for improperly formatted email address.', 'ninja-forms' ),
			),
			array(
				'name' => 'changeDateErrorMsg',
				'type' => 'textbox',
				'label' => esc_html__( 'Please enter a valid date!', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Date field error for improperly formatted date.', 'ninja-forms' ),
			),
			array(
				'name' => 'confirmFieldErrorMsg',
				'type' => 'textbox',
				'label' => esc_html__( 'These fields must match!', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Error displayed when using a Confirm field to force a match in another field.', 'ninja-forms' ),
			),
			array(
				'name' => 'fieldNumberNumMinError',
				'type' => 'textbox',
				'label' => esc_html__( 'Number Min Error', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Number field error displayed when a value entered that is less than the designated minimum value.', 'ninja-forms' ),
			),
			array(
				'name' => 'fieldNumberNumMaxError',
				'type' => 'textbox',
				'label' => esc_html__( 'Number Max Error', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Number field error displayed when a value entered that is greater than the designated maximum value.', 'ninja-forms' ),
			),
			array(
				'name' => 'fieldNumberIncrementBy',
				'type' => 'textbox',
				'label' => esc_html__( 'Please increment by ', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Number field error displayed when a value entered does not meet the required value incrementation. (e.g. the field is set to increment by tens but a number not divisible by ten is entered)', 'ninja-forms' ),
			),
			array(
				'name' => 'formErrorsCorrectErrors',
				'type' => 'textbox',
				'label' => esc_html__( 'Please correct errors before submitting this form.', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Error that is displayed when attempting to submit a form with an unresolved error message still present on the form.', 'ninja-forms' ),
			),
			array(
				'name' => 'validateRequiredField',
				'type' => 'textbox',
				'label' => esc_html__( 'This is a required field.', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Error displayed when a field designated as required is left blank after interacting with that field.', 'ninja-forms' ),
			),
			array(
				'name' => 'honeypotHoneypotError',
				'type' => 'textbox',
				'label' => esc_html__( 'Honeypot Error', 'ninja-forms' ),
				'width' => 'full',
				'help'  => esc_html__( 'Administrative only. Displayed on a form when the hidden honeypot field is interacted with by a bot (e.g. spam) and the submission attempt is rejected.', 'ninja-forms' ),
			),
			array(
				'name' => 'fieldsMarkedRequired',
				'type' => 'textbox',
				'label' => sprintf( esc_html__( 'Fields marked with an %s*%s are required', 'ninja-forms' ), '<span class="ninja-forms-req-symbol">', '</span>' ),
				'width' => 'full',
				'help'  => esc_html__( 'Error displayed when a field designated as required is left blank and the user attempts to submit the form.', 'ninja-forms' ),
			),
		)
	),

	/*
	 * CURRENCY
	 */

	'currency' => array(
		'name'      => 'currency',
		'type'    => 'select',
		'options' => array_merge( array( array( 'label' => esc_html__( 'Plugin Default', 'ninja-forms' ), 'value' => '' ) ), Ninja_Forms::config( 'Currency' ) ),
		'label'   => esc_html__( 'Currency', 'ninja-forms' ),
		'width' => 'full',
		'group' => 'advanced',
		'value'   => '',
		'help'  => esc_html__( 'By default the form will display currency in the proper format as determined by the language selected under the WordPress General settings for the site. If you wish to use a different currency format in the form, it can be selected here from supported options.', 'ninja-forms' ),
	)

));
