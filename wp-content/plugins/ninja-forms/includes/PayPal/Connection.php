<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The merchant's PayPal connection state, as stored on this site.
 *
 * This holds no PayPal credential of any kind. Under PayPal's third-party
 * partner model the partner client secret lives only on the Saturday Drive
 * service, so the site stores nothing more than who the merchant is and what
 * PayPal last told us about their account.
 */
final class NF_PayPal_Connection
{
    /** The option the whole connection state is stored in. */
    const OPTION = 'nf_paypal_connect';

    /** Onboarding attempts kept for support; PayPal ties a tracking ID to an account permanently. */
    const MAX_ATTEMPTS = 20;

    /**
     * How long an unfinished attempt is still news worth telling the merchant
     * about. The attempt itself stays open longer: PayPal's link to it is
     * permanent, so a merchant who finishes verifying hours later is still
     * found the next time the Payments page is opened.
     */
    const ATTEMPT_NEWS_FOR = 1800;

    /**
     * How long a sign-up link we have not used yet is offered again. PayPal's
     * links work once; an unused one is reused rather than minting a referral
     * (and a service credential) every time the Payments page is opened.
     */
    const LINK_FRESH_FOR = 3600;

    /**
     * Which service a *new* connection is made against.
     *
     * Live, unless this site deliberately opts out. Sandbox was the default
     * while this was being built, and that is precisely the wrong thing to
     * ship: a merchant pointed at the sandbox service would see a successful
     * connection, a working checkout and completed payments on their entries,
     * while no money moved anywhere at all. Nothing would look broken until
     * they checked a PayPal balance. The reassuring default is the dangerous
     * one here, so it is not the default.
     *
     * The opt-out is ours, for testing, and is deliberately not reachable by a
     * merchant: define NF_PAYPAL_SANDBOX in wp-config.php, or filter it.
     *
     * This never re-points an existing connection. The environment is stamped
     * on the connection when it is made and read back from storage from then
     * on, so a site connected in sandbox stays in sandbox even after this
     * starts answering 'live' — which matters, because a sandbox credential
     * sent to the live service would simply be refused, and the merchant would
     * be told their connection had failed for no reason they could act on.
     *
     * @return string 'live' or 'sandbox'
     */
    public static function default_environment(): string
    {
        $sandbox = defined( 'NF_PAYPAL_SANDBOX' ) && NF_PAYPAL_SANDBOX;

        /**
         * Force new connections to the sandbox service.
         *
         * @param bool $sandbox
         */
        $sandbox = (bool) apply_filters( 'ninja_forms_paypal_use_sandbox', $sandbox );

        return $sandbox ? 'sandbox' : 'live';
    }

    /**
     * The state of a site that has never connected; every stored key is
     * present here, so readers never need an isset().
     *
     * @return array
     */
    public static function defaults(): array
    {
        return array(
            'merchant_id'             => '',
            'tracking_id'             => '',
            // Issued by the service when onboarding begins, and used to sign
            // every later request. Never sent anywhere; only signatures are.
            'site_id'                 => '',
            'credential'              => '',
            'attempts'                => array(),
            // Whether the merchant has already been told that their last
            // onboarding went unfinished. See unfinished_attempt().
            'unfinished_told'         => false,
            'payments_receivable'     => false,
            'primary_email_confirmed' => false,
            'scopes'                  => array(),
            'last_checked'            => 0,
            'environment'             => self::default_environment(),
        );
    }

    /**
     * The stored state, filled out with defaults for any key not yet stored.
     *
     * May come from WordPress's in-request option cache; use fresh() before
     * writing after a wait.
     *
     * @return array
     */
    public static function get(): array
    {
        $stored = get_option( self::OPTION, array() );
        if ( ! is_array( $stored ) ) $stored = array();
        return array_merge( self::defaults(), $stored );
    }

