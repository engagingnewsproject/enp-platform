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
     * Extract form IDs from a token without full validation.
     * Used for debugging/logging purposes only.
     *
     * @param string $token
     *
     * @return array|false Array of form IDs or false on failure
     */
    public function getFormIds( $token ) {
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