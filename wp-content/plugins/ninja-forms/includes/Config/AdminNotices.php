<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'ninja_forms_admin_notices', array(

    /*
    |--------------------------------------------------------------------------
    | One Week Support
    |--------------------------------------------------------------------------
    */

    'one_week_support' => array(
        'title' => esc_html__( 'How\'s It Going?', 'ninja-forms' ),
        'msg' => esc_html__( 'Thank you for using Ninja Forms! We hope that you\'ve found everything you need, but if you have any questions:', 'ninja-forms' ),
        'link' => '<li><a target="_blank" href="https://ninjaforms.com/documentation/?utm_source=Ninja+Forms+Plugin&utm_medium=Admin+Notice&utm_campaign=Thank+You+Notice&utm_content=Thank+You+Docs+Link">' . esc_html__( 'Check out our documentation', 'ninja-forms' ) . '</a></li>
                   <li><a target="_blank" href="https://ninjaforms.com/contact/?utm_source=Ninja+Forms+Plugin&utm_medium=Admin+Notice&utm_campaign=Thank+You+Notice&utm_content=Thank+You+Support+Link">' . esc_html__( 'Get Some Help' ,'ninja-forms' ) . '</a></li>
                   <li><a href="' . wp_nonce_url( add_query_arg( array( 'nf_admin_notice_ignore' => 'one_week_support' ) ), "nf_admin_notice_ignore" ) .  '">' . esc_html__( 'Dismiss' ,'ninja-forms' ) . '</a></li>',
        'blacklist' => array( 'ninja-forms-three' ),
    ),

));