    /**
     * The stored state as it is now, not as this request first read it.
     *
     * WordPress keeps an option in memory for the rest of a request once it has
     * been read. A request that waits on PayPal and then writes would otherwise
     * write back what it read before waiting — and if the merchant pressed
     * Disconnect in the meantime, that silently reconnects them (seen on
     * staging, 2026-09-17: a page-load status check outlived a Disconnect).
     *
     * @return array
     */
    public static function fresh(): array
    {
        wp_cache_delete( self::OPTION, 'options' );

        return self::get();
    }

    /**
     * Store the state, filling in defaults; not autoloaded, since only the
     * Payments page and checkout read it.
     *
     * @param array $state The full or partial connection state.
     * @return void
     */
    public static function save( array $state ): void
    {
        update_option( self::OPTION, array_merge( self::defaults(), $state ), false );
    }

    /**
     * A merchant is connected once onboarding has given us their payer ID.
     * Whether they may actually take money is a further question — see
     * can_process_payments(), which PayPal gates separately.
     *
     * @return bool
     */
    public static function is_connected(): bool
    {
        $state = self::get();
        return '' !== $state[ 'merchant_id' ];
    }

    /**
     * PayPal's three gates. All must hold, per the partner Solution Design
     * Document: a merchant can be connected and still unable to be paid.
     *
     * @return bool
     */
    public static function can_process_payments(): bool
    {
        // Deliberately delegated rather than restated. This and blocker()
        // encoded the same five conditions in two places, which can only
        // drift: the day PayPal adds a gate, one of them gets it.
        return '' === self::blocker();
    }

    /**
     * Why payments are unavailable, or '' when they are available. The two
     * PayPal failure states get their own seller-facing copy on the page.
     *
     * @return string 'superseded_by_addon', 'not_connected',
     *                'payments_not_receivable', 'email_not_confirmed',
     *                'consent_revoked', or ''.
     */
    public static function blocker(): string
    {
        if ( NF_PayPal_ExistingIntegration::exists() ) return 'superseded_by_addon';
        if ( ! self::is_connected() )            return 'not_connected';
        $state = self::get();
        if ( ! $state[ 'payments_receivable' ] ) return 'payments_not_receivable';
        if ( ! $state[ 'primary_email_confirmed' ] ) return 'email_not_confirmed';
        if ( empty( $state[ 'scopes' ] ) )       return 'consent_revoked';
        return '';
    }

    /**
     * The service issued us a credential. Keep it; it signs everything after this.
     *
     * Called after waiting on the service, so it reads the state as it is now:
     * the Payments page asks PayPal about an open attempt at the same moment it
     * asks for a sign-up link, and that other request may have recorded the
     * merchant while this one waited. A connection made meanwhile is kept, and
     * this credential — for a referral nobody needs now — is not stored over it.
     *
     * @param string $site_id     The service's id for this site.
     * @param string $credential  The secret this site signs requests with.
     * @param string $tracking_id The new referral's tracking id.
     * @return bool Whether the credential was stored.
     */
    public static function store_credential( string $site_id, string $credential, string $tracking_id ): bool
    {
        $state = self::fresh();

        if ( '' !== $state[ 'merchant_id' ] ) return false;

        $attempts   = $state[ 'attempts' ];
        $attempts[] = array(
            'tracking_id' => sanitize_text_field( $tracking_id ),
            'started'     => time(),
            // Set when the merchant follows the sign-up link. Until then the
            // attempt is only a link waiting on the page, and there is nothing
            // to ask PayPal about.
            'opened'      => 0,
            'link'        => '',
            'completed'   => 0,
            'outcome'     => 'started',
        );

        $state[ 'site_id' ]     = sanitize_text_field( $site_id );
        $state[ 'credential' ]  = sanitize_text_field( $credential );
        $state[ 'tracking_id' ] = sanitize_text_field( $tracking_id );
        $state[ 'attempts' ]    = array_slice( $attempts, -self::MAX_ATTEMPTS );
        // A fresh attempt is a fresh chance to be told it went unfinished.
        $state[ 'unfinished_told' ] = false;

        self::save( $state );

        return true;
    }

