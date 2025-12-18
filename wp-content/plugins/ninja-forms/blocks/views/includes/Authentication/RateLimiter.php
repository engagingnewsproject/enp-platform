<?php

namespace NinjaForms\Blocks\Authentication;

/**
 * Rate limiter for Views REST API endpoints.
 *
 * Prevents DoS attacks by limiting requests per IP address.
 */
class RateLimiter {

    /** @var int Default rate limit (requests per window) */
    const DEFAULT_LIMIT = 60;

    /** @var int Default time window in seconds */
    const DEFAULT_WINDOW = 60; // 1 minute

    /** @var string Transient key prefix */
    const TRANSIENT_PREFIX = 'nf_views_rate_limit_';

    /**
     * Check if the current request should be rate limited.
     *
     * @param string $endpoint Endpoint identifier (e.g., 'submissions', 'forms')
     * @param int    $limit    Maximum requests per window (default 60)
     * @param int    $window   Time window in seconds (default 60)
     *
     * @return bool|\WP_Error True if allowed, WP_Error if rate limited
     */
    public static function check( $endpoint, $limit = null, $window = null ) {
        // Allow disabling rate limiting via constant
        if ( defined( 'NF_VIEWS_DISABLE_RATE_LIMITING' ) && NF_VIEWS_DISABLE_RATE_LIMITING ) {
            return true;
        }

        // Allow disabling for logged-in admins
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( $limit === null ) {
            $limit = self::DEFAULT_LIMIT;
        }

        if ( $window === null ) {
            $window = self::DEFAULT_WINDOW;
        }

        // Allow filtering limits per endpoint
        $limit = apply_filters( 'ninja_forms_views_rate_limit', $limit, $endpoint );
        $window = apply_filters( 'ninja_forms_views_rate_window', $window, $endpoint );

        $ip = self::getClientIp();
        $key = self::getTransientKey( $ip, $endpoint );

        $count = get_transient( $key );

        if ( $count === false ) {
            // First request in this window
            set_transient( $key, 1, $window );
            return true;
        }

        if ( $count >= $limit ) {
            // Rate limit exceeded
            return new \WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __( 'Rate limit exceeded. Maximum %d requests per %d seconds allowed.', 'ninja-forms' ),
                    $limit,
                    $window
                ),
                array( 'status' => 429 )
            );
        }

        // Increment counter
        set_transient( $key, $count + 1, $window );

        return true;
    }

    /**
     * Get the client IP address.
     *
     * @return string IP address
     */
    protected static function getClientIp() {
        // Check for proxy headers
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy header
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direct connection
        );

        foreach ( $ip_keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) && ! empty( $_SERVER[ $key ] ) ) {
                // Handle comma-separated IPs (X-Forwarded-For)
                $ips = explode( ',', $_SERVER[ $key ] );
                $ip = trim( $ips[0] );

                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Generate transient key for IP + endpoint.
     *
     * @param string $ip       IP address
     * @param string $endpoint Endpoint identifier
     *
     * @return string Transient key
     */
    protected static function getTransientKey( $ip, $endpoint ) {
        return self::TRANSIENT_PREFIX . md5( $ip . '_' . $endpoint );
    }

    /**
     * Clear rate limit for a specific IP and endpoint.
     * Useful for testing or manual intervention.
     *
     * @param string $ip       IP address
     * @param string $endpoint Endpoint identifier
     *
     * @return bool True on success
     */
    public static function clear( $ip, $endpoint ) {
        $key = self::getTransientKey( $ip, $endpoint );
        return delete_transient( $key );
    }

    /**
     * Get current request count for an IP + endpoint.
     *
     * @param string $ip       IP address (default: current client)
     * @param string $endpoint Endpoint identifier
     *
     * @return int Current request count
     */
    public static function getCount( $ip = null, $endpoint = 'default' ) {
        if ( $ip === null ) {
            $ip = self::getClientIp();
        }

        $key = self::getTransientKey( $ip, $endpoint );
        $count = get_transient( $key );

        return $count !== false ? (int) $count : 0;
    }

    /**
     * Get remaining requests for an IP + endpoint.
     *
     * @param string $endpoint Endpoint identifier
     * @param int    $limit    Maximum requests per window
     *
     * @return int Remaining requests
     */
    public static function getRemaining( $endpoint = 'default', $limit = null ) {
        if ( $limit === null ) {
            $limit = self::DEFAULT_LIMIT;
        }

        $count = self::getCount( null, $endpoint );
        return max( 0, $limit - $count );
    }
}
