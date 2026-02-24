<?php

namespace NinjaForms\Blocks\Authentication;

/**
 * Creates an encoded public/private key hash and validates it.
 *
 * Security improvements:
 * - Tokens are bound to specific form IDs
 * - Tokens include expiration timestamps (15 minutes)
 * - Validation checks both authenticity and authorization
 */
class Token {

    /** @var string */
    protected $privateKey;

    /** @var int Token expiration time in seconds */
    const TOKEN_EXPIRATION = 900; // 15 minutes

    /** @var int Maximum token length in bytes (security: prevent memory exhaustion DoS) */
    const MAX_TOKEN_LENGTH = 8192; // 8KB is generous for typical tokens

    /** @var int Maximum age past expiration for token refresh (security: limit refresh window) */
    const MAX_REFRESH_AGE = 86400; // 24 hours - tokens older than this cannot be refreshed

    /**
     * @param string $privateKey
     */
    public function __construct( $privateKey ) {
        $this->privateKey = $privateKey;
    }

    /**
     * Create a token bound to specific form IDs with expiration.
     *
     * @param string $publicKey
     * @param array $formIds Array of form IDs this token can access
     *
     * @return string
     */
    public function create( $publicKey, $formIds = array() ) {
        $expiration = time() + self::TOKEN_EXPIRATION;
        $payload = json_encode( array(
            'formIds' => array_map( 'intval', $formIds ),
            'exp' => $expiration
        ) );

        $hash = $this->hash( $publicKey, $payload );
        return base64_encode( $hash . ':' . $publicKey . ':' . $payload );
    }

    /**
     * Validate token authenticity and check if it grants access to a specific form.
     *
     * @param string $token
     * @param int|null $formId Form ID to check access for (null to only validate token structure)
     *
     * @return bool
     */
    public function validate( $token, $formId = null ) {
        // Security: Validate token size before decoding to prevent memory exhaustion DoS
        if ( ! is_string( $token ) || strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return false;
        }

        // If the token is malformed, then list() may return an undefined index error.
        // Pad the exploded array to add missing indexes.
        // Limit explode to 3 parts to handle colons in payload JSON
        list( $hash, $publicKey, $payload ) = array_pad( explode( ':', base64_decode( $token ), 3 ), 3, false );

        // Validate token structure and hash
        if ( ! hash_equals( $hash, $this->hash( $publicKey, $payload ) ) ) {
            return false;
        }

        // Decode and validate payload
        $data = json_decode( $payload, true );
        if ( ! is_array( $data ) || ! isset( $data['formIds'] ) || ! isset( $data['exp'] ) ) {
            return false;
        }

        // Check expiration
        if ( time() > $data['exp'] ) {
            return false;
        }

        // If a specific form ID is requested, check authorization
        if ( $formId !== null ) {
            if ( ! in_array( intval( $formId ), $data['formIds'], true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate token signature and structure without checking expiration.
     * Used for token refresh - allows refreshing expired but authentic tokens.
     *
     * @param string $token
     * @param int|null $formId Form ID to check access for (null to only validate signature)
     *
     * @return bool True if token signature is valid (regardless of expiration)
     */
    public function validateSignatureOnly( $token, $formId = null ) {
        // Security: Validate token size before decoding to prevent memory exhaustion DoS
        if ( ! is_string( $token ) || strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return false;
        }

        // If the token is malformed, then list() may return an undefined index error.
        list( $hash, $publicKey, $payload ) = array_pad( explode( ':', base64_decode( $token ), 3 ), 3, false );

        // Validate token structure and hash (signature check)
        if ( ! $hash || ! $publicKey || ! $payload ) {
            return false;
        }

        if ( ! hash_equals( $hash, $this->hash( $publicKey, $payload ) ) ) {
            return false;
        }

        // Decode and validate payload structure
        $data = json_decode( $payload, true );
        if ( ! is_array( $data ) || ! isset( $data['formIds'] ) || ! isset( $data['exp'] ) ) {
            return false;
        }

        // Security: Limit how old a token can be for refresh
        // This prevents indefinite refresh of tokens from logs/browser history
        if ( time() > $data['exp'] + self::MAX_REFRESH_AGE ) {
            return false;
        }

        // If a specific form ID is requested, check authorization
        if ( $formId !== null ) {
            if ( ! in_array( intval( $formId ), $data['formIds'], true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract form IDs from a token without full validation.
     * Used for debugging/logging purposes only.
     *
     * @param string $token
     *
     * @return array|false Array of form IDs or false on failure
     */
    public function getFormIds( $token ) {
        // Security: Validate token size before decoding to prevent memory exhaustion DoS
        if ( ! is_string( $token ) || strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
            return false;
        }

        // Limit explode to 3 parts to handle colons in payload JSON
        $parts = explode( ':', base64_decode( $token ), 3 );

        // Token format: hash:publicKey:payload
        // We only need the payload (3rd part)
        if ( count( $parts ) < 3 ) {
            return false;
        }

        $payload = $parts[2];
        $data = json_decode( $payload, true );
        return isset( $data['formIds'] ) ? $data['formIds'] : false;
    }

    /**
     * Generate HMAC hash for token validation using hash_hmac().
     *
     * Uses HMAC-SHA256 which is cryptographically stronger than simple
     * concatenation and prevents length extension attacks.
     *
     * @param string $publicKey
     * @param string $payload
     *
     * @return string
     */
    protected function hash( $publicKey, $payload = '' ) {
        return hash_hmac( 'sha256', $publicKey . $payload, $this->privateKey );
    }
}