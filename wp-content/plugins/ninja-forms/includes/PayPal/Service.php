<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The site's client for the Saturday Drive PayPal service.
 *
 * Every call that needs the partner credential — creating an onboarding
 * referral, reading a merchant's account status, creating and capturing
 * fee-bearing orders — goes through paypal.ninjaforms.com. The site never calls
 * PayPal directly for these, because the partner secret cannot ship in a GPL
 * plugin.
 *
 * Requests are signed rather than bearing a token: the site credential itself
 * never travels. See the service's docs/plugin-server-auth.md.
 */
final class NF_PayPal_Service
{
    /** The sandbox service. Sandbox and live are separate hosts with separate credentials and data. */
    const HOST_SANDBOX = 'https://sandbox.paypal.ninjaforms.com';
    /** The live service. */
    const HOST_LIVE    = 'https://paypal.ninjaforms.com';

    /** begin_onboarding()'s answer when a connection was recorded while it waited. */
    const ALREADY_CONNECTED = 'nf_paypal_already_connected';

    /**
     * The service host for the connection's environment, without a trailing
     * slash.
     *
     * @return string
     */
    public static function host(): string
    {
        $state = NF_PayPal_Connection::get();
        $host  = 'live' === $state[ 'environment' ] ? self::HOST_LIVE : self::HOST_SANDBOX;

        /**
         * Point the site at a different service — a locally running one during
         * development, most usefully.
         *
         * @param string $host
         */
        return untrailingslashit( apply_filters( 'ninja_forms_paypal_service_host', $host ) );
    }

    /**
     * Begin onboarding. The only unsigned call: the site has no credential
     * until this returns one, and it is returned exactly once.
     *
     * @return array|WP_Error The service's answer. A WP_Error coded
     *                        ALREADY_CONNECTED means the merchant was
     *                        connected meanwhile, which is not a failure.
     *
     * @param string $return_url Where PayPal send the merchant when they finish.
     */
    public static function begin_onboarding( string $return_url )
    {
        $state = NF_PayPal_Connection::get();

        $response = self::post( '/v1/onboarding/begin', array(
            'siteUrl'     => home_url(),
            'returnUrl'   => $return_url,
            'environment' => $state[ 'environment' ],
        ), false );

        if ( is_wp_error( $response ) ) return $response;

        // The tracking id is required as well as the credential: an attempt
        // recorded without one is an attempt nobody can ever ask PayPal about,
        // so the connection could never complete, for a reason nothing
        // explains.
        if ( empty( $response[ 'siteId' ] ) || empty( $response[ 'credential' ] ) || empty( $response[ 'trackingId' ] ) ) {
            return new WP_Error( 'nf_paypal_service', __( 'The PayPal service did not issue a credential.', 'ninja-forms' ) );
        }

        // Another request may have recorded the merchant while this one
        // waited. That connection stands; this referral is not needed.
        if ( ! NF_PayPal_Connection::store_credential( $response[ 'siteId' ], $response[ 'credential' ], $response[ 'trackingId' ] ) ) {
            return new WP_Error( self::ALREADY_CONNECTED, __( 'This site is already connected to PayPal.', 'ninja-forms' ) );
        }

        return $response;
    }

    /**
     * Ask whether the merchant has granted permission on this site's referral.
     *
     * Sends nothing but the signature: the service knows which referral is
     * this site's, and PayPal say which merchant, if any, is linked to it.
     * Answers `linked` false while the merchant is still on PayPal.
     *
     * @return array|WP_Error The service's answer (linked, merchantId, and the
     *                        account status flags), or why it could not be had.
     */
    public static function check_onboarding()
    {
        return self::post( '/v1/onboarding/check', array() );
    }

    /**
     * Read the connected merchant's account status: the gating flags that
     * decide whether payments can be taken.
     *
     * @return array|WP_Error The service's answer, or why it could not be had.
     */
    public static function get_account_status()
    {
        return self::post( '/v1/onboarding/status', array() );
    }

