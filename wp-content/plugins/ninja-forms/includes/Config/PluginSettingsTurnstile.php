<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_turnstile', array(

    /*
    |--------------------------------------------------------------------------
    | Site Key
    |--------------------------------------------------------------------------
    */

    'turnstile_site_key' => array(
        'id'    => 'turnstile_site_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'Cloudflare Turnstile Site Key', 'ninja-forms' ),
        'desc'  => sprintf( esc_html__( 'Get a site key for your domain by registering %shere%s', 'ninja-forms' ), '<a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank">', '</a>' )
    ),

    /*
    |--------------------------------------------------------------------------
    | Secret Key
    |--------------------------------------------------------------------------
    */

    'turnstile_secret_key' => array(
        'id'    => 'turnstile_secret_key',
        'type'  => 'textbox',
        'label' => esc_html__( 'Cloudflare Turnstile Secret Key', 'ninja-forms' ),
        'desc'  => '',
    ),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    'turnstile_theme' => array(
        'id'    => 'turnstile_theme',
        'type'  => 'select',
        'options'   => array(
            array( 'label' => esc_html__( 'Light', 'ninja-forms' ), 'value' => 'light' ),
            array( 'label' => esc_html__( 'Dark', 'ninja-forms' ), 'value' => 'dark' ),
            array( 'label' => esc_html__( 'Auto', 'ninja-forms' ), 'value' => 'auto' ),
        ),
        'label' => esc_html__( 'Turnstile Theme', 'ninja-forms' ),
        'desc'  => esc_html__( 'Select the visual theme for the Turnstile widget', 'ninja-forms' ),
        'value' => 'auto',
    ),

    /*
    |--------------------------------------------------------------------------
    | Size
    |--------------------------------------------------------------------------
    */

    'turnstile_size' => array(
        'id'    => 'turnstile_size',
        'type'  => 'select',
        'options'   => array(
            array( 'label' => esc_html__( 'Normal', 'ninja-forms' ), 'value' => 'normal' ),
            array( 'label' => esc_html__( 'Compact', 'ninja-forms' ), 'value' => 'compact' ),
        ),
        'label' => esc_html__( 'Turnstile Size', 'ninja-forms' ),
        'desc'  => esc_html__( 'Select the size of the Turnstile widget', 'ninja-forms' ),
        'value' => 'normal',
    ),

));