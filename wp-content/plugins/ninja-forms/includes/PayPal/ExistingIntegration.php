<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Does this site already have a PayPal path of its own?
 *
 * Core's PayPal connection stands down wherever one does. The rule is
 * PayPal-specific on purpose: a site running Stripe, Authorize.net, Elavon or
 * Recurly still sees the Payments page, because those merchants can perfectly
 * well add PayPal through core. Only an existing PayPal integration wins.
 *
 * When one is present it does not merely hide the page — it disables whatever
 * the merchant had set up in core. The add-on's own first-party connection is
 * the integration, and it carries no platform fee.
 */
final class NF_PayPal_ExistingIntegration
{
    /** The paid PayPal Checkout add-on's main class. */
    const CHECKOUT_CLASS  = 'NF_Paypal';
    /** The paid PayPal Checkout add-on's plugin path, for installs without the class loaded. */
    const CHECKOUT_PLUGIN = 'ninja-forms-paypal/ninja-forms-paypal.php';

    /** The legacy PayPal Express add-on's main class — roughly 600 merchants, never force-migrated. */
    const EXPRESS_CLASS  = 'NF_PayPalExpress';
    /** The legacy PayPal Express add-on's plugin path. */
    const EXPRESS_PLUGIN = 'ninja-forms-paypal-express/ninja-forms-paypal-express.php';

    /**
     * Which existing PayPal integration is present: 'checkout', 'express', or
     * '' for none. Checkout wins when both somehow are.
     *
     * @return string 'checkout', 'express', a filtered value, or ''.
     */
    public static function which(): string
    {
        if ( self::present( self::CHECKOUT_CLASS, self::CHECKOUT_PLUGIN ) ) return 'checkout';
        if ( self::present( self::EXPRESS_CLASS, self::EXPRESS_PLUGIN ) )   return 'express';

        /**
         * Another PayPal integration we do not know about.
         *
         * @param string $which 'checkout', 'express', or '' for none.
         */
        return (string) apply_filters( 'ninja_forms_existing_paypal_integration', '' );
    }

    /**
     * Whether any existing PayPal integration should make core stand down.
     *
     * @return bool
     */
    public static function exists(): bool
    {
        return '' !== self::which();
    }

    /**
     * The add-on's human name, for telling the merchant what took over.
     *
     * @return string Translated add-on name, or '' when none is present.
     */
    public static function name(): string
    {
        switch ( self::which() ) {
            case 'checkout': return __( 'PayPal Checkout', 'ninja-forms' );
            case 'express':  return __( 'PayPal Express', 'ninja-forms' );
            default:         return '';
        }
    }

    /**
     * Prefer the class: a merchant who installed the add-on by hand may have
     * it in a differently named folder, which the plugin path would miss.
     *
     * @param string $class  The add-on's main class name.
     * @param string $plugin The add-on's plugin path relative to the plugins directory.
     * @return bool
     */
    private static function present( string $class, string $plugin ): bool
    {
        if ( class_exists( $class ) ) return true;

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( $plugin );
    }
}