    /**
     * Ask the service to create the order.
     *
     * No fee is sent, and none would be honoured: the service computes 1.9%
     * and injects it, so the site cannot calculate, transmit or tamper with
     * what Saturday Drive earns.
     *
     * @param array $order merchantId, amount, currency, method and the order
     *                     details the gateway fixed at submit.
     * @return array|WP_Error The service's answer (orderId on success), or why
     *                        it could not be had.
     */
    public static function create_order( array $order )
    {
        return self::post( '/v1/checkout/order', $order );
    }

    /**
     * Everything the page needs to draw PayPal's buttons, before any order
     * exists. The order itself is created when the visitor presses one of
     * them, because it has to name the method it will be paid with.
     *
     * @param array $checkout The merchant, currency and funding the form offers.
     * @return array|WP_Error The service's answer (SDK parameters and methods),
     *                        or why it could not be had.
     */
    public static function prepare_checkout( array $checkout )
    {
        return self::post( '/v1/checkout/prepare', $checkout );
    }

    /**
     * Capture an order the visitor approved. This is when the money moves.
     *
     * @param string $order_id PayPal's order id.
     * @return array|WP_Error The service's answer (capture status and ids), or
     *                        why it could not be had.
     */
    public static function capture_order( string $order_id )
    {
        return self::post( '/v1/checkout/capture', array( 'orderId' => $order_id ) );
    }

    /**
     * What has become of an order since this site last heard - a refund made
     * in PayPal's dashboard reaches the service by webhook and the site only
     * by asking.
     *
     * @param string $order_id PayPal's order id.
     * @return array|WP_Error The service's answer (status, amounts), or why it
     *                        could not be had.
     */
    public static function order_status( string $order_id )
    {
        return self::post( '/v1/checkout/status', array( 'orderId' => $order_id ) );
    }

    /**
     * Tell the service this site is disconnecting, so it revokes the credential.
     *
     * @return array|WP_Error The service's answer, or why it could not be had.
     */
    public static function disconnect()
    {
        return self::post( '/v1/disconnect', array() );
    }

    /**
     * Sign and send.
     *
     * The signature covers who we are, when, a one-time value and a digest of
     * the exact bytes sent — so a captured request cannot be replayed or
     * altered, and the credential never leaves this site.
     *
     * @param string $path   Service path, beginning with a slash.
     * @param array  $body   Sent as JSON.
     * @param bool   $signed False only for begin_onboarding(), before a credential exists.
     * @return array|WP_Error The decoded answer, or a WP_Error when the service
     *                        could not be reached or refused.
     */
    private static function post( string $path, array $body, bool $signed = true )
    {
        $json    = wp_json_encode( $body );
        $headers = array( 'Content-Type' => 'application/json' );

        if ( $signed ) {
            $state = NF_PayPal_Connection::get();

            if ( '' === $state[ 'site_id' ] || '' === $state[ 'credential' ] ) {
                return new WP_Error( 'nf_paypal_service', __( 'This site is not connected to the PayPal service.', 'ninja-forms' ) );
            }

            $timestamp = (string) time();
            $nonce     = wp_generate_password( 24, false, false );
            $payload   = implode( "\n", array( $state[ 'site_id' ], $timestamp, $nonce, hash( 'sha256', $json ) ) );

            $headers[ 'X-NF-Site' ]      = $state[ 'site_id' ];
            $headers[ 'X-NF-Timestamp' ] = $timestamp;
            $headers[ 'X-NF-Nonce' ]     = $nonce;
            $headers[ 'X-NF-Signature' ] = hash_hmac( 'sha256', $payload, $state[ 'credential' ] );
        }

        $response = wp_remote_post( self::host() . $path, array(
            'timeout' => 15,
            'headers' => $headers,
            'body'    => $json,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'nf_paypal_service', __( 'The PayPal service could not be reached.', 'ninja-forms' ) );
        }

        $code    = (int) wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || ! is_array( $decoded ) ) {
            // The service tells us nothing useful about a refusal on purpose;
            // the reason is recorded on its side for support to look up.
            return new WP_Error( 'nf_paypal_service', __( 'The PayPal service refused that request.', 'ninja-forms' ) );
        }

        return $decoded;
    }
}
