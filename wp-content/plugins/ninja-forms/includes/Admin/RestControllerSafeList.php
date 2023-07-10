<?php

namespace NinjaForms\Includes\Admin;

/**
 * Configuration of what can be called and extended by NF_AJAX_REST_Controller
 */
class RestControllerSafeList
{

    const   ALLOWED_METHOD_CALLS = [
        'NF_AJAX_Controllers_Form' => [
            'delete'
        ],
        'NF_AJAX_REST_Forms' => [
            'delete'
        ]
    ];

    public static function isClassMethodAllowed(string $fqcn, string $method): bool
    {
        $return = false;

        if(isset(self::ALLOWED_METHOD_CALLS[$fqcn])
        && in_array($method,self::ALLOWED_METHOD_CALLS[$fqcn])
        ){
            $return = true;
        } 

        return $return;
    }
}
