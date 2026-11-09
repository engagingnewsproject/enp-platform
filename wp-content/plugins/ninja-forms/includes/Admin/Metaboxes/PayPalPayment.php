<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The PayPal payment, shown on the submission it belongs to.
 *
 * The payment details are already written onto the entry; without this they
 * sit in the database where only a developer would find them. A merchant
 * looking at an order wants the amount, whether it actually completed, and a
 * way through to the transaction in their own PayPal account.
 *
 * Absent on submissions that were never paid for, rather than an empty panel
 * on every entry of every form.
 */
final class NF_Admin_Metaboxes_PayPalPayment extends NF_Abstracts_SubmissionMetabox
{
    /**
     * Register the box only on the edit screen of a submission PayPal paid for.
     *
     * @return void
     */
    public function __construct()
    {
        // Before parent::__construct(), and that order is the whole point.
        // NF_Abstracts_Metabox registers add_meta_boxes unconditionally, while
        // NF_Abstracts_SubmissionMetabox only loads $this->sub when a post is
        // being edited. Constructing the parent first on a screen with no post
        // — add-new, most obviously — registers a metabox whose render has no
        // submission to read, and the screen dies with a critical error.
        // NF_Admin_Metaboxes_Calculations guards in this order for the same
        // reason.
        if ( ! isset( $_GET[ 'post' ] ) ) return;

        parent::__construct();

        add_action( 'init', array( $this, 'init' ), 10 );

        // Core's entries carry the environment from their first write; an
        // entry the paid PayPal add-on wrote has paypal_status but not that.
        if ( $this->sub && ( ! $this->sub->get_extra_value( 'paypal_status' )
            || ! $this->sub->get_extra_value( 'paypal_environment' ) ) ) {
            remove_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        }
    }

    /**
     * Set the translated title once translations are loaded.
     *
     * @return void
     */
    public function init(): void
    {
        $this->_title = esc_html__( 'PayPal Payment', 'ninja-forms' );
    }

    /**
     * Print the payment rows and a link through to the PayPal transaction.
     *
     * Refreshes the status from PayPal first, so a refund made in the PayPal
     * dashboard shows here without waiting for a webhook.
     *
     * @param WP_Post $post    The submission post being edited.
     * @param array   $metabox The metabox arguments WordPress passes.
     * @return void
     */
    public function render_metabox( $post, $metabox )
    {
        // Belt as well as braces. The constructor guard stops this being
        // registered without a submission; this stops it *rendering* without
        // one, so the box cannot fatal an admin screen if some other path
        // registers it.
        if ( ! $this->sub ) return;

        // What PayPal has done to it since - a refund made in their
        // dashboard, a pending capture completing - before it is shown.
        $status = NF_PayPal_PaymentSync::refresh( $this->sub );
        $rows   = NF_PayPal_SubmissionMetabox::rows( $status, $this->sub );

        $link = '';
        echo '<table class="widefat striped"><tbody>';
        foreach ( $rows as $row ) {
            if ( '' === $row[ 'value' ] || null === $row[ 'value' ] ) continue;
            echo '<tr><th scope="row" style="width:9em">' . esc_html( $row[ 'label' ] ) . '</th>';
            echo '<td>' . esc_html( $row[ 'value' ] ) . '</td></tr>';
            if ( ! empty( $row[ 'url' ] ) ) $link = $row[ 'url' ];
        }
        echo '</tbody></table>';

        if ( $link ) {
            echo '<p><a href="' . esc_url( $link ) . '" target="_blank" rel="noreferrer">'
                . esc_html__( 'View this payment in PayPal', 'ninja-forms' )
                . '</a></p>';
        }
    }
}
