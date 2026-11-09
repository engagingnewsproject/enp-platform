<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_example_settings', array(

    /*
    |--------------------------------------------------------------------------
    | Section Settings
    |--------------------------------------------------------------------------
    |
    | Section description here.
    |
    */

    /*
     * SETTING NAME
     */

    'setting_name_here' => array(
        'name' => 'setting_name_here',
        'type' => 'textbox', // 'textarea', 'number', 'toggle', etc
        'label' => 'Label Here',
        'width' => 'one-half', // 'full', 'one-half', 'one-third'
        'group' => 'primary', // 'primary', 'restrictions', 'advanced'
        'value' => '',
        'help' => 'Help Text Here',
        'use_merge_tags' => TRUE, // TRUE or FALSE
    ),

));