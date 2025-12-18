<?php

namespace NinjaForms\Blocks\Authentication;

/**
 * Manages a stored secret and guarantees that one is always available.
 *
 * Includes automatic secret rotation for enhanced security.
 */
class SecretStore {

    /** @var string */
    const OPTION_KEY = 'ninja-forms-views-secret';

    /** @var string */
    const ROTATION_DATE_KEY = 'ninja-forms-views-secret-rotation-date';

    /** @var int Default rotation interval in seconds (90 days) */
    const DEFAULT_ROTATION_INTERVAL = 7776000; // 90 * 24 * 60 * 60

    /**
     * Gets the SECRET or creates the SECRET if it does not exist.
     *
     * If defined, defaults to NINJA_FORMS_VIEWS_SECRET constant.
     * If a secret does not exist, then it creates a secret and stores the value.
     * If the secret is wrongly typed, then it self-corrects by creating a new secret.
     *
     * @return string
     */
    public static function getOrCreate() {

        // If defined, default to the NINJA_FORMS_VIEWS_SECRET constant.
        if( defined( 'NINJA_FORMS_VIEWS_SECRET' ) && self::validate( NINJA_FORMS_VIEWS_SECRET ) ) {
            $secret = NINJA_FORMS_VIEWS_SECRET;
        } else {
            $secret = get_option( self::OPTION_KEY );
        }

        // If the secret does not exist or is wrongly typed, then create a new secret and store the value.
        if( ! self::validate( $secret ) ) {
            $secret = KeyFactory::make( 64 ); // Use longer secret (128 chars hex)
            update_option( self::OPTION_KEY, $secret, $autoload = true );
            update_option( self::ROTATION_DATE_KEY, time(), $autoload = true );
        }

        return $secret;
    }

    /**
     * Rotate the secret (invalidates all existing tokens).
     *
     * Call this when:
     * - Security is compromised
     * - During scheduled rotation
     * - Manually via admin action
     *
     * @return string New secret
     */
    public static function rotate() {
        $newSecret = KeyFactory::make( 64 ); // 128 character hex string
        update_option( self::OPTION_KEY, $newSecret, true );
        update_option( self::ROTATION_DATE_KEY, time(), true );

        // Log rotation event
        error_log( sprintf(
            'Ninja Forms Views: Secret rotated at %s',
            gmdate( 'Y-m-d H:i:s' )
        ) );

        // Fire action hook for extensibility (only if WordPress is loaded)
        if ( function_exists( 'do_action' ) ) {
            do_action( 'ninja_forms_views_secret_rotated', $newSecret );
        }

        return $newSecret;
    }

    /**
     * Check if secret should be rotated based on age.
     *
     * @param int $maxAge Maximum age in seconds (default 90 days)
     *
     * @return bool True if rotation is needed
     */
    public static function shouldRotate( $maxAge = null ) {
        if ( $maxAge === null ) {
            $maxAge = self::DEFAULT_ROTATION_INTERVAL;
        }

        // Allow filtering the rotation interval (only if WordPress is loaded)
        if ( function_exists( 'apply_filters' ) ) {
            $maxAge = apply_filters( 'ninja_forms_views_rotation_interval', $maxAge );
        }

        $lastRotation = get_option( self::ROTATION_DATE_KEY );

        if ( ! $lastRotation ) {
            // Never rotated, check if secret is old (legacy installation)
            return false; // Don't force rotation on existing installations
        }

        return ( time() - $lastRotation ) > $maxAge;
    }

    /**
     * Get the last rotation date.
     *
     * @return int|false Unix timestamp of last rotation, or false if never rotated
     */
    public static function getLastRotationDate() {
        return get_option( self::ROTATION_DATE_KEY );
    }

    /**
     * Get days until next rotation.
     *
     * @return int Days remaining, or -1 if overdue
     */
    public static function getDaysUntilRotation() {
        $lastRotation = self::getLastRotationDate();

        if ( ! $lastRotation ) {
            return 999; // Never rotated, return large number
        }

        $rotationInterval = self::DEFAULT_ROTATION_INTERVAL;

        // Allow filtering the rotation interval (only if WordPress is loaded)
        if ( function_exists( 'apply_filters' ) ) {
            $rotationInterval = apply_filters(
                'ninja_forms_views_rotation_interval',
                $rotationInterval
            );
        }

        $nextRotation = $lastRotation + $rotationInterval;
        $secondsUntilRotation = $nextRotation - time();

        return (int) ceil( $secondsUntilRotation / DAY_IN_SECONDS );
    }

    /**
     * Validate secret format.
     *
     * @param mixed $secret
     *
     * @return bool
     */
    public static function validate( $secret ) {
        return $secret && is_string( $secret );
    }
}