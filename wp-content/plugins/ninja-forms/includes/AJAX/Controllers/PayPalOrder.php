<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create the PayPal order for a halted submission, when the visitor presses
 * a payment button.
 *
 * PayPal require every order to name the method it will be paid with, and
 * that is not known until a button is pressed - so the gateway halts the
 * submission without an order and this makes it on the click. Decision,
 * 2026-09-09.
 *
 * The browser gets to say one thing: which button. Everything else the order
 * needs - amount, currency, the merchant, the buyer's email, the item name -
 * was fixed by the gateway when the form was submitted and lives in the
 * halted submission core keeps in its session. Nothing in the request is
 * trusted for any of it, and the session is what ties the order to this
 * visitor's submission: a request without the session cookie finds nothing to
 * pay for.
 */
final class NF_AJAX_Controllers_PayPalOrder
{
    /**
     * Register the order endpoint for visitors and logged-in users alike.
     *
     * @return void
     */
    public function __construct()
    {
        // Visitors are not logged in, so both hooks.
        add_action( 'wp_ajax_nf_paypal_order',        array( $this, 'create' ) );
        add_action( 'wp_ajax_nopriv_nf_paypal_order', array( $this, 'create' ) );
    }

    /**
     * AJAX handler for nf_paypal_order: create (or reuse) the PayPal order for
     * the button the visitor pressed.
     *
     * Responds with { orderId } on success; every refusal is a JSON error
     * carrying a visitor-facing message. Never returns normally in production,
     * because both responses end the request.
     *
     * @return void
     */
    public function create(): void
    {
        $this->check_nonce();

        if ( ! isset( $_COOKIE[ 'nf_wp_session' ] ) ) {
            $this->refuse( __( 'We could not find your form submission. Please submit the form again.', 'ninja-forms' ) );
        }

        $data  = Ninja_Forms()->session()->get( 'nf_processing_data' );
        $order = is_array( $data ) && isset( $data[ 'extra' ][ 'nf_paypal_order' ] ) ? $data[ 'extra' ][ 'nf_paypal_order' ] : null;

        if ( ! is_array( $order ) || empty( $order[ 'amount' ] ) || empty( $order[ 'merchantId' ] ) ) {
            $this->refuse( __( 'We could not find your form submission. Please submit the form again.', 'ninja-forms' ) );
        }

        // Re-checked on every click, not only at submit: the merchant may have
        // disconnected in between, and the money must never move then.
        if ( ! NF_PayPal_Connection::can_process_payments() ) {
            $this->refuse( __( 'Payment is temporarily unavailable. Please try again in a few minutes.', 'ninja-forms' ) );
        }

        $method = isset( $_POST[ 'method' ] ) ? sanitize_key( wp_unslash( $_POST[ 'method' ] ) ) : 'paypal';

        if ( ! in_array( $method, $this->methods_offered( $order ), true ) ) {
            $this->refuse( __( 'That payment option is not available on this form.', 'ninja-forms' ) );
        }

        // A second press of the same button - after a cancelled approval, say
        // - reuses the order rather than making another. A different button
        // needs a different order, because the order names its method.
        if ( ! empty( $order[ 'orderId' ] ) && $method === $order[ 'method' ] ) {
            wp_send_json_success( array( 'orderId' => $order[ 'orderId' ] ) );
        }

        // No fee is sent. The service computes it and would ignore one anyway.
        $created = NF_PayPal_Service::create_order( array(
            'merchantId' => $order[ 'merchantId' ],
            'amount'     => $order[ 'amount' ],
            'currency'   => $order[ 'currency' ],
            'formId'     => isset( $order[ 'formId' ] ) ? $order[ 'formId' ] : 0,
            'returnUrl'  => isset( $order[ 'returnUrl' ] ) ? $order[ 'returnUrl' ] : '',
            'cancelUrl'  => isset( $order[ 'cancelUrl' ] ) ? $order[ 'cancelUrl' ] : '',
            'funding'    => isset( $order[ 'funding' ] ) ? $order[ 'funding' ] : array(),
            'buyerEmail' => isset( $order[ 'buyerEmail' ] ) ? $order[ 'buyerEmail' ] : '',
            'itemName'   => isset( $order[ 'itemName' ] ) ? $order[ 'itemName' ] : '',
            'brandName'  => isset( $order[ 'brandName' ] ) ? $order[ 'brandName' ] : '',
            'method'     => $method,
        ) );

        if ( is_wp_error( $created ) || empty( $created[ 'orderId' ] ) ) {
            // Said on the entry too: PayPal refused, so it was not paid. A later
            // successful try overwrites this; nothing overwrites a completed
            // payment.
            NF_PayPal_Gateway::record_on_entry( $data, array( 'paypal_status' => 'failed' ) );
            $this->refuse( __( "PayPal couldn't complete your payment. You have not been charged. Please try again.", 'ninja-forms' ) );
        }

        // Written back where the resume leg will read it: core restores this
        // same session data when the visitor comes back, and the gateway's
        // complete() captures the order it finds there.
        $data[ 'extra' ][ 'nf_paypal_order' ][ 'orderId' ] = $created[ 'orderId' ];
        $data[ 'extra' ][ 'nf_paypal_order' ][ 'method' ]  = $method;
        $data[ 'extra' ][ 'nf_paypal_order' ][ 'status' ]  = 'created';

        Ninja_Forms()->session()->set( 'nf_processing_data', $data );

        wp_send_json_success( array( 'orderId' => $created[ 'orderId' ] ) );
    }

    /**
     * The buttons this form was offered: the PayPal wallet always (it is also
     * what the deterministic test button stands in for), plus whatever the
     * service listed. Anything else is a request the page never made.
     *
     * @param array $order The halted submission's nf_paypal_order extra.
     * @return string[]
     */
    private function methods_offered( array $order ): array
    {
        $offered = array( 'paypal' );

        if ( isset( $order[ 'sdk' ][ 'methods' ] ) && is_array( $order[ 'sdk' ][ 'methods' ] ) ) {
            foreach ( $order[ 'sdk' ][ 'methods' ] as $method ) {
                if ( isset( $method[ 'name' ] ) ) $offered[] = (string) $method[ 'name' ];
            }
        }

        return array_values( array_unique( $offered ) );
    }

    /**
     * The same nonce the form itself submits with, timestamped the same way.
     * See NF_AJAX_Controllers_Submission::submit().
     *
     * @return void
     */
    private function check_nonce(): void
    {
        $nonce_name = 'ninja_forms_display_nonce';

        if ( isset( $_REQUEST[ 'nonce_ts' ] ) && 0 < strlen( $_REQUEST[ 'nonce_ts' ] ) ) {
            $nonce_name .= '_' . sanitize_text_field( wp_unslash( $_REQUEST[ 'nonce_ts' ] ) );
        }

        if ( ! check_ajax_referer( $nonce_name, 'security', false ) ) {
            $this->refuse( __( 'This page has been open too long. Please reload it and try again.', 'ninja-forms' ) );
        }
    }

    /**
     * End the request with a JSON error the checkout script shows the visitor.
     *
     * @param string $message Translated, visitor-facing reason.
     * @return void
     */
    private function refuse( string $message ): void
    {
        wp_send_json_error( array( 'message' => $message ) );
    }
}
