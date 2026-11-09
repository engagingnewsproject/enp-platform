<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PayPal as a gateway on the existing Collect Payment action.
 *
 * This is a gateway registration, not a new action and not a builder change:
 * core already turns anything registered on ninja_forms_register_payment_gateways
 * into an option in Collect Payment's gateway dropdown.
 */
final class NF_PayPal_Gateway extends NF_Abstracts_PaymentGateway
{
    /**
     * Also the name of the form action. Collect Payment locks a re-named
     * instance's gateway dropdown to the gateway whose slug matches the
     * action name, so these two must stay identical.
     */
    const SLUG = 'paypal-connect';

    protected $_slug = self::SLUG;

    protected $_name = 'PayPal';

    /**
     * The gateway's display name.
     *
     * Translated on demand rather than in the constructor. Gateways are built
     * on plugins_loaded, and asking for a ninja-forms translation before init
     * makes WordPress load the textdomain too early and say so — once per
     * request, which is how this was found. Nothing reads the name before
     * NF_Actions_CollectPayment::buildPaymentGatewayOptions at init/15.
     *
     * Deferring with an init hook was the obvious alternative and is worse: a
     * gateway constructed after init would then have no name at all, silently.
     * Answering when asked cannot get the order wrong.
     *
     * @return string
     */
    public function get_name()
    {
        return esc_html__( 'PayPal', 'ninja-forms' );
    }

    /**
     * The settings a merchant sees on the payment action.
     *
     * Built on demand, for the reason given on get_name() — every label here
     * is translated. Memoised because CollectPayment merges these into the
     * action's own settings and may ask more than once.
     *
     * @return array Builder setting definitions.
     */
    public function get_settings()
    {
        if ( ! empty( $this->_settings ) ) {
            return $this->_settings;
        }

        // One of the four places the fee is disclosed: here, where a merchant
        // sets up the thing that charges. Wording approved by Quay, 2026-08-12.
        //
        // A gateway's settings are merged into every Collect Payment action,
        // so each one here is shown only while PayPal is the selected gateway
        // ('deps'); otherwise they would appear on Stripe's, Authorize.net's
        // and the generic action too.
        $this->_settings = array(
            'funding_types' => array(
                'name'     => 'funding_types',
                'type'     => 'fieldset',
                'group'    => 'primary',
                'width'    => 'full',
                'label'    => esc_html__( 'Enable Payment Options', 'ninja-forms' ),
                'settings' => self::funding_types(),
                'deps'     => array( 'payment_gateways' => 'paypal-connect' ),
            ),
            // Optional, because a form cannot be relied on to have an email
            // field at all — a donation form often has none. When the merchant
            // maps one, PayPal's login is prefilled and the buyer types less.
            // When they do not, or the visitor mistypes it, PayPal ask for it
            // themselves and the payment proceeds regardless. It is never a
            // condition of paying.
            'buyer_email' => array(
                'name'           => 'buyer_email',
                'deps'           => array( 'payment_gateways' => 'paypal-connect' ),
                'type'           => 'textbox',
                'group'          => 'primary',
                'label'          => esc_html__( 'Buyer Email', 'ninja-forms' ),
                'placeholder'    => esc_attr__( 'Optional — search for an email field', 'ninja-forms' ),
                'value'          => '',
                'width'          => 'one-half',
                'use_merge_tags' => true,
            ),
            // What PayPal's checkout shows the buyer as the thing being paid
            // for. PayPal require a line item on every order; a form builder
            // cannot know whether a payment is a donation, a ticket or a fee,
            // so the words are the merchant's - this box, or the form's title
            // when it is empty. Decision, 2026-09-09.
            'item_name' => array(
                'name'           => 'item_name',
                'deps'           => array( 'payment_gateways' => 'paypal-connect' ),
                'type'           => 'textbox',
                'group'          => 'primary',
                'label'          => esc_html__( 'Item Name', 'ninja-forms' ),
                'placeholder'    => esc_attr__( 'Optional — defaults to the form title', 'ninja-forms' ),
                'value'          => '',
                'width'          => 'one-half',
                'use_merge_tags' => true,
            ),
            'paypal_fee_disclosure' => array(
                'type'      => 'html',
                'group'     => 'primary',
                'label'     => esc_html__( 'About this payment', 'ninja-forms' ),
                'value'     => esc_html__( 'Payments collected through this action use your connected PayPal account. Each transaction includes the 1.9% per-transaction fee + PayPal fee; there\'s nothing else to pay, no license and no monthly cost.', 'ninja-forms' ),
                'width'     => 'full',
                'className' => 'yellow-background-description top',
                'deps'      => array( 'payment_gateways' => 'paypal-connect' ),
            ),
        );

        return $this->_settings;
    }

