<?php

if( ! defined( 'NJ_CC_API_KEY' ) ) {
	define( 'NJ_CC_API_KEY', 'd4tkm7yt9chm5bmfc32txtj6' );
}

/**
 * Plugin text domain
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cc_textdomain() {

	// Set filter for plugin's languages directory
	$edd_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$edd_lang_dir = apply_filters( 'ninja_forms_cc_languages_directory', $edd_lang_dir );

	// Load the translations
	load_plugin_textdomain( 'ninja-forms-cc', false, $edd_lang_dir );
}
add_action( 'init', 'ninja_forms_cc_textdomain' );


/**
 * Add the Constant Contact tab to the Plugin Settings screen
 *
 * @since       1.0
 * @return      void
 */

function ninja_forms_cc_add_tab() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$tab_args              = array(
		'name'             => 'Constant Contact',
		'page'             => 'ninja-forms-settings',
		'display_function' => '',
		'save_function'    => 'ninja_forms_save_license_settings',
		'tab_reload'       => true,
	);
	ninja_forms_register_tab( 'constant_contact', $tab_args );

}
add_action( 'admin_init', 'ninja_forms_cc_add_tab' );


/**
 * PRegister the settings in the Constant Contact Tab
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cc_add_plugin_settings() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$mc_args = array(
		'page'     => 'ninja-forms-settings',
		'tab'      => 'constant_contact',
		'slug'     => 'constant_contact',
		'title'    => __( 'Constant Contact', 'ninja-forms-cc' ),
		'settings' => array(
			array(
				'name' => 'constant_contact_access_token',
				'label' => __( 'Constant Contact Access Token', 'ninja-forms-cc' ),
				'desc' => sprintf(
					__( 'Enter your Constant Contact Access Token. <a href="%s" target="_blank">Click here</a> to generate an access token then copy the token here.', 'ninja-forms-cc' ),
					'http://pippinsplugins.com/constant-contact/index.php'
				),
				'type' => 'text',
				'size' => 'regular'
			)
		)
	);
	ninja_forms_register_tab_metabox( $mc_args );
}
add_action( 'admin_init', 'ninja_forms_cc_add_plugin_settings', 100 );


/**
 * Register the form-specific settings
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cc_add_form_settings() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$args = array();
	$args['page'] = 'ninja-forms';
	$args['tab']  = 'form_settings';
	$args['slug'] = 'basic_settings';
	$args['settings'] = array(
		array(
			'name'      => 'constant_contact_signup_form',
			'type'      => 'checkbox',
			'label'     => __( 'Constant Contact', 'ninja-forms-cc' ),
			'desc'      => __( 'Enable Constant Contact signup for this form?', 'ninja-forms-cc' ),
			'help_text' => __( 'This will cause all email fields in this form to be sent to Constant Contact', 'ninja-forms-cc' ),
		),
		array(
			'name'    => 'ninja_forms_cc_list',
			'label'   => __( 'Choose a list', 'edda' ),
			'desc'    => __( 'Select the list you wish to subscribe users to when submitting the form', 'ninja-forms-cc' ),
			'type'    => 'select',
			'options' => ninja_forms_cc_get_constant_contact_lists()
		)
	);
	ninja_forms_register_tab_metabox_options( $args );

}
add_action( 'admin_init', 'ninja_forms_cc_add_form_settings', 100 );

/**
 * Retrieve an array of Constant Contact lists
 *
 * @since       1.0
 * @return      array
 */
function ninja_forms_cc_get_constant_contact_lists() {

	global $pagenow, $edd_settings_page;

	if ( ! isset( $_GET['page'] ) || ! isset( $_GET['tab'] ) || $_GET['page'] != 'ninja-forms' || $_GET['tab'] != 'form_settings' )
		return;
	$options = get_option( "ninja_forms_settings" );

	if ( ! empty( $options['constant_contact_access_token'] ) ) {

		$lists = array();

		$api_url  = 'https://api.constantcontact.com/v2/lists?api_key=' . NJ_CC_API_KEY;
		$headers  = array( 'Authorization' => 'Bearer ' . trim( $options['constant_contact_access_token'] ) );

		$query    = wp_remote_get( $api_url, array( 'headers' => $headers, 'sslverify' => false ) );

		if( is_wp_error( $query ) )
			return $lists;

		if( 200 != $query['response']['code'] )
			return $lists;

		$body = wp_remote_retrieve_body( $query );

		$list_data = json_decode( $body );

		if ( $list_data ) {
			foreach ( $list_data as $list ) {
				$lists[] = array(
					'value' => $list->id,
					'name'  => $list->name
				);
			}
		}

		return $lists;
	}
	return array();
}


