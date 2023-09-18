<?php

/**
 * Array of compatibility checks and notices
 */
return [
    [
        'className' => 'NF_Authorize_Net',
        'minVersion' => '3.1.1',
        'title' => esc_html__('Attention Authorize.net users', 'ninja-forms'),
        'message' => sprintf(esc_html__('Please update your Authorize.net add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. %sIf you have questions or need help, please %scontact our support team%s.', 'ninja-forms'), '<br />', '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_Recurly',
        'minVersion' => '3.0.5',
        'title' => esc_html__('Attention Recurly users', 'ninja-forms'),
        'message' => sprintf(esc_html__('Please update your Recurly add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. %sIf you have questions or need help, please %scontact our support team%s.', 'ninja-forms'), '<br />', '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_Stripe_Checkout',
        'minVersion' => '3.2.6',
        'title' => esc_html__('Attention Stripe Users', 'ninja-forms'),
        'message' => sprintf(esc_html__('Please update your Stripe add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. %sIf you have questions or need help, please %scontact our support team%s.', 'ninja-forms'), '<br />', '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_UserAnalytics',
        'minVersion' => '3.0.3',
        'title' => esc_html__('Attention User Analytics Users', 'ninja-forms'),
        'message' => sprintf(esc_html__('Please update your User Analytics add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. %sIf you have questions or need help, please %scontact our support team%s.', 'ninja-forms'), '<br />', '<a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">', '</a>'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ]
];