    /**
     * PayPal funding sources this release never offers.
     *
     * Regional bank and redirect methods: none were in PayPal's certification
     * of this integration, each depends on merchant and buyer region, and some
     * have been retired by their operators. They are not toggles, and they are
     * always sent to PayPal as switched off so its buttons never show them.
     */
    private const NOT_OFFERED = array(
        'bancontact', 'blik', 'eps', 'giropay', 'ideal',
        'mercadopago', 'mybank', 'p24', 'sepa', 'sofort',
    );

    /**
     * The payment options a merchant can turn on for one form.
     *
     * The same list, in the same words, as the Payments page: PayPal itself
     * (always offered, so not a toggle), Credit & Debit Cards, Pay Later and
     * Venmo. Pay Later covers PayPal Credit too, which PayPal treats as a
     * funding source inside its wallet rather than a method of its own
     * (Questions and Responses v2.0, answer 4). Apple Pay and Google Pay are
     * not offered in this release, so they are not toggles that draw nothing.
     * Cards and Pay Later start on; Venmo is US-only, so it starts off.
     *
     * A toggle is permission, not a promise. What actually renders is the
     * intersection of this and what PayPal says the merchant is approved for -
     * they asked us explicitly not to display methods unconditionally
     * (Questions and Responses v2.0, answer 5).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function funding_types(): array
    {
        $types = array(
            'card'     => array( esc_html__( 'Credit & Debit Cards', 'ninja-forms' ), 1 ),
            'paylater' => array( esc_html__( 'Pay Later', 'ninja-forms' ), 1 ),
            'venmo'    => array( esc_html__( 'Venmo', 'ninja-forms' ), 0 ),
        );

        $settings = array();

        foreach ( $types as $name => $type ) {
            $settings[] = array(
                'name'  => $name,
                'type'  => 'toggle',
                'label' => $type[ 0 ],
                'width' => 'one-third',
                'value' => $type[ 1 ],
            );
        }

        return $settings;
    }

    /**
     * Split the merchant's toggles into what PayPal should offer and what it
     * should keep out of the way.
     *
     * Both halves are sent. Naming only the wanted ones would leave PayPal
     * free to show the rest, and a merchant who turned something off means it.
     *
     * @param array $action_settings The payment action's settings.
     * @return array{enable: string[], disable: string[]}
     */
    private function funding( array $action_settings ): array
    {
        $enable = array();
        $disable = array();

        foreach ( self::funding_types() as $type ) {
            $value = isset( $action_settings[ $type[ 'name' ] ] )
                ? $action_settings[ $type[ 'name' ] ]
                : $type[ 'value' ];

            // PayPal Credit rides on the Pay Later toggle.
            $names = 'paylater' === $type[ 'name' ]
                ? array( 'credit', 'paylater' )
                : array( $type[ 'name' ] );

            if ( (int) $value === 1 ) {
                $enable = array_merge( $enable, $names );
            } else {
                $disable = array_merge( $disable, $names );
            }
        }

        $disable = array_merge( $disable, self::NOT_OFFERED );

        return array( 'enable' => $enable, 'disable' => $disable );
    }

