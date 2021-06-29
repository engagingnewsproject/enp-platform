<?php

namespace NinjaFormsAddonManager;

class Keygen
{
    /**
    * Ported from WP OAuth Server Pro
    *
    * @param int $length
    * @return string $random_string
    */
    public static function generate_key( $length = 40 )
    {
        if( 0 >= $length ) $length = 40; // Min key length.
        if( 255 <= $length ) $length = 255; // Max key length.
        $characters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';
        for ( $i = 0; $i < $length; $i ++ ) {
            $random_string .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
        }
        return $random_string;
    }
}
