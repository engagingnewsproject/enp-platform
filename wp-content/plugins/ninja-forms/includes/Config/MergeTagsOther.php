<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_other', array(

    /*
    |--------------------------------------------------------------------------
    | Querystring
    |--------------------------------------------------------------------------
    */
    'query_string' => array(
        'tag' => '{querystring:YOUR_KEY}',
        'label' => esc_html__( 'Query String', 'ninja_forms' ),
        'callback' => null,
    ),

    /*
    |--------------------------------------------------------------------------
    | System Date
    |--------------------------------------------------------------------------
    */

    'date' => array(
        'id' => 'date',
        'tag' => '{other:date}',
        'label' => esc_html__( 'Date', 'ninja_forms' ),
        'callback' => 'system_date'
    ),

    /*
    |--------------------------------------------------------------------------
    | System Date
    |--------------------------------------------------------------------------
    */

    'time' => array(
        'id' => 'time',
        'tag' => '{other:time}',
        'label' => esc_html__( 'Time', 'ninja_forms' ),
        'callback' => 'system_time'
    ),

    /*
    |--------------------------------------------------------------------------
    | System IP Address
    |--------------------------------------------------------------------------
    */

    'ip' => array(
        'id' => 'ip',
        'tag' => '{other:user_ip}',
        'label' => esc_html__( 'User IP Address', 'ninja_forms' ),
        'callback' => 'user_ip'
    ),

    /*
    |--------------------------------------------------------------------------
    | Referer URL
    |--------------------------------------------------------------------------
    */

    'referer_url' => array(
        'id' => 'referer_url',
        'tag' => '{other:referer_url}',
        'label' => esc_html__( 'Referer URL', 'ninja_forms' ),
        'callback' => 'referer_url'
    ),

    /*
    |--------------------------------------------------------------------------
    | Random String
    |--------------------------------------------------------------------------
    */

    'mergetag_random' => array(
        'id'        => 'mergetag_random',
        'tag'       => '{other:random}',
        'label'     => esc_html__( 'Random 5 character string.', 'ninja_forms' ),
        'callback'  => 'mergetag_random'
    ),

    /*
    |--------------------------------------------------------------------------
    | Year
    |--------------------------------------------------------------------------
    */

    'mergetag_year' => array(
        'id'        => 'mergetag_year',
        'tag'       => '{other:year}',
        'label'     => __( 'Year in yyyy format', 'ninja-forms' ),
        'callback'  => 'mergetag_year'
    ),

    /*
    |--------------------------------------------------------------------------
    | Month
    |--------------------------------------------------------------------------
    */

    'mergetag_month' => array(
        'id'         => 'mergetag_month',
        'tag'        => '{other:month}',
        'label'      => __( 'Month in mm format', 'ninja-forms' ),
        'callback'   => 'mergetag_month'
    ),

    /*
    |--------------------------------------------------------------------------
    | Day
    |--------------------------------------------------------------------------
    */

    'mergetag_day' => array(
        'id'       => 'mergetag_day',
        'tag'      => '{other:day}',
        'label'    => __( 'Day in dd format', 'ninja-forms' ),
        'callback' => 'mergetag_day'
    ),

)); 