    /**
     * The buyer's email as the form supplied it, or '' when it did not.
     *
     * By the time this runs core has already replaced the setting's merge tag
     * with the field's value. What is left is whatever the visitor typed, so
     * it is checked as an email and otherwise dropped: a typo here must never
     * stop a payment, because PayPal will ask for the address themselves.
     *
     * @param array $action_settings The payment action's settings, merge tags resolved.
     * @return string
     */
    private function buyer_email( array $action_settings ): string
    {
        $email = isset( $action_settings[ 'buyer_email' ] ) ? trim( (string) $action_settings[ 'buyer_email' ] ) : '';

        // A merge tag that found no field is left in place by core, and
        // an unresolved `{field:...}` is not an address.
        if ( '' === $email || ! is_email( $email ) ) {
            return '';
        }

        return sanitize_email( $email );
    }

    /**
     * Halt the submission and put PayPal's own buttons in front of the
     * visitor, without leaving the page.
     *
     * No order is made here. PayPal require every order to name the method
     * it will be paid with, and nobody knows that until the visitor presses a
     * button - so this pass asks the service only for the SDK setup, halts,
     * and NF_AJAX_Controllers_PayPalOrder creates the order on the click. The
     * amount, currency and everything else the order needs are decided here,
     * server-side, and stored with the halted submission; the browser gets to
     * say which button was pressed and nothing else. Decision, 2026-09-09.
     *
     * Halting is core's existing contract for a payment that cannot finish in
     * one pass; core's own resume handler names returning "from PayPal or
     * Stripe". Only the trip changed, not the seam.
     *
     * Public, not protected as the abstract declares: Collect Payment calls
     * this directly on the gateway object.
     *
     * @param array      $action_settings The payment action's settings, merge tags resolved.
     * @param int|string $form_id         The form being submitted.
     * @param array      $data            The submission data; carries 'resume' on the return leg.
     * @return array The submission data: halted with the order details in
     *               extra, completed, or carrying a form error.
     */
    public function process( $action_settings, $form_id, $data )
    {
        // Same method, both legs of the trip: core runs the halted action
        // again when the visitor comes back, with the return URL's parameters
        // in $data['resume'].
        if ( isset( $data[ 'resume' ] ) ) {
            return $this->complete( $data, $action_settings );
        }

        // Fail closed. Whatever the reason — never connected, PayPal has the
        // account restricted, an add-on has taken over, our service is
        // unreachable — the visitor is told plainly and the submission is
        // preserved unpaid. A submission is never recorded as paid when no
        // money moved.
        if ( ! NF_PayPal_Connection::can_process_payments() ) {
            return $this->fail( $data, $action_settings );
        }

        $total = isset( $action_settings[ 'payment_total' ] ) ? (float) $action_settings[ 'payment_total' ] : 0;
        if ( $total <= 0 ) {
            return $this->fail( $data, $action_settings, __( 'This form has no amount to charge.', 'ninja-forms' ) );
        }

        $connection = NF_PayPal_Connection::get();
        $funding    = $this->funding( $action_settings );

        // The merchant's choices for this form shape the SDK setup, which the
        // service builds — the plugin never assembles it.
        $prepared = NF_PayPal_Service::prepare_checkout( array(
            'merchantId' => $connection[ 'merchant_id' ],
            'currency'   => $this->currency(),
            'funding'    => $funding,
        ) );

        if ( is_wp_error( $prepared ) ) {
            return $this->fail( $data, $action_settings );
        }

        // Without PayPal's script setup the page can only offer the
        // deterministic test button, which exists for the sandbox and the
        // walkthrough's fake service. A live checkout fails closed instead of
        // putting a stand-in control in front of a real visitor.
        if ( 'sandbox' !== $connection[ 'environment' ] && empty( $prepared[ 'sdk' ][ 'url' ] ) ) {
            return $this->fail( $data, $action_settings );
        }

        // Ride the submission's existing extra data rather than inventing
        // storage; core already preserves a halted action's extra data when it
        // resumes, and this is also how the SDK setup reaches the browser —
        // the submit response carries `extra` back to the page. Everything
        // the order will need is fixed here so the click that creates it
        // cannot change any of it.
        $data[ 'extra' ][ 'nf_paypal_order' ] = array(
            'orderId'    => '',
            'method'     => '',
            'status'     => 'awaiting_method',
            'merchantId' => $connection[ 'merchant_id' ],
            'amount'     => number_format( $total, 2, '.', '' ),
            'currency'   => $this->currency(),
            'formId'     => $form_id,
            'returnUrl'  => $this->return_url( $form_id, 'success' ),
            'cancelUrl'  => $this->return_url( $form_id, 'cancel' ),
            'funding'    => $funding,
            // Empty far more often than not, and that is fine.
            'buyerEmail' => $this->buyer_email( $action_settings ),
            'itemName'   => $this->item_name( $action_settings, $form_id ),
            'brandName'  => $this->brand_name(),
            // How the page sets PayPal's SDK up, decided by the service and
            // never by the plugin. Absent when the service has no PayPal
            // credentials, which is the walkthrough's case: no real buttons
            // exist to render, so the front end shows its deterministic test
            // button instead.
            'sdk'        => isset( $prepared[ 'sdk' ] ) ? $prepared[ 'sdk' ] : null,
        );

        // The entry already exists - core's Save action runs before payment -
        // and until now it said nothing about money, so a visitor who never
        // paid left an entry indistinguishable from a finished one (live,
        // 2026-10-01). It says Not paid from this moment until a capture says
        // otherwise.
        // The environment is stamped from the first write, not only on
        // capture: it is what marks the entry as core's own. The paid PayPal
        // add-on stores paypal_status under the same name, and core's panels
        // show only entries carrying this.
        $this->record( $data, array(
            'paypal_status'      => 'awaiting_payment',
            'paypal_environment' => $connection[ 'environment' ],
        ) );

        // No redirect. PayPal's own buttons take it from here, on this page,
        // and the visitor never leaves the site — the same shape the paid
        // PayPal Checkout add-on ships. The submission halts exactly as before
        // and resumes when the buttons report approval.
        $data[ 'halt' ] = true;

        return $data;
    }

