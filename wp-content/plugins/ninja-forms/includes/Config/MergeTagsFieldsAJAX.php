<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_fields_ajax', array(

    'all_fields_table' => array(
        'id' => 'all_fields_table',
        'tag' => '{all_fields_table}',
        'label' => esc_html__( 'All Fields Table', 'ninja-forms' ),
        'callback' => 'all_fields_table',
        'fields' => array()
    ),

    'fields_table' => array(
        'id' => 'fields_table',
        'tag' => '{fields_table}',
        'label' => esc_html__( 'Fields Table', 'ninja-forms' ),
        'callback' => 'fields_table',
        'fields' => array()
    ),

    /*
     |--------------------------------------------------------------------------
     | DEPRECATED
     |--------------------------------------------------------------------------
     */

    'all_fields' => array(
        'id' => 'all_fields',
        'tag' => '{field:all_fields}',
        'label' => esc_html__( 'All Fields', 'ninja-forms' ),
        'callback' => 'all_fields',
        'fields' => array()
    ),

));