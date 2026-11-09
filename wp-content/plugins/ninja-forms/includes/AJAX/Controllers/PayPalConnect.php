<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connect, disconnect, and status-refresh for the core PayPal connection.
 *
 * Onboarding binds the site, not the WordPress user who happened to click the
 * button, so a different administrator can refresh or disconnect later.
 */
class NF_AJAX_Controllers_PayPalConnect
{
    /**
     * How long a status answer stays good for, in seconds.
     *
     * Every refresh becomes a live PayPal API call, and PayPal rate-limits.
     * Opening the Payments page in three tabs, or two administrators looking
     * at once, would otherwise be three or six calls asking a question whose
     * answer changes when a merchant does something in PayPal — hours or days
     * apart, not seconds. Pressing "Refresh Connection" always bypasses this.
     */
    public const STATUS_FRESH_FOR = 300;

    /** Transient: PayPal's status was asked recently. See mark_status_checked(). */
    public const STATUS_CHECKED = 'nf_paypal_status_checked';

    /** Transient: an open attempt was asked about within the last minute. */
    public const ATTEMPT_CHECKED = 'nf_paypal_attempt_checked';

    /**
     * Register the Payments page's AJAX endpoints (all administrator-only).
     *
     * @return void
     */
    public function __construct()
    {
        add_action( 'wp_ajax_nf_paypal_connect',    array( $this, 'connect' ) );
        add_action( 'wp_ajax_nf_paypal_return',     array( $this, 'handle_return' ) );
        add_action( 'wp_ajax_nf_paypal_disconnect', array( $this, 'disconnect' ) );
        add_action( 'wp_ajax_nf_paypal_refresh',    array( $this, 'refresh' ) );
        add_action( 'wp_ajax_nf_paypal_check',      array( $this, 'check' ) );
        add_action( 'wp_ajax_nf_paypal_opened',     array( $this, 'opened' ) );
    }

    /**
     * The PayPal sign-up link for the Payments page's Connect button.
     *
     * PayPal's seller-onboarding guide puts the link on the page as a button
     * that their partner.js opens in PayPal's own mini-browser, so the link has
     * to exist before the merchant clicks. An unused link is offered again
     * rather than minting a referral — and a service credential — each time
     * the page is opened; PayPal's links work only once, so a followed one is
     * replaced.
     *
     * Beginning a referral is the only request the site ever makes unsigned:
     * the service issues this site its credential with it.
     *
     * Responds with the dashboard connection state plus approvalUrl, or a
     * JSON error with a merchant-facing message.
     *
     * @return void
     */
    public function connect(): void
    {
        $this->authorize();

        // A merchant who followed the last link may have finished since.
        // Minting a new referral would put the site on a new tracking id and
        // lose theirs, so ask about it first.
        if ( NF_PayPal_Connection::has_opened_attempt() ) {
            $outcome = $this->settle_attempt();

            // PayPal could not be asked, so whether that attempt connected is
            // unknown. A new referral now would replace a tracking id PayPal
            // may already have linked; say so and let Connect ask again.
            if ( is_wp_error( $outcome ) ) {
                wp_send_json_error( array( 'message' => self::unreachable() ) );
            }

            if ( 'connected' === $outcome ) {
                wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
            }
        }

        $link = NF_PayPal_Connection::unused_link();

        if ( '' === $link ) {
            $referral = NF_PayPal_Service::begin_onboarding( $this->return_url() );

            // Connected by another request — the page's own status check —
            // while the referral was being made.
            if ( is_wp_error( $referral ) && NF_PayPal_Service::ALREADY_CONNECTED === $referral->get_error_code() ) {
                wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
            }

            if ( is_wp_error( $referral ) ) {
                wp_send_json_error( array( 'message' => $referral->get_error_message() ) );
            }

            // Checked rather than assumed. A response without it would draw a
            // Connect button that goes nowhere and says nothing about why.
            if ( empty( $referral[ 'approvalUrl' ] ) ) {
                wp_send_json_error( array( 'message' => __( 'PayPal did not return a sign-up link. Please try again.', 'ninja-forms' ) ) );
            }

            $link = $referral[ 'approvalUrl' ];
            NF_PayPal_Connection::remember_link( $link, $referral[ 'trackingId' ] );
        }

        wp_send_json_success( array_merge(
            NF_PayPal_Connection::for_dashboard(),
            array( 'approvalUrl' => $link )
        ) );
    }

