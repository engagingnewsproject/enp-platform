<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_Welcome extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $page_title = 'Welcome';

    public $menu_slug = 'nf-welcome';

    public $position = 0;

    public function __construct()
    {
        parent::__construct();

        add_action( 'admin_init', array( $this, 'nf_upgrade_redirect' ) );
        add_action( 'admin_init', array( $this, 'check_onboarding' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_the_things' ) );
    }

    /**
     * If we have required updates, unregister the menu item
     */
    public function nf_upgrade_redirect() {
        global $pagenow;
            
        if( "1" == get_option( 'ninja_forms_needs_updates' ) ) {
            remove_submenu_page( $this->parent_slug, $this->menu_slug );
        }
    }

    /**
     * If onboarding has already been completed, unregister the menu item
     */
    public function check_onboarding() {
        global $pagenow;
        $show = false;

        $onboarding = get_user_meta( get_current_user_id(), 'nf_onboarding', true );
        if( empty($onboarding) || empty($onboarding['status']) ) {
            $show = true;
        } elseif( in_array($onboarding['status'], array('enabled', 'abandoned')) ) {
            $show = true;
        }

        if( ! $show ) {
            remove_submenu_page( $this->parent_slug, $this->menu_slug );
        }
    }

    public function get_page_title()
    {
        $title = '<span style="color:#84cc1e">' . esc_html__( 'Welcome', 'ninja-forms' ) . '</span>';
        return $title;
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_add_new_capabilities', $this->capability );
    }

    public function enqueue_the_things()
    {

    }

    public function display()
    {

	    wp_enqueue_script( 'jBox', Ninja_Forms::$url . 'assets/js/min/jBox.min.js', array( 'jquery' ) );
        wp_enqueue_style( 'nf-combobox', Ninja_Forms::$url . 'assets/css/combobox.css' );
	    wp_enqueue_style( 'jBox', Ninja_Forms::$url . 'assets/css/jBox.css' );
        wp_enqueue_style( 'nf-onboarding', Ninja_Forms::$url . 'assets/css/nfOnboarding.css' );
        wp_register_script( 'ninja_forms_admin_menu_welcome', Ninja_Forms::$url . 'assets/js/admin-welcome.js', array( 'jquery' ), FALSE, TRUE );
        wp_register_script( 'nf-onboarding', Ninja_Forms::$url . 'assets/js/lib/nfOnboarding.js', array('jquery', 'jBox'), FALSE, TRUE);
        wp_localize_script( 'nf-onboarding', 'nfOBi18n', Ninja_Forms::config('i18nOnboarding'));

        if ( get_option( 'ninja_forms_allow_tracking' ) && '1' == get_option( 'ninja_forms_allow_tracking' ) ) {
            $allow_tel = 1;
        } else {
            $allow_tel = 0;
        }
        $current_user = wp_get_current_user();
        wp_localize_script( 'ninja_forms_admin_menu_welcome', 'nfAdmin', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'dashboard_url' => admin_url( 'admin.php?page=ninja-forms' ),
            'batchNonce'   => wp_create_nonce( 'ninja_forms_batch_nonce' ),
            'i18n'          => array(
            ),
            'currentUserEmail'  => $current_user->user_email,
            'allow_telemetry' => $allow_tel,
            'showOptin'       => ( get_option( 'ninja_forms_do_not_allow_tracking' ) ||
                                   get_option( 'ninja_forms_allow_tracking' ) ) ? 0 : 1,
            'onboardingStep' => apply_filters( 'nf_onboarding_step_now', 0 ),
        ));
        wp_localize_script( 'ninja_forms_admin_menu_welcome', 'nfi18n', Ninja_Forms::config( 'i18nDashboard' ) );

        wp_enqueue_style( 'nf-font-awesome', Ninja_Forms::$url . 'assets/css/font-awesome.min.css' );
        wp_enqueue_script( 'ninja_forms_admin_menu_welcome' );
        wp_enqueue_script( 'nf-onboarding');

        Ninja_Forms::template( 'admin-menu-welcome.html.php' );
        Ninja_Forms::template( 'admin-onboarding.html.php');
    }

} // End Class NF_Admin_Menus_Welcome