    /**
     * Keep the sign-up link PayPal issued for the attempt just started.
     *
     * @param string      $url         PayPal's sign-up (action) URL.
     * @param string|null $tracking_id The attempt the link belongs to. When a
     *                                 newer attempt has begun meanwhile, the
     *                                 link is not pinned to that one.
     * @return void
     */
    public static function remember_link( string $url, ?string $tracking_id = null ): void
    {
        self::update_last_attempt( array( 'link' => esc_url_raw( $url ) ), $tracking_id );
    }

    /**
     * The sign-up link to offer now, or '' when a new one is needed: none was
     * issued, it has been followed (PayPal's links work once), or it is stale.
     *
     * @return string
     */
    public static function unused_link(): string
    {
        $last = self::last_attempt();

        if ( null === $last || 'started' !== $last[ 'outcome' ] ) return '';
        if ( ! empty( $last[ 'opened' ] ) || empty( $last[ 'link' ] ) ) return '';
        if ( time() - (int) $last[ 'started' ] > self::LINK_FRESH_FOR ) return '';

        return $last[ 'link' ];
    }

    /**
     * The merchant followed the sign-up link: from now on PayPal may have an answer.
     *
     * @return void
     */
    public static function mark_opened(): void
    {
        $last = self::last_attempt();

        if ( null === $last || 'started' !== $last[ 'outcome' ] ) return;

        self::update_last_attempt( array( 'opened' => time() ), $last[ 'tracking_id' ] );
    }

    /**
     * Has the merchant gone to PayPal on an attempt still waiting for an answer?
     *
     * @return bool
     */
    public static function has_opened_attempt(): bool
    {
        $last = self::last_attempt();

        return null !== $last && 'started' === $last[ 'outcome' ] && ! empty( $last[ 'opened' ] );
    }

    /**
     * The most recent onboarding attempt, or null when there has been none.
     *
     * The one place the last attempt is found, so every reader and writer
     * agrees on which attempt that is and what an attempt missing a field
     * means.
     *
     * @param array|null $state The state to read; the stored one when omitted.
     * @return array|null The attempt with every field present, or null.
     */
    private static function last_attempt( ?array $state = null ): ?array
    {
        if ( null === $state ) $state = self::get();

        $attempts = isset( $state[ 'attempts' ] ) ? $state[ 'attempts' ] : array();

        if ( empty( $attempts ) ) return null;

        return array_merge(
            array( 'tracking_id' => '', 'opened' => 0, 'link' => '', 'started' => 0, 'completed' => 0, 'outcome' => '' ),
            end( $attempts )
        );
    }

    /**
     * $state with $changes written onto its last attempt. Does not save.
     *
     * @param array $state   The connection state.
     * @param array $changes Attempt fields to overwrite.
     * @return array The state, unchanged when there is no attempt.
     */
    private static function change_last_attempt( array $state, array $changes ): array
    {
        $attempts = $state[ 'attempts' ];
        $last     = count( $attempts ) - 1;

        if ( $last < 0 ) return $state;

        $attempts[ $last ]   = array_merge( $attempts[ $last ], $changes );
        $state[ 'attempts' ] = $attempts;

        return $state;
    }

    /**
     * Write $changes onto the last attempt, as stored now rather than as this
     * request first read it — a request that waited on the service must not
     * write back a connection recorded by another one meanwhile.
     *
     * @param array       $changes     Attempt fields to overwrite.
     * @param string|null $tracking_id Only when the last attempt is this one.
     * @return void
     */
    private static function update_last_attempt( array $changes, ?string $tracking_id = null ): void
    {
        $state = self::fresh();
        $last  = self::last_attempt( $state );

        if ( null === $last ) return;
        if ( null !== $tracking_id && $last[ 'tracking_id' ] !== $tracking_id ) return;

        self::save( self::change_last_attempt( $state, $changes ) );
    }

