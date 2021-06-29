<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'nf_constant_contact_plugin_settings', array(
    /*
    |--------------------------------------------------------------------------
    | Constant Contact Access Token
    |--------------------------------------------------------------------------
    */

    'constant_contact_access_token' => array(
        'id'    => 'constant_contact_access_token',
        'type'  => 'textbox',
        'label' => __( 'Constant Contact Access Token', 'ninja-forms-constant-contact' ),
        'desc' => sprintf(
            __( 'Enter your Constant Contact Access Token. %sClick here%s to generate an access token then copy the token here.', 'ninja-forms-constant-contact' ),
            '<a href="https://oauth.ninjaforms.com/constant-contact/oauth.php?admin_url=' . urlencode( admin_url() . 'admin.php?page=nf-settings' ) . '&plugin_version=' . NF_ConstantContact::VERSION . '" target="_blank">', '</a>'
        ),
    ),
));
