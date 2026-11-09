<?php

/**
 * Array of compatibility checks and notices
 */
return [
    [
        'className' => 'NF_Authorize_Net',
        'minVersion' => '3.1.1',
        /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
        'title' => sprintf( esc_html__( 'Attention %s users', 'ninja-forms' ), 'Authorize.net' ),
        'message' =>
            /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
            sprintf( esc_html__( 'Please update your %s add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms.', 'ninja-forms' ), 'Authorize.net' )
            . '<br />'
            /* translators: 1: opening link tag, 2: closing link tag. */
            . sprintf( esc_html__( 'If you have questions or need help, please %1$scontact our support team%2$s.', 'ninja-forms' ), '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>' ),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_Recurly',
        'minVersion' => '3.0.5',
        /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
        'title' => sprintf( esc_html__( 'Attention %s users', 'ninja-forms' ), 'Recurly' ),
        'message' =>
            /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
            sprintf( esc_html__( 'Please update your %s add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms.', 'ninja-forms' ), 'Recurly' )
            . '<br />'
            /* translators: 1: opening link tag, 2: closing link tag. */
            . sprintf( esc_html__( 'If you have questions or need help, please %1$scontact our support team%2$s.', 'ninja-forms' ), '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>' ),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_Stripe_Checkout',
        'minVersion' => '3.2.6',
        /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
        'title' => sprintf( esc_html__( 'Attention %s users', 'ninja-forms' ), 'Stripe' ),
        'message' =>
            /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
            sprintf( esc_html__( 'Please update your %s add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms.', 'ninja-forms' ), 'Stripe' )
            . '<br />'
            /* translators: 1: opening link tag, 2: closing link tag. */
            . sprintf( esc_html__( 'If you have questions or need help, please %1$scontact our support team%2$s.', 'ninja-forms' ), '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>' ),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_UserAnalytics',
        'minVersion' => '3.0.3',
        /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
        'title' => sprintf( esc_html__( 'Attention %s users', 'ninja-forms' ), 'User Analytics' ),
        'message' =>
            /* translators: %s: name of a Ninja Forms add-on, for example Stripe. */
            sprintf( esc_html__( 'Please update your %s add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms.', 'ninja-forms' ), 'User Analytics' )
            . '<br />'
            /* translators: 1: opening link tag, 2: closing link tag. */
            . sprintf( esc_html__( 'If you have questions or need help, please %1$scontact our support team%2$s.', 'ninja-forms' ), '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>' ),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ]
];
