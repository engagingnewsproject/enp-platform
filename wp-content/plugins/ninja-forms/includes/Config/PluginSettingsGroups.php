<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_plugin_settings_groups', array(

    'general' => array(
        'id' => 'general',
        'label' => esc_html__( 'General Settings', 'ninja-forms' ),
    ),

    'recaptcha' => array(
        'id' => 'recaptcha',
        'label' => esc_html__( 'reCaptcha Settings', 'ninja-forms' ) . ' <a href="https://ninjaforms.com/docs/google-recaptcha/?utm_source=Ninja+Forms+Plugin&utm_medium=Settings&utm_campaign=Documentation&utm_content=reCAPTCHA+Documentation" target="_blank"><img src="' . Ninja_Forms::$url . 'assets/img/help_icon.png" alt="Documentation Link" width="25" height="25"></a>',
    ),

    'advanced' => array(
        'id' => 'advanced',
        'label' => esc_html__( 'Advanced Settings', 'ninja-forms' ),
    ),

    'saved_fields' => array(
        'id' => 'saved_fields',
        'label' => esc_html__( 'Favorite Fields', 'ninja-forms' ),
    ),

));
