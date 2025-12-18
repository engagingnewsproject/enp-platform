<?php

namespace NinjaForms\Blocks\Authentication;

/**
 * Creates cryptographically secure random strings for use as public and private keys.
 */
class KeyFactory {

    /**
     * Generate a cryptographically secure random key using random_bytes().
     *
     * @param int $length Desired length of the output string (default 40)
     *
     * @return string Hex-encoded random string (actual length will be $length * 2)
     */
    public static function make( $length = 40 ) {
        if( 0 >= $length ) $length = 40; // Min key length.
        if( 255 <= $length ) $length = 255; // Max key length.

        try {
            // Use random_bytes() for cryptographically secure randomness
            // Returns binary data, so we convert to hex for safe string handling
            return bin2hex( random_bytes( $length ) );
        } catch ( Exception $e ) {
            // Fallback to wp_generate_password if random_bytes fails
            // (should never happen on PHP 7.0+, but defensive programming)
            error_log( 'Ninja Forms Views: random_bytes() failed, using fallback: ' . $e->getMessage() );
            return wp_generate_password( $length * 2, true, true );
        }
    }
}