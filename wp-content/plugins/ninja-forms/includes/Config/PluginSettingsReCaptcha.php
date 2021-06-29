<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_recaptcha', array(

    /*
    |--------------------------------------------------------------------------
    | Site Key
    |--------------------------------------------------------------------------
    */

    'recaptcha_site_key' => array(
        'id'    => 'recaptcha_site_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'reCAPTCHA v2 Site Key', 'ninja-forms' ),
        'desc'  => sprintf( esc_html__( 'Get a site key for your domain by registering %shere%s', 'ninja-forms' ), '<a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">', '</a>' )
    ),

    /*
    |--------------------------------------------------------------------------
    | Secret Key
    |--------------------------------------------------------------------------
    */

    'recaptcha_secret_key' => array(
        'id'    => 'recaptcha_secret_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'reCAPTCHA v2 Secret Key', 'ninja-forms' ),
        'desc'  => '',
    ),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    'recaptcha_theme' => array(
        'id'    => 'recaptcha_theme',
        'type'  => 'select',
        'options'   => array(
            array( 'label' => esc_html__( 'Light', 'ninja-forms' ), 'value' => 'light' ),
            array( 'label' => esc_html__( 'Dark', 'ninja-forms' ), 'value' => 'dark' ),
        ),
        'label' => esc_html__( 'reCAPTCHA Theme', 'ninja-forms' ),
        'desc'  => '',
    ),

    /*
   |--------------------------------------------------------------------------
   | Language
   |--------------------------------------------------------------------------
   */

    'recaptcha_lang' => array(
	    'id'    => 'recaptcha_lang',
	    'type'  => 'textbox',
	    'label' => esc_html__( 'reCAPTCHA Language', 'ninja-forms' ),
	    'desc'  => 'e.g. en, da - ' . sprintf( esc_html__( 'Language used by reCAPTCHA. To get the code for your language click %shere%s', 'ninja-forms' ), '<a href="https://developers.google.com/recaptcha/docs/language" target="_blank">', '</a>' )
    ),
    
    /*
	|--------------------------------------------------------------------------
	| Site Key v3
	|--------------------------------------------------------------------------
	*/

    'recaptcha_site_key_3' => array(
	    'id'    => 'recaptcha_site_key_3',
	    'type'  => 'textbox',
	    'label' => esc_html__( 'reCAPTCHA v3 Site Key', 'ninja-forms' ),
	    'desc'  => sprintf( esc_html__( 'Get a site key for your domain by registering %shere%s', 'ninja-forms' ), '<a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">', '</a>' )
    ),

    /*
	|--------------------------------------------------------------------------
	| Secret Key v3
	|--------------------------------------------------------------------------
	*/

    'recaptcha_secret_key_3' => array(
	    'id'    => 'recaptcha_secret_key_3',
	    'type'  => 'textbox',
	    'label' => esc_html__( 'reCAPTCHA v3 Secret Key', 'ninja-forms' ),
	    'desc'  => '',
    ),
));
