<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The Payments submenu under Ninja Forms.
 *
 * The page itself is drawn by the React dashboard (the menu slug is a hash
 * route into it), so this class only registers the menu entry and decides
 * when it should not appear.
 */
final class NF_Admin_Menus_Payments extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $page_title = 'Payments';

    public $menu_slug = 'ninja-forms#payments';

    public $position = 3.5;

    /**
     * Register the menu, then the check that may remove it again.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        add_action( 'admin_init', array( $this, 'nf_upgrade_redirect' ) );
    }

    /**
     * Unregister the menu item while required updates are pending, or when an
     * existing PayPal add-on manages payments instead.
     *
     * @return void
     */
    public function nf_upgrade_redirect(): void {
        if ( '1' === (string) get_option( 'ninja_forms_needs_updates' ) ) {
            remove_submenu_page( $this->parent_slug, $this->menu_slug );
        }

        // A site that already has PayPal through an add-on manages it there.
        if ( NF_PayPal_ExistingIntegration::exists() ) {
            remove_submenu_page( $this->parent_slug, $this->menu_slug );
        }
    }

    /**
     * Translated page title; the class property cannot call translation functions.
     *
     * @return string
     */
    public function get_page_title()
    {
        return esc_html__( 'Payments', 'ninja-forms' );
    }

    /**
     * Capability required to see the Payments menu.
     *
     * Defaults to manage_options, the same check every PayPal Connect AJAX
     * endpoint makes, so the menu is never shown to a role the page cannot serve.
     *
     * @return string
     */
    public function get_capability()
    {
        /**
         * Filter the capability required to see the Payments menu.
         *
         * @param string $capability Default 'manage_options'.
         */
        return apply_filters( 'ninja_forms_admin_payments_capabilities', $this->capability );
    }

    /**
     * Required by the parent; the React dashboard renders the page.
     *
     * @return void
     */
    public function display()
    {
        // This section intentionally left blank.
    }

} // End Class NF_Admin_Menus_Payments