    /**
     * Is there an onboarding attempt actually waiting to be completed?
     *
     * @return bool
     */
    public static function has_open_attempt(): bool
    {
        $last = self::last_attempt();

        return null !== $last && 'started' === $last[ 'outcome' ];
    }

    /**
     * Should the merchant be told their last attempt connected nothing?
     *
     * Only ever asked after PayPal have said nothing is linked to the attempt,
     * so this is never a guess about a merchant who did grant permission. A
     * seller who backs out of PayPal's permission screen would otherwise land
     * on the same Connect button they pressed a minute ago with nothing
     * acknowledging it, which reads as a broken button.
     *
     * True once per attempt, because it is news the first time and clutter
     * after that. Being told is recorded rather than the attempt being closed:
     * the attempt stays open so a merchant who finishes later is still found.
     * Not said about an attempt older than ATTEMPT_NEWS_FOR — that is
     * archaeology rather than news.
     *
     * @return bool Whether the merchant should be told, now.
     */
    public static function unfinished_attempt(): bool
    {
        // Asked after waiting on PayPal, and it writes: read what is stored now.
        $state = self::fresh();

        if ( '' !== $state[ 'merchant_id' ] ) return false;

        if ( ! empty( $state[ 'unfinished_told' ] ) ) return false;

        $last = self::last_attempt( $state );

        if ( null === $last || 'started' !== $last[ 'outcome' ] ) return false;

        // A link nobody followed is not an unfinished connection.
        if ( empty( $last[ 'opened' ] ) ) return false;

        if ( time() - (int) $last[ 'started' ] > self::ATTEMPT_NEWS_FOR ) return false;

        $state[ 'unfinished_told' ] = true;
        self::save( $state );

        return true;
    }

    /**
     * Close an attempt nobody is going to finish.
     *
     * A deliberate Disconnect ends whatever attempt was still open, so the
     * page it lands on does not greet the merchant with news about a connection
     * they have just chosen to remove.
     *
     * @return void
     */
    public static function abandon_attempt(): void
    {
        $state = self::fresh();
        $last  = self::last_attempt( $state );

        if ( null === $last || 'started' !== $last[ 'outcome' ] ) return;

        self::save( self::change_last_attempt( $state, array(
            'completed' => time(),
            'outcome'   => 'abandoned',
        ) ) );
    }

    /**
     * PayPal named the merchant linked to this attempt. The merchant and the
     * status flags both come from PayPal by way of the service, never from a
     * browser — we only record what we were told and when.
     *
     * @param string      $merchant_id PayPal's id for the linked merchant.
     * @param array       $status      Gating flags: payments_receivable,
     *                                 primary_email_confirmed, scopes.
     * @param string|null $tracking_id The attempt PayPal were asked about;
     *                                 nothing is written when it is no longer
     *                                 the stored one.
     * @return bool Whether the connection was recorded.
     */
    public static function complete_attempt( string $merchant_id, array $status, ?string $tracking_id = null ): bool
    {
        $state = self::fresh();

        // The attempt PayPal were asked about has to be the one still here. A
        // Disconnect or a new Connect while PayPal answered means this answer
        // is about a connection nobody wants any more.
        if ( null !== $tracking_id && $state[ 'tracking_id' ] !== $tracking_id ) return false;

        $last = self::last_attempt( $state );
        if ( null !== $last && 'started' === $last[ 'outcome' ] ) {
            $state = self::change_last_attempt( $state, array(
                'completed' => time(),
                'outcome'   => 'connected',
            ) );
        }

        $state[ 'merchant_id' ] = sanitize_text_field( $merchant_id );
        $state                  = self::apply_status( $state, $status );

        self::save( $state );

        return true;
    }