/**
 * Subscribe an email address to a Constant Contact list
 *
 * @since       1.0
 * @return      bool
 */
function ninja_forms_cc_subscribe_email( $subscriber = array(), $list_id = '' ) {

	$options = get_option( "ninja_forms_settings" );

	if ( empty( $list_id ) || empty( $subscriber ) )
		return false;

	$api_url_args = array(
		'api_key'   => NJ_CC_API_KEY,
		'action_by' => 'ACTION_BY_VISITOR'
	);
	$api_url  = add_query_arg( $api_url_args, 'https://api.constantcontact.com/v2/contacts' );
	$headers  = array(
		'Authorization' => 'Bearer ' . trim( $options['constant_contact_access_token'] ),
		'Content-Type'  => 'application/json'
	);

	$api_body = json_encode( array(
		'email_addresses' => array(
			array(
				'email_address' => $subscriber['email'],
				'opt_in_source' => 'ACTION_BY_VISITOR'
			)
		),
		'first_name' => ! empty( $subscriber['first_name'] ) ? $subscriber['first_name'] : '',
		'last_name'  => ! empty( $subscriber['last_name'] ) ? $subscriber['last_name'] : '',
		'lists'      => array(
			array(
				'id' => $list_id
			)
		)
	) );

	$query = wp_remote_post( $api_url, array( 'headers' => $headers, 'sslverify' => false, 'body' => $api_body ) );

	if( is_wp_error( $query ) )
		return false;

	if( 201 != $query['response']['code'] )
		return false;

	// Contact added successfully
	return true;
}


/**
 * Check for newsletter signups on form submission
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cc_check_for_email_signup() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	global $ninja_forms_processing;

	$form = $ninja_forms_processing->get_all_form_settings();

	// Check if Constant Contact is enabled for this form
	if ( empty( $form['constant_contact_signup_form'] ) )
		return;

	//Get all the user submitted values
	$all_fields = $ninja_forms_processing->get_all_fields();

	if ( is_array( $all_fields ) ) { //Make sure $all_fields is an array.
		//Loop through each of our submitted values.
		$subscriber = array();
		foreach ( $all_fields as $field_id => $value ) {

			$field = $ninja_forms_processing->get_field_settings( $field_id );

			if ( ! empty( $field['data']['email'] ) && is_email( $value ) ) {
				$subscriber['email'] = $value;
			}

			if ( ! empty( $field['data']['first_name'] ) ) {
				$subscriber['first_name'] = $value;
			}

			if ( ! empty( $field['data']['last_name'] ) ) {
				$subscriber['last_name'] = $value;
			}

		}
		if ( ! empty( $subscriber ) ) {
			ninja_forms_cc_subscribe_email( $subscriber, $form['ninja_forms_cc_list'] );
		}
	}
}


/**
 * Connect our signup check to form processing
 *
 * @since       1.0
 * @return      void
 */
function ninja_forms_cc_hook_into_processing() {
	add_action( 'ninja_forms_process', 'ninja_forms_cc_check_for_email_signup' );
}
add_action( 'init', 'ninja_forms_cc_hook_into_processing' );


/**
 * Plugin Updater / licensing
 *
 * @since       1.0.3
 * @return      void
 */

function ninja_forms_cc_extension_setup_license() {
    if ( class_exists( 'NF_Extension_Updater' ) ) {
        $NF_Extension_Updater = new NF_Extension_Updater( 'Constant Contact', '3.0.6', 'Pippin Williamson', __FILE__, 'constant_contact' );
    }
}
add_action( 'admin_init', 'ninja_forms_cc_extension_setup_license' );
