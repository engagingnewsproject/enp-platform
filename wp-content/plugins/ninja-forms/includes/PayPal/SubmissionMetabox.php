<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The PayPal payment, on the React submissions page.
 *
 * Registered through `nf_react_table_extra_value_keys`, the same seam the
 * PayPal Checkout add-on and core's own Calculations box use, so a payment
 * taken through core looks the same as one taken through the add-on.
 *
 * Returns null when the submission was never paid for, which leaves the panel
 * off that entry entirely rather than showing an empty heading.
 */
final class NF_PayPal_SubmissionMetabox
{
    /**
     * The stored status, in the reader's language.
     *
     * Statuses are stored as machine values and translated here. Storing the
     * translated word instead makes the database contents depend on whoever
     * happened to take the payment, and makes every later comparison a
     * comparison between languages.
     *
     * Anything unrecognised is shown as-is rather than blanked: an entry
     * written by an older version still has to render.
     *
     * @param string $status Stored machine status, e.g. 'completed'.
     * @return string Translated label, or the status itself when unrecognised.
     */
    public static function status_label( string $status ): string
    {
        $labels = array(
            'completed' => __( 'Completed', 'ninja-forms' ),
            // Submitted, never paid: still on PayPal, or gone without paying.
            // True at any moment until a capture, which is why it is not
            // worded as waiting.
            'awaiting_payment' => __( 'Not paid', 'ninja-forms' ),
            'pending'   => __( 'Pending', 'ninja-forms' ),
            'failed'    => __( 'Not paid — PayPal did not take the payment', 'ninja-forms' ),
            // Deliberately not "Failed": the money may have moved. Whoever
            // reads this needs to check PayPal rather than assume either way.
            'mismatched' => __( 'Needs checking — amount not confirmed', 'ninja-forms' ),
            'cancelled' => __( 'Cancelled', 'ninja-forms' ),
            // Learned from the service after the merchant refunds in PayPal's
            // own dashboard - see NF_PayPal_PaymentSync.
            'refunded'           => __( 'Refunded', 'ninja-forms' ),
            'partially_refunded' => __( 'Partially refunded', 'ninja-forms' ),
        );

        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }

    /**
     * Build the panel for a submission's stored PayPal status.
     *
     * Called through nf_react_table_extra_value_keys for every submission the
     * React page shows; refreshes the status from PayPal first.
     *
     * @param mixed $extraValue                      value of the registered key
     * @param NF_Database_Models_Submission $nfSub
     * @return \NinjaForms\Includes\Entities\MetaboxOutputEntity|null
     */
    public function handle( $extraValue, $nfSub ): ?\NinjaForms\Includes\Entities\MetaboxOutputEntity
    {
        if ( ! $extraValue ) return null;

        // An entry the paid PayPal add-on wrote carries paypal_status too, and
        // has its own panel. Core's entries carry the environment from their
        // first write (NF_PayPal_Gateway::process()); nothing else does.
        if ( ! $nfSub->get_extra_value( 'paypal_environment' ) ) return null;

        // What PayPal has done to it since - a refund, a pending capture
        // completing - before it is shown as the truth.
        $status = NF_PayPal_PaymentSync::refresh( $nfSub );

        return \NinjaForms\Includes\Entities\MetaboxOutputEntity::fromArray( array(
            'title'                => __( 'PayPal Payment', 'ninja-forms' ),
            'labelValueCollection' => self::rows( $status, $nfSub ),
        ) );
    }

    /**
     * The payment's rows, shared by this panel and the classic submission
     * metabox so both show the same thing.
     *
     * Rows with nothing to show are left out. The Transaction ID row carries a
     * 'url' through to the payment in the merchant's PayPal account.
     *
     * @param string                        $status Stored machine status.
     * @param NF_Database_Models_Submission $nfSub
     * @return array[] Each row: label, value, styling, and optionally url.
     */
    public static function rows( string $status, $nfSub ): array
    {
        $rows = array();

        $rows[] = array(
            'label'   => __( 'Status', 'ninja-forms' ),
            'value'   => self::status_label( $status ),
            // Anything that is not a completed payment is worth the eye going
            // to it first. Compared against the stored machine value, never a
            // translated one — an admin reading the site in a different
            // language than it was paid in would otherwise see every
            // successful payment flagged as a problem.
            'styling' => 'completed' === $status ? '' : 'alert',
        );

        $total    = $nfSub->get_extra_value( 'paypal_total' );
        $currency = $nfSub->get_extra_value( 'paypal_currency' );
        if ( $total ) {
            $rows[] = array(
                'label'   => __( 'Total', 'ninja-forms' ),
                'value'   => trim( $total . ' ' . $currency ),
                'styling' => '',
            );
        }

        $refunded = $nfSub->get_extra_value( 'paypal_refunded' );
        if ( $refunded ) {
            $rows[] = array(
                'label'   => __( 'Refunded', 'ninja-forms' ),
                'value'   => trim( $refunded . ' ' . $currency ),
                'styling' => '',
            );
        }

        $transaction_id = $nfSub->get_extra_value( 'paypal_transaction_id' );
        if ( $transaction_id ) {
            $rows[] = array(
                'label'   => __( 'Transaction ID', 'ninja-forms' ),
                'value'   => $transaction_id,
                'styling' => '',
                'url'     => self::paypal_link( $transaction_id, $nfSub ),
            );
        }

        $order_id = $nfSub->get_extra_value( 'paypal_order_id' );
        if ( $order_id ) {
            $rows[] = array(
                'label'   => __( 'Order ID', 'ninja-forms' ),
                'value'   => $order_id,
                'styling' => '',
            );
        }

        return $rows;
    }

    /**
     * Through to the transaction in the merchant's own PayPal account.
     *
     * The environment comes off the payment, not the site's current
     * connection: moving a site from sandbox to live must not start pointing
     * its old test payments at the live dashboard.
     *
     * @param string                        $transaction_id
     * @param NF_Database_Models_Submission $nfSub
     * @return string
     */
    private static function paypal_link( string $transaction_id, $nfSub ): string
    {
        $environment = $nfSub->get_extra_value( 'paypal_environment' );
        if ( ! $environment ) {
            $state       = NF_PayPal_Connection::get();
            $environment = $state[ 'environment' ];
        }

        return NF_PayPal_Connection::transaction_url( $transaction_id, $environment );
    }
}
