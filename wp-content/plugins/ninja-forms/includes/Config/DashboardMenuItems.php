<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_dashboard_menu_items', array(

    'widgets' => array(
        'slug' => 'widgets',
        'niceName' => esc_html__( 'Forms', 'ninja-forms' ),
    ),
    'apps' => array(
        'slug' => 'add-ons',
        'niceName' => esc_html__( 'Add-ons', 'ninja-forms' ),
    ),
    'user_access' => array(
        'slug' => 'user-access',
        'niceName' => esc_html__( 'User access', 'ninja-forms' )
    ),
    'services' => array(
        'slug' => 'services',
        'niceName' => esc_html__( 'Partner Apps & Services', 'ninja-forms' ),
    ),
));
