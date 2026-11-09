<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wires PayPal Core Payments into core: its gateway, its form action, and its
 * first-payment notice.
 *
 * Collect Payment is built to be re-instantiated under another name: given
 * one, it becomes a distinct action and locks its gateway dropdown to the
 * gateway whose slug matches that name. So merchants add a "PayPal Core Payments"
 * action rather than a generic Collect Payment they then have to configure.
 *
 * The gateway and the action both stand down where a PayPal add-on already
 * provides one.
 */
final class NF_PayPal_Registration
{
    /**
     * PayPal Core Payments actions created by register_action(), awaiting their
     * translated name at init.
     *
     * @var NF_Actions_CollectPayment[]
     */
    private $actions = array();

    /**
     * Slugs of every gateway registered the last time the gateway filter ran,
     * or null until it has run.
     *
     * @var string[]|null
     */
    private $gateway_slugs = null;

    /**
     * The once-only fee reminder shown after the merchant's first payment.
     *
     * @var NF_PayPal_FirstPaymentNotice
     */
    private $first_payment_notice;

    /**
     * Hook the gateway, the action, and the builder clean-up into core.
     */
    public function __construct()
    {
        add_filter( 'ninja_forms_register_payment_gateways', array( $this, 'register_gateway' ) );
        // Before the Save action and every gateway read: payment values never
        // come from the browser (NF_PayPal_Gateway::strip_visitor_payment_values).
        add_filter( 'ninja_forms_submit_data', array( 'NF_PayPal_Gateway', 'strip_visitor_payment_values' ), 0 );
        // Last, so it sees every gateway any add-on registered.
        add_filter( 'ninja_forms_register_payment_gateways', array( $this, 'remember_gateway_slugs' ), PHP_INT_MAX );
        add_filter( 'ninja_forms_register_actions', array( $this, 'register_action' ) );
        // After core's own action_type_settings filters have run at 10.
        add_filter( 'ninja_forms_action_type_settings', array( $this, 'hide_generic_collect_payment' ), 20 );
        add_filter( 'nf_react_table_extra_value_keys', array( $this, 'register_panel_handler' ) );

        $this->first_payment_notice = new NF_PayPal_FirstPaymentNotice();
    }

    /**
     * Register the payment panel for the React submissions page.
     *
     * Keyed on paypal_environment, which only core writes (from the first write
     * of an entry). paypal_status is a name PayPal Express uses for its own,
     * and two handlers on one key means the later one silently replaces the
     * other: core's handler on paypal_status took the panel away from Express
     * entries, which carry no environment.
     *
     * @param array $handlers Extra-value key => handler class.
     * @return array
     */
    public function register_panel_handler( $handlers )
    {
        $handlers[ 'paypal_environment' ] = 'NF_PayPal_SubmissionMetabox';
        return $handlers;
    }

    /**
     * Add the PayPal Connect gateway, unless a PayPal add-on already provides one.
     *
     * @param array $gateways Registered payment gateways, keyed by slug.
     * @return array
     */
    public function register_gateway( $gateways )
    {
        if ( ! NF_PayPal_ExistingIntegration::exists() ) {
            $gateway = new NF_PayPal_Gateway();
            $gateways[ $gateway->get_slug() ] = $gateway;
        }
        return $gateways;
    }

    /**
     * Record which gateway slugs are registered, so checking them later does
     * not rebuild every gateway object.
     *
     * @param array $gateways Registered payment gateways, keyed by slug.
     * @return array The gateways, unchanged.
     */
    public function remember_gateway_slugs( $gateways )
    {
        $this->gateway_slugs = is_array( $gateways ) ? array_keys( $gateways ) : array();
        return $gateways;
    }

    /**
     * Add the PayPal Core Payments form action, unless a PayPal add-on already provides one.
     *
     * @param array $actions Registered form actions, keyed by name.
     * @return array
     */
    public function register_action( $actions )
    {
        if ( NF_PayPal_ExistingIntegration::exists() ) return $actions;

        // Untranslated here: this runs on plugins_loaded, and translating
        // before init loads the textdomain too early. Named at init instead.
        $action = new NF_Actions_CollectPayment( 'PayPal Core Payments', NF_PayPal_Gateway::SLUG );
        $action->set_documentation_url( 'https://ninjaforms.com/docs/paypal-core-payments/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Documentation&utm_content=PayPal+Core+Payments' );

        $this->actions[] = $action;
        // Priority 11 because CollectPayment::initHook sets the nicename at init/10.
        add_action( 'init', array( $this, 'translate_action_names' ), 11 );

        $actions[ NF_PayPal_Gateway::SLUG ] = $action;
        return $actions;
    }

    /**
     * Give each PayPal Core Payments action its translated name, once translations
     * may load.
     *
     * @return void
     */
    public function translate_action_names(): void
    {
        foreach ( $this->actions as $action ) {
            $action->set_nicename( esc_html__( 'PayPal Core Payments', 'ninja-forms' ) );
        }
    }

    /**
     * Hide the generic Collect Payment action when PayPal Core Payments is the only
     * gateway.
     *
     * Collect Payment only appears once some gateway is registered, so ours
     * would otherwise summon a generic action whose only choice is PayPal,
     * next to the PayPal Core Payments action that already says so.
     *
     * @param array $action_type_settings Builder action types, keyed by name.
     * @return array
     */
    public function hide_generic_collect_payment( $action_type_settings )
    {
        $slugs = $this->gateway_slugs;
        if ( null === $slugs ) {
            $slugs = array_keys( apply_filters( 'ninja_forms_register_payment_gateways', array() ) );
        }

        if ( array( NF_PayPal_Gateway::SLUG ) === $slugs ) {
            unset( $action_type_settings[ 'collectpayment' ] );
        }

        return $action_type_settings;
    }
}