    /**
     * The merchant followed the sign-up link. From here PayPal may have an
     * answer, so checks begin, and the next Connect needs a fresh link.
     *
     * @return void
     */
    public function opened(): void
    {
        $this->authorize();

        NF_PayPal_Connection::mark_opened();

        // The first check after the merchant leaves must not be held back by
        // a page load a moment ago.
        delete_transient( self::ATTEMPT_CHECKED );

        wp_send_json_success( array() );
    }

    /**
     * Find out from PayPal whether the open attempt connected anyone.
     *
     * The one place a connection is made. PayPal link the merchant to our
     * referral when they grant permission, and may then never send them back —
     * a verification step, a "Save and close" or a closed window all end on
     * PayPal's own dashboard (live, 2026-09-17). So nothing here depends on the
     * trip back: the service asks PayPal which merchant is linked to this
     * site's referral, and that answer is the only thing ever stored. No
     * merchant id from a URL or a browser is read, so a crafted link has
     * nothing to offer.
     *
     * @return string|WP_Error 'connected', 'waiting' (nothing linked yet), or
     *                         'none' (no attempt to ask about)
     */
    private function settle_attempt()
    {
        if ( NF_PayPal_Connection::is_connected() ) return 'connected';
        if ( ! NF_PayPal_Connection::has_opened_attempt() ) return 'none';

        $asked_about = NF_PayPal_Connection::get()[ 'tracking_id' ];
        $answer      = NF_PayPal_Service::check_onboarding();

        if ( is_wp_error( $answer ) ) return $answer;

        if ( empty( $answer[ 'linked' ] ) || empty( $answer[ 'merchantId' ] ) ) return 'waiting';

        if ( ! NF_PayPal_Connection::complete_attempt( $answer[ 'merchantId' ], self::normalise( $answer ), $asked_about ) ) {
            // Disconnected, or a new attempt began, while PayPal answered.
            return 'none';
        }

        // Just checked, as part of completing. Recording that here stops the
        // Payments page asking PayPal again the instant it renders Connected.
        self::mark_status_checked();

        return 'connected';
    }

    /**
     * The Payments page asking, while the merchant is in PayPal's mini-browser,
     * whether they have finished. Polled every few seconds, and once more when
     * the merchant says they are done.
     *
     * `final` marks that last ask. Only then may the answer be "nothing was
     * connected" — the attempt still stays open, so a merchant who finishes
     * verifying later is found the next time the page is opened.
     *
     * Responds with the dashboard connection state plus outcome and
     * unfinishedAttempt.
     *
     * @return void
     */
    public function check(): void
    {
        $this->authorize();

        $final   = isset( $_POST[ 'final' ] ) && '1' === sanitize_text_field( wp_unslash( $_POST[ 'final' ] ) );
        $outcome = $this->settle_attempt();

        if ( is_wp_error( $outcome ) ) {
            wp_send_json_error( array( 'message' => self::unreachable() ) );
        }

        wp_send_json_success( array_merge(
            NF_PayPal_Connection::for_dashboard(),
            array(
                'outcome'           => $outcome,
                'unfinishedAttempt' => $final && 'waiting' === $outcome && NF_PayPal_Connection::unfinished_attempt(),
            )
        ) );
    }

    /**
     * Where PayPal send the merchant when they do send them back — the page
     * behind PayPal's mini-browser, which PayPal navigate here as they close it.
     *
     * Nothing PayPal append to the URL is read. The attempt is settled by
     * asking PayPal, exactly as the Payments page does; this is just an early,
     * free moment to ask. A crafted link to it can do no more than make this
     * site ask PayPal about its own referral.
     *
     * Always ends the request with a redirect to the Payments page.
     *
     * @return void
     */
    public function handle_return(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage payment connections.', 'ninja-forms' ) );
        }

        $this->settle_attempt();

