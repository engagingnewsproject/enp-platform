<?php

/**
 * Array of compatibility checks and notices
 */
return [
    [
        'className' => 'NF_Authorize_Net',
        'minVersion' => '3.1.1',
        'title' => __('Attention Authorize.net users', 'ninja-forms'),
        'message' => __('Please update your Authorize.net add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. <br />If you have questions or need help, please <a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">contact our support team</a>.', 'ninja-forms'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ],
    [
        'className' => 'NF_Recurly',
        'minVersion' => '3.0.5',
        'title' => __('Attention Recurly users', 'ninja-forms'),
        'message' => __('Please update your Recurly add-on for Ninja Forms. The version you are using is not compatible with your current version of Ninja Forms. <br />If you have questions or need help, please <a style="background-color:transparent; padding:0; text-decoration:underline;"href="'. \admin_url('admin.php?page=nf-system-status').'">contact our support team</a>.', 'ninja-forms'),
        'int' => 0,
        'link'=>'<a href="https://ninjaforms.com/docs/installation/#add-ons">Update now</a>'
    ]
];
