<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bring an entry's payment up to date with what PayPal has since done to it.
 *
 * A merchant refunds in PayPal's own dashboard, as the paid add-on's merchants
 * do. PayPal tell the Saturday Drive service by webhook, and the service
 * records it against the order - but nothing pushes that to the merchant's
 * site, which holds no PayPal credential and answers no webhooks. So the site
 * asks, at the moment someone looks at the entry, and the entry stops saying
 * Completed once the money has gone back.
 *
 * Only payments that can still change are asked about: a completed payment
 * can be refunded, a partially refunded one refunded further, and a pending
 * capture can complete. A failed, cancelled or fully refunded payment is
 * final and costs no call.
 */
final class NF_PayPal_PaymentSync
{
    /** Statuses the service can still move. */
    const OPEN = array( 'completed', 'pending', 'partially_refunded' );

    /**
     * How long an answer is trusted before the entry is asked about again.
     * The submissions list renders many entries at once; without this every
     * page load would be one service call per paid entry.
     */
    const FRESH_FOR = 300;

    /**
     * Ask the service about an entry whose payment can still change, and write
     * any change onto the entry before it is shown.
     *
     * At most one call per entry per FRESH_FOR seconds; a failed call is not
     * retried for an hour, and never changes the entry.
     *
     * @param NF_Database_Models_Submission $sub
     * @return string the entry's payment status after the check
     */
    public static function refresh( $sub ): string
    {
        $status   = (string) $sub->get_extra_value( 'paypal_status' );
        $order_id = (string) $sub->get_extra_value( 'paypal_order_id' );

        if ( '' === $order_id || ! in_array( $status, self::OPEN, true ) ) {
            return $status;
        }

        $key = 'nf_paypal_sync_' . $sub->get_id();
        if ( get_transient( $key ) ) {
            return $status;
        }
        set_transient( $key, 1, self::FRESH_FOR );

        $answer = NF_PayPal_Service::order_status( $order_id );

        // Unreachable or refused: the entry keeps saying what it said. Never
        // invent a change the service did not report. Asked again after an
        // hour rather than five minutes - a refusal (an order from before the
        // site reconnected, which the service rightly no longer owns to us)
        // is permanent, and an outage is not helped by asking every screen.
        if ( is_wp_error( $answer ) || empty( $answer[ 'status' ] ) ) {
            set_transient( $key, 1, 12 * self::FRESH_FOR );
            return $status;
        }

        $values = self::changes( $status, $answer );
        if ( ! $values ) {
            return $status;
        }

        foreach ( $values as $name => $value ) {
            $sub->update_extra_value( $name, $value );
        }
        $sub->save();

        return $values[ 'paypal_status' ];
    }

    /**
     * What to write, given what the entry says and what the service now says.
     *
     * @param string $status the entry's stored status
     * @param array  $answer the service's order status
     * @return array<string, string> extra values to write, empty when nothing changed
     */
    public static function changes( string $status, array $answer ): array
    {
        $remote   = (string) $answer[ 'status' ];
        $refunded = isset( $answer[ 'refunded' ] ) ? (string) $answer[ 'refunded' ] : '';
        $amount   = isset( $answer[ 'amount' ] ) ? (string) $answer[ 'amount' ] : '';

        if ( 'refunded' === $remote && 'refunded' !== $status ) {
            return array(
                'paypal_status'   => 'refunded',
                'paypal_refunded' => '' !== $refunded ? $refunded : $amount,
            );
        }

        if ( 'partially_refunded' === $remote ) {
            // Written even when already partially refunded: a further partial
            // refund changes the amount, and that is what the merchant reads.
            return array(
                'paypal_status'   => 'partially_refunded',
                'paypal_refunded' => $refunded,
            );
        }

        // A capture that was under review has since gone through.
        if ( 'captured' === $remote && 'pending' === $status ) {
            $values = array( 'paypal_status' => 'completed' );
            if ( ! empty( $answer[ 'transactionId' ] ) ) {
                $values[ 'paypal_transaction_id' ] = (string) $answer[ 'transactionId' ];
            }
            return $values;
        }

        return array();
    }
}
