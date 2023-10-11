<?php 

namespace NinjaForms\Includes\Handlers;

/**
 * Sanitize output for enhanced functionality while maintaining security
 */
class Sanitizer{

    const DISALLOWED_SCRIPT_TRIGGERS=[
        '/<\s*(script)/i', // < script (includes empty spaces after opening tag)
        '/(onload)/i', // word 'onload' 
        '/(onerror)/i', // word 'onerror'
        '/(onfocus)/i', // word 'onfocus'
        '/(javascript)/i' // word 'javascript'
    ];

    /**
     * Block disallowed script triggering text
     *
     * @param string $string
     * @return string
     */
   public static function preventScriptTriggerInHtmlOutput(string $string): string
    {
        $return = $string;

        $fail = false;

        foreach(self::DISALLOWED_SCRIPT_TRIGGERS as $disallowedString){
            $preg_match = preg_match($disallowedString,$string);

            if($preg_match){
                $fail = true;
            }
        }

        if($fail){
            $return = htmlspecialchars($return,\ENT_QUOTES);
        }

        return $return;
    }
}