    /**
     * The one line item's name, in the merchant's own words.
     *
     * PayPal require a line item on every order, and a form builder cannot
     * know whether a payment is a donation, a ticket or a fee. So the words
     * are the merchant's: the Item Name they set on the action (merge tags
     * already resolved by core), or the form's title when they set nothing.
     *
     * @param array      $action_settings The payment action's settings, merge tags resolved.
     * @param int|string $form_id         The form being submitted.
     * @return string At most 127 characters.
     */
    private function item_name( array $action_settings, $form_id ): string
    {
        $name = isset( $action_settings[ 'item_name' ] ) ? trim( (string) $action_settings[ 'item_name' ] ) : '';

        if ( '' === $name ) {
            $form = Ninja_Forms()->form( $form_id )->get();
            $name = $form ? trim( (string) $form->get_setting( 'title' ) ) : '';
        }

        // PayPal cap item names at 127 characters.
        return mb_substr( sanitize_text_field( $name ), 0, 127 );
    }

    /**
     * What PayPal's checkout calls the seller: the merchant's site, because it
     * is their customer paying them. Never "Ninja Forms".
     *
     * @return string At most 127 characters.
     */
    private function brand_name(): string
    {
        return mb_substr( sanitize_text_field( wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) ), 0, 127 );
    }

    /**
     * The visitor is back from PayPal. Capture the money, then let the rest of
     * the form's actions run — emails and the like fire here, on the resumed
     * pass, so they go out once and only for a payment that actually happened.
     *
     * @param array $data            The resumed submission data.
     * @param array $action_settings The payment action's settings.
     * @return array The submission data, with a form error unless the payment
     *               completed or is pending.
     */
    private function complete( array $data, array $action_settings ): array
    {
        $resume = $data[ 'resume' ];

        if ( isset( $resume[ 'nf_paypal' ] ) && 'cancel' === $resume[ 'nf_paypal' ] ) {
            $this->record( $data, array( 'paypal_status' => 'cancelled' ) );
            $data[ 'errors' ][ 'form' ][ 'paypal' ] = __( 'The PayPal payment was cancelled. Please try again.', 'ninja-forms' );
            return $data;
        }

        // Only ever the order this submission created, read from the server's
        // own copy of the halted submission: the order step wrote the order id
        // there, and the amount and currency fixed at submit travel with it.
        // Nothing the browser sends back as `extra` is consulted, so no
        // request can name a different order or a different amount. (There
        // used to be a fallback to a `token` parameter off the resumed URL,
        // and before that the resumed `extra` itself was read; both let the
        // browser choose.) The server's copy also stands in for the resumed
        // extra from here on, so what the page is told matches the record.
        $order    = $this->halted_order();
        $order_id = ! empty( $order[ 'orderId' ] ) ? (string) $order[ 'orderId' ] : '';

        $data[ 'extra' ][ 'nf_paypal_order' ] = $order;

        // Already captured once. A visitor who reloads the return URL — which
        // carries nf_resume, so the browser re-submits — must not drive a
        // second capture, and must not turn a recorded payment into a failure
        // when PayPal rightly refuses the repeat.
        //
        // The entry is what gets asked, not the request or the halted
        // session: the session still holds the state as it was when the
        // visitor left for PayPal, so it says nothing about whether the money
        // has since moved. The entry is the only record that was updated, and
        // only this gateway writes payment values onto it (visitor-supplied
        // ones are dropped before any action runs, see
        // strip_visitor_payment_values()). A completed record for some other
        // order is not this submission's payment, so it does not stand in the
        // way of capturing the order this submission created (a completed
        // record with no order id is honoured as it stands).
        $recorded          = $this->recorded_status( $data );
        $recorded_order_id = $this->recorded_value( $data, 'paypal_order_id' );

        if ( 'completed' === $recorded && ( '' === $order_id || '' === $recorded_order_id || $recorded_order_id === $order_id ) ) {
            return $data;
        }

        // Pending and mismatched are final for this leg too. PayPal is still
        // deciding, or disagreed about the amount, and money may have moved;
        // a second capture attempt can only be refused and would relabel the
        // entry as failed. NF_PayPal_PaymentSync writes what PayPal decides.
        if ( 'pending' === $recorded ) {
            return $data;
        }

        if ( 'mismatched' === $recorded ) {
            $data[ 'errors' ][ 'form' ][ 'paypal' ] = $this->mismatch_message();
            return $data;
        }

        if ( '' === $order_id ) {
            $this->record( $data, array( 'paypal_status' => 'failed' ) );
            $data[ 'errors' ][ 'form' ][ 'paypal' ] = __( 'We could not match your payment to this submission. Please contact the site owner before trying again.', 'ninja-forms' );
            return $data;
        }

        $connection = NF_PayPal_Connection::get();
        $capture    = NF_PayPal_Service::capture_order( $order_id );

        // PENDING is not a failure — PayPal uses it for a capture under
        // review. Telling the visitor they were not charged, and telling the
        // merchant the payment failed, would both be false.
        if ( ! is_wp_error( $capture ) && ! empty( $capture[ 'status' ] ) && 'PENDING' === $capture[ 'status' ] ) {
            $this->record( $data, array(
                'paypal_status'   => 'pending',
                'paypal_order_id' => $order_id,
            ) );

            $data[ 'extra' ][ 'nf_paypal_order' ][ 'status' ] = 'pending';

            return $data;
        }

        // The service refuses to call a capture completed when the amount it
        // read back from PayPal disagrees with the order. That is not the same
        // as a failure, and it must not be told to the visitor as one: money
        // may well have moved. Saying "you have not been charged" here would
        // be the plugin inventing a fact it does not have.
        if ( ! is_wp_error( $capture ) && ! empty( $capture[ 'status' ] ) && 'AMOUNT_MISMATCH' === $capture[ 'status' ] ) {
            return $this->mismatched( $data, $order_id );
        }

        if ( is_wp_error( $capture ) || empty( $capture[ 'status' ] ) || 'COMPLETED' !== $capture[ 'status' ] ) {
            $this->record( $data, array( 'paypal_status' => 'failed', 'paypal_order_id' => $order_id ) );
            $data[ 'errors' ][ 'form' ][ 'paypal' ] = __( 'PayPal could not complete your payment. You have not been charged. Please try again.', 'ninja-forms' );
            return $data;
        }

        // What was charged, as the service read it back from PayPal — not what
        // this action would compute now. On a form whose total comes from a
        // field or a calculation those two can differ, and only one of them is
        // the amount the visitor actually paid.
        $total    = isset( $capture[ 'amount' ] ) ? (string) $capture[ 'amount' ] : '';
        $currency = isset( $capture[ 'currency' ] ) ? (string) $capture[ 'currency' ] : '';

        // And it must be the amount this submission fixed at submit. The
        // service already refuses a capture that disagrees with its order;
        // this is the plugin's own check that the order captured is the one
        // this submission created, in the amount and currency it asked for.
        if ( ! $this->matches_order( $order, $total, $currency ) ) {
            return $this->mismatched( $data, $order_id );
        }

        $this->record( $data, array(
            'paypal_status'         => 'completed',
            'paypal_order_id'       => $order_id,
            // Defaulted rather than indexed: this is the success path, after
            // money has already moved, and a missing field here must not fatal
            // in front of a visitor who has just paid.
            'paypal_transaction_id' => isset( $capture[ 'transactionId' ] ) ? $capture[ 'transactionId' ] : '',
            'paypal_total'          => '' !== $total ? $total : ( isset( $order[ 'amount' ] ) ? $order[ 'amount' ] : '' ),
            'paypal_currency'       => '' !== $currency ? $currency : ( isset( $order[ 'currency' ] ) ? $order[ 'currency' ] : $this->currency() ),
            // Stamped on the payment, not read from the site later: a site that
            // moves from sandbox to live must not relabel its old test payments.
            'paypal_environment'    => $connection[ 'environment' ],
        ) );

        $data[ 'extra' ][ 'nf_paypal_order' ][ 'status' ]        = 'completed';
        $data[ 'extra' ][ 'nf_paypal_order' ][ 'transactionId' ] = isset( $capture[ 'transactionId' ] ) ? $capture[ 'transactionId' ] : '';

        // The fee notice is owed after the first real payment. A sandbox
        // payment carries no fee, and must not use up the one-time notice
        // before the site takes live money.
        if ( 'live' === $connection[ 'environment' ] ) {
            NF_PayPal_FirstPaymentNotice::payment_taken();
        }

        return $data;
    }

    /**
     * What the entry already says about this payment, if anything.
     *
     * @param array $data The submission data.
     * @return string The stored paypal_status, or '' without a saved entry.
     */
    private function recorded_status( array $data ): string
    {
        return $this->recorded_value( $data, 'paypal_status' );
    }

    /**
     * One payment value the entry already carries.
     *
     * @param array  $data The submission data.
     * @param string $key  The extra value's name.
     * @return string The stored value, or '' without a saved entry.
     */
    private function recorded_value( array $data, string $key ): string
    {
        $sub = self::saved_entry( $data );
        if ( null === $sub ) return '';

        return (string) $sub->get_extra_value( $key );
    }

    /**
     * The entry the Save action created for this submission, where the
     * payment record lives. The one place that knows how to find it, for
     * the read path and the write path alike.
     *
     * @param array $data The submission data.
     * @return NF_Database_Models_Submission|null The saved entry, or null when
     *                                            the Save action has not run.
     */
    private static function saved_entry( array $data )
    {
        $sub_id = isset( $data[ 'actions' ][ 'save' ][ 'sub_id' ] ) ? $data[ 'actions' ][ 'save' ][ 'sub_id' ] : false;
        if ( ! $sub_id ) return null;

        return Ninja_Forms()->form()->sub( $sub_id )->get();
    }

    /**
     * The order this submission created, from the server's own copy.
     *
     * Core keeps the halted submission in its session while the visitor is
     * paying; process() put the order's fixed facts there, and the order
     * step (NF_AJAX_Controllers_PayPalOrder) wrote the order id into the same
     * record. That copy is the only source the resume leg reads.
     *
     * @return array The nf_paypal_order record, or an empty array when the
     *               session holds none.
     */
    private function halted_order(): array
    {
        $session = Ninja_Forms()->session();
        $halted  = $session ? $session->get( 'nf_processing_data' ) : null;
        $order   = is_array( $halted ) && isset( $halted[ 'extra' ][ 'nf_paypal_order' ] ) ? $halted[ 'extra' ][ 'nf_paypal_order' ] : null;

        return is_array( $order ) ? $order : array();
    }

    /**
     * Whether a capture is for the amount and currency this submission fixed.
     *
     * @param array  $order    The server's order record.
     * @param string $total    The captured amount, as the service read it back.
     * @param string $currency The captured currency.
     * @return bool
     */
    private function matches_order( array $order, string $total, string $currency ): bool
    {
        if ( '' !== $total && isset( $order[ 'amount' ] ) && '' !== (string) $order[ 'amount' ]
            && abs( (float) $order[ 'amount' ] - (float) $total ) >= 0.005 ) {
            return false;
        }

        if ( '' !== $currency && ! empty( $order[ 'currency' ] )
            && strtoupper( (string) $order[ 'currency' ] ) !== strtoupper( $currency ) ) {
            return false;
        }

        return true;
    }

    /**
     * The one way a capture whose amount could not be confirmed is recorded:
     * mismatched on the entry with the order id, mismatched on the order
     * record the page is told, and the visitor warned. Reached when the
     * service reports AMOUNT_MISMATCH and when the plugin's own check
     * against the amount fixed at submit fails.
     *
     * @param array  $data     The resumed submission data.
     * @param string $order_id The order that was captured.
     * @return array The submission data, carrying the form error.
     */
    private function mismatched( array $data, string $order_id ): array
    {
        $this->record( $data, array(
            'paypal_status'   => 'mismatched',
            'paypal_order_id' => $order_id,
        ) );

        $data[ 'extra' ][ 'nf_paypal_order' ][ 'status' ] = 'mismatched';

        $data[ 'errors' ][ 'form' ][ 'paypal' ] = $this->mismatch_message();

        return $data;
    }

    /**
     * What the visitor is told when the amount could not be confirmed. Never
     * "you have not been charged": the plugin does not know that.
     *
     * @return string
     */
    private function mismatch_message(): string
    {
        return __( 'We could not confirm the amount of your payment, so this form has not been sent. Do not try again — please contact the site owner, who can check whether you were charged.', 'ninja-forms' );
    }

    /**
     * Nothing about a payment is ever taken from the browser.
     *
     * Core stores a submission's `extra` data as the browser sent it and
     * hands it to every action, so a request could carry a payment status or
     * an order record of its own. The keys this gateway writes are removed
     * before anything reads or saves them; the gateway sets its own, on the
     * server. Hooked on ninja_forms_submit_data, which runs before the Save
     * action and before any gateway code.
     *
     * @param array $form_data The submitted form data.
     * @return array The same, without any paypal_* or nf_paypal_order extra.
     */
    public static function strip_visitor_payment_values( $form_data )
    {
        if ( ! is_array( $form_data ) || empty( $form_data[ 'extra' ] ) || ! is_array( $form_data[ 'extra' ] ) ) {
            return $form_data;
        }

        foreach ( array_keys( $form_data[ 'extra' ] ) as $key ) {
            if ( 'nf_paypal_order' === $key || 0 === strpos( (string) $key, 'paypal_' ) ) {
                unset( $form_data[ 'extra' ][ $key ] );
            }
        }

        return $form_data;
    }

    /**
     * Write the payment onto the entry, so the merchant can see it there.
     *
     * @param array $data   The submission data.
     * @param array $values Extra values to write, keyed by name.
     * @return void
     */
    private function record( array $data, array $values ): void
    {
        self::record_on_entry( $data, $values );
    }

    /**
     * The same, for the order step, which runs outside the action: it reaches
     * the entry through the halted submission core keeps in its session.
     *
     * Never turns a completed payment into anything but a refund, and never
     * turns a pending or mismatched payment into a failure.
     *
     * @param array $data   The submission data, as halted.
     * @param array $values Extra values to write, keyed by name.
     * @return void
     */
    public static function record_on_entry( array $data, array $values ): void
    {
        $sub = self::saved_entry( $data );
        if ( null === $sub ) return;

        // A completed payment is final. Nothing that runs later may relabel
        // money that actually moved — the transaction id would sit there
        // contradicting the status, and the merchant would be told a payment
        // failed that their PayPal account shows succeeding. The one thing
        // that legitimately comes after completed is the money going back,
        // which NF_PayPal_PaymentSync writes and which this must let through.
        $current = (string) $sub->get_extra_value( 'paypal_status' );
        $next    = isset( $values[ 'paypal_status' ] ) ? (string) $values[ 'paypal_status' ] : null;

        if ( 'completed' === $current
            && null !== $next
            && ! in_array( $next, array( 'completed', 'refunded', 'partially_refunded' ), true ) ) {
            return;
        }

        // Pending and mismatched are PayPal's to resolve, and
        // NF_PayPal_PaymentSync writes what PayPal later says. A refused
        // repeat capture, or a cancel, must not relabel money that may have
        // moved as a failure.
        if ( in_array( $current, array( 'pending', 'mismatched' ), true )
            && in_array( $next, array( 'failed', 'cancelled' ), true ) ) {
            return;
        }

        foreach ( $values as $key => $value ) {
            $sub->update_extra_value( $key, $value );
        }
        $sub->save();
    }

    /**
     * Stop the submission with a message the visitor can act on. No retry
     * loop, no queueing — reliability is ours to fix, not something to paper
     * over at checkout.
     *
     * @param array  $data            The submission data.
     * @param array  $action_settings The payment action's settings.
     * @param string $message         Visitor-facing reason; a generic one when ''.
     * @return array The submission data, carrying the form error.
     */
    private function fail( array $data, array $action_settings, string $message = '' ): array
    {
        if ( '' === $message ) {
            $message = esc_html__( 'Payment is temporarily unavailable. Please try again in a few minutes.', 'ninja-forms' );
        }

        // The entry was saved before this action ran; it must not read as a
        // finished submission when nobody could pay.
        $this->record( $data, array( 'paypal_status' => 'failed' ) );

        $data[ 'errors' ][ 'form' ][ 'paypal' ] = $message;

        return $data;
    }

    /**
     * The site's Ninja Forms currency setting, as PayPal's upper-case code.
     *
     * @return string Defaults to 'USD' when unset.
     */
    private function currency(): string
    {
        $currency = Ninja_Forms()->get_setting( 'currency' );
        return $currency ? strtoupper( $currency ) : 'USD';
    }

    /**
     * Back to the page the form is on, carrying the flag core's front end
     * watches for: seeing nf_resume, it re-submits and the halted action picks
     * up where it left off.
     *
     * @param int|string $form_id The form being submitted.
     * @param string     $outcome 'success' or 'cancel'.
     * @return string
     */
    private function return_url( $form_id, string $outcome ): string
    {
        $page = wp_get_referer();
        if ( ! $page ) $page = home_url();

        return add_query_arg(
            array( 'nf_resume' => (int) $form_id, 'nf_paypal' => $outcome ),
            $page
        );
    }
}
