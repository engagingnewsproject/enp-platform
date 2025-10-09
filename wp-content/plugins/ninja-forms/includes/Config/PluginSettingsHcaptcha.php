<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_hcaptcha', array(

    /*
    |--------------------------------------------------------------------------
    | Site Key
    |--------------------------------------------------------------------------
    */

    'hcaptcha_site_key' => array(
        'id'    => 'hcaptcha_site_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'hCaptcha Site Key', 'ninja-forms' ),
        'desc'  => sprintf( esc_html__( 'Get a site key for your domain by registering %shere%s', 'ninja-forms' ), '<a href="https://dashboard.hcaptcha.com/signup" target="_blank">', '</a>' ),
        'value' => '',
    ),

    /*
    |--------------------------------------------------------------------------
    | Secret Key
    |--------------------------------------------------------------------------
    */

    'hcaptcha_secret_key' => array(
        'id'    => 'hcaptcha_secret_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'hCaptcha Secret Key', 'ninja-forms' ),
        'desc'  => esc_html__( 'This key should be kept private and secure.', 'ninja-forms' ),
        'value' => '',
    ),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    'hcaptcha_theme' => array(
        'id'    => 'hcaptcha_theme',
        'type'  => 'select',
        'options'   => array(
            array( 'label' => esc_html__( 'Light', 'ninja-forms' ), 'value' => 'light' ),
            array( 'label' => esc_html__( 'Dark', 'ninja-forms' ), 'value' => 'dark' ),
        ),
        'label' => esc_html__( 'hCaptcha Theme', 'ninja-forms' ),
        'desc'  => esc_html__( 'Select the visual theme for the hCaptcha widget', 'ninja-forms' ),
        'value' => 'light',
    ),

    /*
    |--------------------------------------------------------------------------
    | Size
    |--------------------------------------------------------------------------
    */

    'hcaptcha_size' => array(
        'id'    => 'hcaptcha_size',
        'type'  => 'select',
        'options'   => array(
            array( 'label' => esc_html__( 'Normal', 'ninja-forms' ), 'value' => 'normal' ),
            array( 'label' => esc_html__( 'Compact', 'ninja-forms' ), 'value' => 'compact' ),
        ),
        'label' => esc_html__( 'hCaptcha Size', 'ninja-forms' ),
        'desc'  => esc_html__( 'Select the size of the hCaptcha widget', 'ninja-forms' ),
        'value' => 'normal',
    ),

));