    /**
     * Refresh the three gates from PayPal without touching the merchant identity.
     *
     * @param array       $status  Gating flags: payments_receivable,
     *                             primary_email_confirmed, scopes.
     * @param string|null $site_id The connection PayPal were asked about. When
     *                             it is no longer the stored one — disconnected
     *                             or replaced while PayPal answered — nothing
     *                             is written.
     *
     * @return bool Whether the answer was recorded.
     */
    public static function update_status( array $status, ?string $site_id = null ): bool
    {
        $state = self::fresh();

        if ( null !== $site_id && ( '' === $state[ 'merchant_id' ] || $state[ 'site_id' ] !== $site_id ) ) return false;

        self::save( self::apply_status( $state, $status ) );

        return true;
    }

    /**
     * Fold PayPal's three gating flags into the stored state.
     *
     * One copy, because the two callers must agree: completing onboarding and
     * refreshing afterwards write exactly the same fields, and a change made
     * to one of two identical blocks is a change half-made.
     *
     * @param array $state  The connection state.
     * @param array $status Gating flags: payments_receivable,
     *                      primary_email_confirmed, scopes.
     * @return array The state with the flags and last_checked written. Does not save.
     */
    private static function apply_status( array $state, array $status ): array
    {
        $state[ 'payments_receivable' ]     = ! empty( $status[ 'payments_receivable' ] );
        $state[ 'primary_email_confirmed' ] = ! empty( $status[ 'primary_email_confirmed' ] );
        $state[ 'scopes' ]                  = isset( $status[ 'scopes' ] ) ? array_map( 'sanitize_text_field', (array) $status[ 'scopes' ] ) : array();
        $state[ 'last_checked' ]            = time();

        return $state;
    }

    /**
     * Where to look this payment up in PayPal.
     *
     * Built from the environment stamped on the payment rather than the site's
     * current one: a site that has moved from sandbox to live must not send
     * someone hunting for an old test payment on the live site.
     *
     * @param string $transaction_id PayPal's capture (transaction) id.
     * @param string $environment    'live' or 'sandbox'.
     * @return string The PayPal activity URL, or '' without a transaction id.
     */
    public static function transaction_url( string $transaction_id, string $environment ): string
    {
        if ( ! $transaction_id ) return '';

        $host = 'live' === $environment ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com';

        return $host . '/activity/payment/' . rawurlencode( $transaction_id );
    }

    /**
     * Forget the merchant locally. This cannot revoke consent on PayPal's side
     * — only the merchant can do that from their own PayPal account — so the
     * page points them there rather than implying we did it for them.
     *
     * The attempts history is kept for support.
     *
     * @return void
     */
    public static function disconnect(): void
    {
        self::abandon_attempt();

        // defaults() clears site_id and credential as well: the service has
        // revoked them, and signing with a revoked credential would simply be
        // refused. A reconnect is issued a fresh pair.
        $state               = self::defaults();
        $previous            = self::fresh();
        $state[ 'attempts' ] = $previous[ 'attempts' ];
        self::save( $state );
    }

    /**
     * The shape the dashboard's React app reads.
     *
     * @return array connected, canProcess, blocker, merchantId, scopes,
     *               environment, lastChecked, supersededBy.
     */
    public static function for_dashboard(): array
    {
        $state = self::get();
        return array(
            'connected'   => self::is_connected(),
            'canProcess'  => self::can_process_payments(),
            'blocker'     => self::blocker(),
            'merchantId'  => $state[ 'merchant_id' ],
            // Shown to the merchant, not just gated on: PayPal ask that a
            // connected seller can see what they granted (certification item
            // 14). These are the trailing names of PayPal's scope URIs, as
            // the service hands them over.
            'scopes'      => array_values( $state[ 'scopes' ] ),
            'environment' => $state[ 'environment' ],
            'lastChecked' => (int) $state[ 'last_checked' ],
            'supersededBy' => NF_PayPal_ExistingIntegration::name(),
        );
    }
}
