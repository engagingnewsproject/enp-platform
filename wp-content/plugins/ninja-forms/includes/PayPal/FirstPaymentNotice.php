<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tell the merchant about the fee once their first payment actually lands.
 *
 * The fourth of the four disclosure surfaces. Deliberately once and
 * dismissible: a reminder at the moment money first moves is useful, and
 * repeating it after every payment would be nagging about a fee they already
 * agreed to.
 */
final class NF_PayPal_FirstPaymentNotice
{
    /** Option holding '' (no payment yet), 'pending' (show the notice) or 'dismissed'. */
    const SEEN_OPTION = 'nf_paypal_first_payment_notice';

    /**
     * Hook the notice, its dismiss script, and its dismiss endpoint.
     *
     * Printed on admin_notices directly rather than through NF_Admin_Notices:
     * this is the fee disclosure owed after the first payment, so it must show
     * even where the Ninja Forms "disable admin notices" setting is on.
     *
     * @return void
     */
    public function __construct()
    {
        add_action( 'admin_notices', array( $this, 'render' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action( 'wp_ajax_nf_paypal_dismiss_first_payment', array( $this, 'dismiss' ) );
    }

    /**
     * Called when a payment completes. Records that there has been one.
     *
     * Only the first call has an effect, so the notice is never re-armed after
     * the merchant dismisses it.
     *
     * @return void
     */
    public static function payment_taken(): void
    {
        if ( get_option( self::SEEN_OPTION, '' ) ) return;
        update_option( self::SEEN_OPTION, 'pending', false );
    }

    /**
     * Whether this admin should see the notice on this request.
     *
     * @return bool
     */
    private function should_show(): bool
    {
        return 'pending' === get_option( self::SEEN_OPTION, '' ) && current_user_can( 'manage_options' );
    }

    /**
     * Attach the dismiss handler to WordPress's own admin script.
     *
     * WordPress's dismiss button only hides the notice for this page load, so
     * the dismissal is recorded server-side or it returns on every screen.
     *
     * @return void
     */
    public function enqueue(): void
    {
        if ( ! $this->should_show() ) return;

        $data = array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'nf_paypal_dismiss_first_payment' ),
        );

        $script = <<<'JS'
( function ( data ) {
    document.addEventListener( 'click', function ( event ) {
        if ( ! event.target.classList.contains( 'notice-dismiss' ) ) return;
        if ( ! event.target.closest( '.nf-paypal-first-payment' ) ) return;
        var body = new FormData();
        body.append( 'action', 'nf_paypal_dismiss_first_payment' );
        body.append( 'security', data.nonce );
        fetch( data.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body,
        } );
    } );
} )( %s );
JS;

        wp_add_inline_script( 'common', sprintf( $script, wp_json_encode( $data ) ) );
    }

    /**
     * Print the notice.
     *
     * @return void
     */
    public function render(): void
    {
        if ( ! $this->should_show() ) return;

        // Shown once, after the merchant's first PayPal payment. Wording
        // approved by Quay, 2026-08-12. Two sentences kept as two separate
        // strings: a translator handling the congratulation and the fee
        // reminder together would be working on a wall of text, and the
        // reminder is the part that carries the legal weight.
        $congratulations = esc_html__( 'Your first PayPal payment just came through. Congratulations!', 'ninja-forms' );
        $reminder        = esc_html__( 'A quick reminder while it\'s fresh: as shown when you connected, each payment includes our 1.9% fee plus PayPal\'s own processing fee. That\'s the entire cost to you. No license, no monthly charge, nothing else to pay. We\'ll only show this note once. Here\'s to many more! 🍻', 'ninja-forms' );

        printf(
            '<div class="notice notice-info is-dismissible nf-paypal-first-payment"><p>%s</p><p>%s</p></div>',
            $congratulations,
            $reminder
        );
    }

    /**
     * Record the dismissal so the notice does not return.
     *
     * @return void
     */
    public function dismiss(): void
    {
        check_ajax_referer( 'nf_paypal_dismiss_first_payment', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        update_option( self::SEEN_OPTION, 'dismissed', false );
        wp_send_json_success();
    }
}
