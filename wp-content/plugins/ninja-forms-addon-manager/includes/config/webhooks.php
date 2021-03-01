<?php

return [
    'controllers' => [

        /*
        |--------------------------------------------------------------------------
        | Example
        |--------------------------------------------------------------------------
        |
        | Example webhook controller.
        |
        */

        'example' => 'NinjaFormsAddonManager\Webhooks\Example',

        /*
        |--------------------------------------------------------------------------
        | Install
        |--------------------------------------------------------------------------
        |
        | Installs a plugin.
        |
        */

        'install' => 'NinjaFormsAddonManager\Webhooks\Install',

        /*
        |--------------------------------------------------------------------------
        | Sync
        |--------------------------------------------------------------------------
        |
        | Returns the active plugins.
        |
        */

        'sync'    => 'NinjaFormsAddonManager\Webhooks\Sync',
    ]
];