        wp_safe_redirect( admin_url( 'admin.php?page=ninja-forms' ) . '#payments' );
        exit;
    }

    /**
     * Disconnect the site from PayPal, here and at the service.
     *
     * Responds with the now-disconnected dashboard connection state.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->authorize();

        // Tell the service first so the credential is revoked on its side too;
        // a failure there must not strand the merchant on a page they cannot
        // disconnect from, so the local state is cleared either way.
        NF_PayPal_Service::disconnect();
        NF_PayPal_Connection::disconnect();

        // Whatever we last checked is now about a connection that no longer
        // exists, and a reconnect within the window must not inherit it.
        delete_transient( self::STATUS_CHECKED );

        wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
    }

    /**
     * Re-read PayPal's gating flags. The Payments page does this on every load.
     *
     * Answers from the stored connection while the last check is still fresh,
     * unless the request posts force=1 (the "Refresh Connection" button).
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->authorize();

        $forced = isset( $_POST[ 'force' ] ) && '1' === sanitize_text_field( wp_unslash( $_POST[ 'force' ] ) );

        if ( ! NF_PayPal_Connection::is_connected() ) {
            // An attempt still open may have been finished on PayPal without
            // the merchant ever coming back. Opening the Payments page is when
            // they expect to see it, so ask PayPal now — at most once a minute
            // across tabs, since an attempt nobody finishes stays open until
            // the next Connect or Disconnect.
            $outcome = 'not_asked';
            if ( $forced || ! get_transient( self::ATTEMPT_CHECKED ) ) {
                set_transient( self::ATTEMPT_CHECKED, 1, MINUTE_IN_SECONDS );
                $outcome = $this->settle_attempt();
            }

            if ( 'connected' !== $outcome ) {
                // Said only after PayPal have answered that nothing is linked,
                // never on a guess — and not when PayPal could not be asked.
                wp_send_json_success( array_merge(
                    NF_PayPal_Connection::for_dashboard(),
                    array( 'unfinishedAttempt' => 'waiting' === $outcome && NF_PayPal_Connection::unfinished_attempt() )
                ) );
            }

            wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
        }

        if ( ! $forced && get_transient( self::STATUS_CHECKED ) ) {
            // Checked recently enough. The stored connection already holds the
            // three gating flags, so the page still renders the truth as of
            // that check — it just does not ask PayPal again.
            wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
        }

        $asked_about = NF_PayPal_Connection::get()[ 'site_id' ];
        $status      = NF_PayPal_Service::get_account_status();

        if ( is_wp_error( $status ) ) {
            // A Disconnect while PayPal answered revokes the credential, and
            // the answer is a refusal about a connection that is already gone.
            if ( ! NF_PayPal_Connection::fresh()[ 'merchant_id' ] ) {
                wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
            }
            wp_send_json_error( array( 'message' => $status->get_error_message() ) );
        }

        if ( ! NF_PayPal_Connection::update_status( self::normalise( $status ), $asked_about ) ) {
            // Disconnected while PayPal answered: say what is true now, and do
            // not write the old connection back.
            wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
        }

        self::mark_status_checked();

        wp_send_json_success( NF_PayPal_Connection::for_dashboard() );
    }

    /**
     * PayPal's status was just read: the Payments page need not ask again
     * until it goes stale.
     *
     * @return void
     */
    private static function mark_status_checked(): void
    {
        /**
         * How long a PayPal status answer is treated as fresh, in seconds.
         *
         * @param int $seconds Defaults to NF_AJAX_Controllers_PayPalConnect::STATUS_FRESH_FOR.
         */
        $fresh_for = (int) apply_filters( 'ninja_forms_paypal_status_fresh_for', self::STATUS_FRESH_FOR );

        set_transient( self::STATUS_CHECKED, 1, $fresh_for );
    }

    /**
     * Said when PayPal could not be asked whether an attempt connected anyone.
     *
     * @return string Translated merchant-facing message.
     */
    private static function unreachable(): string
    {
        return __( 'We could not reach PayPal to check your connection. Please try again in a moment.', 'ninja-forms' );
    }

    /**
     * The service answers in camelCase; the connection stores PayPal's own names.
     *
     * @param array $status The service's status answer (paymentsReceivable,
     *                      primaryEmailConfirmed, scopes).
     * @return array{payments_receivable: bool, primary_email_confirmed: bool, scopes: array}
     */
    private static function normalise( array $status ): array
    {
        return array(
            'payments_receivable'     => ! empty( $status[ 'paymentsReceivable' ] ),
            'primary_email_confirmed' => ! empty( $status[ 'primaryEmailConfirmed' ] ),
            'scopes'                  => isset( $status[ 'scopes' ] ) ? (array) $status[ 'scopes' ] : array(),
        );
    }

    /**
     * No fragment, ever: PayPal append their parameters to the end of the
     * return URL, and after a `#` they would never reach the server.
     *
     * @return string The admin-ajax URL that runs handle_return().
     */
    private function return_url(): string
    {
        return add_query_arg( array( 'action' => 'nf_paypal_return' ), admin_url( 'admin-ajax.php' ) );
    }

    /**
     * Refuse the request unless it carries the Payments page nonce and comes
     * from an administrator. Ends the request on failure.
     *
     * @return void
     */
    private function authorize(): void
    {
        check_ajax_referer( 'ninja_forms_paypal_connect', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to manage payment connections.', 'ninja-forms' ) ) );
        }
    }
}
