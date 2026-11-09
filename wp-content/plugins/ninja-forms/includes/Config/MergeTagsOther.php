<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_merge_tags_other', array(

    /*
    |--------------------------------------------------------------------------
    | Querystring
    |--------------------------------------------------------------------------
    */
    'query_string' => array(
        'tag' => '{querystring:YOUR_KEY}',
        'label' => esc_html__( 'Query String', 'ninja-forms' ),
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
        'label' => esc_html__( 'Date', 'ninja-forms' ),
        'callback' => 'system_date'
    ),

    // Share the File Uploads key so its callback can override without duplication.
    'fu_date' => array(
        'id' => 'fu_date',
        'tag' => '{other:formatted_date}',
        'label' => __( 'Date in yyyy-mm-dd format', 'ninja-forms' ),
        'callback' => 'mergetag_formatted_date'
    ),

    /*
    |--------------------------------------------------------------------------
    | System Date
    |--------------------------------------------------------------------------
    */

    'time' => array(
        'id' => 'time',
        'tag' => '{other:time}',
        'label' => esc_html__( 'Time', 'ninja-forms' ),
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
        'label' => esc_html__( 'User IP Address', 'ninja-forms' ),
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
        'label' => esc_html__( 'Referer URL', 'ninja-forms' ),
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
        'label'     => esc_html__( 'Random 5 character string.', 'ninja-forms' ),
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
