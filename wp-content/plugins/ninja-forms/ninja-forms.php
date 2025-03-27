<?php
/*
Plugin Name: Ninja Forms
Plugin URI: http://ninjaforms.com/?utm_source=WordPress&utm_medium=readme
Description: Ninja Forms is a webform builder with unparalleled ease of use and features.
Version: 3.9.2
Author: Saturday Drive
Author URI: http://ninjaforms.com/?utm_source=Ninja+Forms+Plugin&utm_medium=Plugins+WP+Dashboard
Text Domain: ninja-forms
Domain Path: /lang/

Copyright 2016 WP Ninjas.
*/
use NinjaForms\Includes\Admin\VersionCompatibilityCheck;
use NinjaForms\Includes\Admin\ManageUpdates;

require_once dirname( __FILE__ ) . '/lib/NF_Tracking.php';
require_once dirname( __FILE__ ) . '/includes/Integrations/sendwp.php';

// Services require PHP v5.6+
if( version_compare( PHP_VERSION, '5.6', '>=' ) ) {
  include_once dirname( __FILE__ ) . '/services/bootstrap.php';
}

function ninja_forms_three_table_exists(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'nf3_forms';
    return ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name );
}

/**
 * Auto-disable deprecated addons to avoid crashing the site.
 */
include_once 'lib/NF_AddonChecker.php';

/**
 * Class Ninja_Forms
 */
final class Ninja_Forms
{

    /**
     * @since 3.0
     */

    const VERSION = '3.9.2';

    /**
     * @since 3.4.0
     */
    const DB_VERSION = '1.4';

    const WP_MIN_VERSION = '6.4';

    /**
     * @var Ninja_Forms
     * @since 2.7
     */
    private static $instance;

    /**
     * Plugin Directory
     *
     * @since 3.0
     * @var string $dir
     */
    public static $dir = '';

    /**
     * Plugin Database Version
     *
     * This may be overwritten at a later point in this file.
     *
     * @since 3.4.0
     * @var string $db_version
     */
    public static $db_version = self::DB_VERSION;

    /**
     * Plugin URL
     *
     * @since 3.0
     * @var string $url
     */
    public static $url = '';

    /**
     * Admin Menus
     *
     * @since 3.0
     * @var array
     */
    public $menus = array();

    /**
     * AJAX Controllers
     *
     * @since 3.0
     * @var array
     */
    public $controllers = array();

    /**
     * Form Fields
     *
     * @since 3.0
     * @var array
     */
    public $fields = array();

    /**
     * Form Actions
     *
     * @since 3.0
     * @var array
     */
    public $actions = array();

    /**
     * Merge Tags
     *
     * @since 3.0
     * @var array
     */
    public $merge_tags = array();

    /**
     * Metaboxes
     *
     * @since 3.0
     * @var array
     */
    public $metaboxes = array();

    /**
     * Model Factory
     *
     * @var object
     */
    public $factory = '';

    /**
     * Logger
     *
     * @var string
     */
    protected $_logger = '';

    /**
     * Dispatcher
     *
     * @var NF_Dispatcher
     */
    protected $_dispatcher = '';

    /**
     * @var NF_Session
     */
    protected $session = '';

    /**
     * @var NF_Tracking
     */
    public $tracking;

    /**
     *
     * @var NF_Handlers_FieldsetRepeater
     */
    public $fieldsetRepeater;
    /**
     * Plugin Settings
     *
     * @since 3.0
     * @var array
     */
    protected $settings = array();

    protected $requests = array();

    protected $processes = array();

    public $routes;
    public $preview;
    public $shortcodes;
    public $add_form_modal;
    public $_eos;
    public $notices;
    public $widgets;
    public $submission_expiration_cron;
    
    /**
     * Main Ninja_Forms Instance
     *
     * Insures that only one instance of Ninja_Forms exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 2.7
     * @static
     * @staticvar array $instance
     * @return Ninja_Forms Highlander Instance
     */
    public static function instance()
    {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Ninja_Forms ) ) {
            self::$instance = new Ninja_Forms;

            self::$dir = plugin_dir_path( __FILE__ );

            self::$url = plugin_dir_url( __FILE__ );
            // @TODO: Remove this CONSTANT
            if( ! defined( 'NF_PLUGIN_URL' ) ){
                define( 'NF_PLUGIN_URL', self::$url );
            }

            $saved_version = get_option( 'ninja_forms_version' );
            // If we have a recorded version...
            // AND that version is less than our current version...
            if ( $saved_version && version_compare( $saved_version, self::VERSION, '<' ) ) {
                // *IMPORTANT: Filter to delete old bad data.
                // Leave this here until at least 3.4.0.
                if ( version_compare( $saved_version, '3.3.7', '<' ) && version_compare( $saved_version, '3.3.4', '>' ) ) {
                    delete_option( 'nf_sub_expiration' );
                }
                // We just upgraded the plugin.
                $plugin_upgrade = true;
            } else {
                $plugin_upgrade = false;
            }
            update_option( 'ninja_forms_version', self::VERSION );
            // If we've not recorded our db version...
            if ( ! get_option( 'ninja_forms_db_version' ) ) {
                // If this isn't a fresh install...
                // AND If we're upgrading from a version before 3.3.0...
                if ( $saved_version && version_compare( $saved_version, '3.3.0', '<' ) ) {
                    // Set it to the baseline (1.0) so that our upgrade process will run properly.
                    add_option( 'ninja_forms_db_version', '1.0', '', 'no' );
                } // OR If this isn't a fresh install...
                elseif ( $saved_version ) {
                    // Set it to the expected (1.1) so that our upgrade process will handle that.
                    add_option( 'ninja_forms_db_version', '1.1', '', 'no' );
                } // Otherwise... (This is a fresh install.)
                else {
                    // Set it to the current DB version.
                    add_option( 'ninja_forms_db_version', self::DB_VERSION, '', 'no' );
                    // Establish that our upgrades don't need to take place.
                    $required_updates = Ninja_Forms()->config( 'RequiredUpdates' );
                    $updated = array();
                    // Get a timestamp.
                    date_default_timezone_set( 'UTC' );
                    $now = date( "Y-m-d H:i:s" );
                    foreach( $required_updates as $slug => $update ) {
                        $updated[ $slug ] = $now;
                    }
                    update_option( 'ninja_forms_required_updates', $updated );
                }
            }
            // Set our static db version.
            self::$db_version = get_option( 'ninja_forms_db_version', self::$db_version );

            /*
                * Register our autoloader
                */
            spl_autoload_register( array( self::$instance, 'autoloader' ) );

            /*
                * Plugin Settings
                */
            self::$instance->settings = apply_filters( 'ninja_forms_settings', get_option( 'ninja_forms_settings' ) );

            /*
             * Admin Menus
             */
            self::$instance->menus[ 'forms' ]           = new NF_Admin_Menus_Forms();
            self::$instance->menus[ 'dashboard' ]       = new NF_Admin_Menus_Dashboard();
            self::$instance->menus[ 'add-new' ]         = new NF_Admin_Menus_AddNew();
            self::$instance->menus[ 'submissions']      = new NF_Admin_Menus_Submissions();
            self::$instance->menus[ 'import-export']    = new NF_Admin_Menus_ImportExport();
            self::$instance->menus[ 'settings' ]        = new NF_Admin_Menus_Settings();
            self::$instance->menus[ 'licenses']         = new NF_Admin_Menus_Licenses();
            self::$instance->menus[ 'system_status']    = new NF_Admin_Menus_SystemStatus();
            self::$instance->menus[ 'add-ons' ]         = new NF_Admin_Menus_Addons();
            self::$instance->menus[ 'divider']          = new NF_Admin_Menus_Divider();
            self::$instance->menus[ 'mock-data']        = new NF_Admin_Menus_MockData();
            self::$instance->menus[ 'welcome' ]         = new NF_Admin_Menus_Welcome();

            /*
                * AJAX Controllers
                */
            self::$instance->controllers[ 'form' ]          = new NF_AJAX_Controllers_Form();
            self::$instance->controllers[ 'fields' ]    = new NF_AJAX_Controllers_Fields();
            self::$instance->controllers[ 'batch_process' ] = new NF_AJAX_REST_BatchProcess();
            self::$instance->controllers[ 'required_updates' ] = new NF_AJAX_REST_RequiredUpdate();
            self::$instance->controllers[ 'preview' ]       = new NF_AJAX_Controllers_Preview();
            self::$instance->controllers[ 'submission' ]    = new NF_AJAX_Controllers_Submission();
            self::$instance->controllers[ 'savedfields' ]   = new NF_AJAX_Controllers_SavedFields();
            self::$instance->controllers[ 'deletealldata' ] = new NF_AJAX_Controllers_DeleteAllData();
            self::$instance->controllers[ 'jserror' ]       = new NF_AJAX_Controllers_JSError();
            self::$instance->controllers[ 'dispatchpoints' ] = new NF_AJAX_Controllers_DispatchPoints();
            self::$instance->controllers[ 'onboarding' ] = new NF_AJAX_Controllers_Onboarding();

            /*
                * REST Controllers
                */
            self::$instance->controllers[ 'REST' ][ 'forms' ] = new NF_AJAX_REST_Forms();
            self::$instance->controllers[ 'REST' ][ 'new-form-templates' ] = new NF_AJAX_REST_NewFormTemplates();

            /*
            *   API Routes
            */
            self::$instance->routes[ 'submissions' ] = new NF_Routes_Submissions();
            self::$instance->routes[ 'telemetry' ] = new NF_Routes_Telemetry();

            /*
                * Async Requests
                */
            require_once Ninja_Forms::$dir . 'includes/Libraries/BackgroundProcessing/classes/wp-async-request.php';

            /*
                * Background Processes
                */
            require_once Ninja_Forms::$dir . 'includes/Libraries/BackgroundProcessing/wp-background-processing.php';
            self::$instance->requests[ 'update-fields' ] = new NF_AJAX_Processes_UpdateFields();


            /*
                * WP-CLI Commands
                */
            if( class_exists( 'WP_CLI_Command' ) ) {
                WP_CLI::add_command('ninja-forms', 'NF_WPCLI_NinjaFormsCommand');
            }

            /*
                * Preview Page
                */
            self::$instance->preview = new NF_Display_Preview();

            /**
             * Admin Footer Text
             */
            new NF_Admin_FooterMessage();

            /*
                * Public Form Link
                */
            add_filter('template_include', array(self::$instance, 'maybe_load_public_form'));

            /*
                * Shortcodes
                */
            self::$instance->shortcodes = new NF_Display_Shortcodes();

            /*
                * Submission CPT
                */
            new NF_Admin_CPT_Submission();
            new NF_Admin_CPT_DownloadAllSubmissions();
            require_once Ninja_Forms::$dir . 'lib/StepProcessing/menu.php';

            /**
             * Blocks
             */

            require_once Ninja_Forms::$dir . 'blocks/ninja-forms-blocks.php';


            /*
                * User data requests ( GDPR actions )
                */
            new NF_Admin_UserDataRequests();

            /*
                * Logger
                */
            self::$instance->_logger = new NF_Database_Logger();

            /*
                * Dispatcher
                */
            self::$instance->_dispatcher = new NF_Dispatcher();


            /*
                * Add Form Modal
                */
            self::$instance->add_form_modal = new NF_Admin_AddFormModal();

            /*
                * EOS Parser
                */
            self::$instance->_eos[ 'parser' ] = require_once 'includes/Libraries/EOS/Parser.php';

            /*
                * Admin Notices System
                */
            self::$instance->notices = new NF_Admin_Notices();

            (new VersionCompatibilityCheck())->activate();

            self::$instance->widgets[] = new NF_Widget();

            /*
                * Opt-In Tracking
                */
            self::$instance->tracking = new NF_Tracking();

            /*
                * Fieldset Repeater Handler
                */
            self::$instance->fieldsetRepeater =  new NF_Handlers_FieldsetRepeater();

            self::$instance->submission_expiration_cron = new NF_Database_SubmissionExpirationCron();

            /*
                * Activation Hook
                * TODO: Move to a permanent home.
                */
            register_activation_hook( __FILE__, array( self::$instance, 'activation' ) );



            /*
                * Require EDD auto-update file
                */
            if( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
                // Load our custom updater if it doesn't already exist
                require_once( self::$dir . 'includes/Integrations/EDD/EDD_SL_Plugin_Updater.php');
            }
            require_once self::$dir . 'includes/Integrations/EDD/class-extension-updater.php';

            // If Ninja Forms was just upgraded...
            if ( $plugin_upgrade ) {
                // Ensure all of our tables have been defined.
                $migrations = new NF_Database_Migrations();
                $migrations->migrate();

                add_action( 'init', array( self::$instance, 'flush_rewrite_rules' ) );
                // Enable "Dev Mode" for existing installations.
                $settings = Ninja_Forms()->get_settings();
                if( ! isset($settings['builder_dev_mode'])){
                    Ninja_Forms()->update_setting('builder_dev_mode', 1);
                }
            }
        }

        add_action( 'admin_notices', array( self::$instance, 'admin_notices' ) );

        add_action( 'plugins_loaded', array( self::$instance, 'plugins_loaded' ) );

        add_action( 'ninja_forms_available_actions', array( self::$instance, 'scrub_available_actions' ) );

        add_action( 'init', array( self::$instance, 'instantiateTranslatableObjects' ), 5 );
        add_action( 'init', array( self::$instance, 'init' ), 5 );
        add_action( 'admin_init', array( self::$instance, 'admin_init' ), 5 );
        add_action( 'admin_enqueue_scripts', function() {
            if(apply_filters('ninja_forms_current_user_is_onboarding', 0)) {
                wp_register_script( 'nf-heartbeat', self::$url . 'assets/js/admin-heartbeat.js', array('jquery') );
                wp_enqueue_script('nf-heartbeat');
            }
        });

        add_action( 'nf_weekly_promotion_update', array( self::$instance, 'nf_run_promotion_manager' ) );
        add_action( 'activated_plugin', array( self::$instance, 'nf_bust_promotion_cache_on_plugin_activation' ), 10, 2 );

        // Checks php version and..
        if (version_compare(PHP_VERSION, '7.2.0', '<')) {
            // Pulls in the whip notice if the user is.
            add_action( 'admin_init', array( self::$instance, 'nf_php_version_whip_notice' ) );
        }

        add_action( 'admin_init', array( self::$instance, 'nf_do_telemetry' ) );
        add_action( 'admin_init', array( self::$instance, 'nf_plugin_add_suggested_privacy_content' ), 20 );

        return self::$instance;
    }

    public function init()
    {
        do_action( 'nf_init', self::$instance );
        $this->register_rewrite_rules();
    }

    public function flush_rewrite_rules()
    {
        $this->register_rewrite_rules();
        flush_rewrite_rules();
    }

    public function instantiateTranslatableObjects(): void
    {        
        new NF_Admin_Metaboxes_Calculations();

        /*
            * Merge Tags
            */
        self::$instance->merge_tags['wp'] = new NF_MergeTags_WP();
        self::$instance->merge_tags['fields'] = new NF_MergeTags_Fields();
        self::$instance->merge_tags['calcs'] = new NF_MergeTags_Calcs();
        self::$instance->merge_tags['form'] = new NF_MergeTags_Form();
        self::$instance->merge_tags['other'] = new NF_MergeTags_Other();
        self::$instance->merge_tags['deprecated'] = new NF_MergeTags_Deprecated();

        self::$instance->metaboxes['append-form'] = new NF_Admin_Metaboxes_AppendAForm();


        /*
            * Field Class Registration
            */
        self::$instance->fields = apply_filters('ninja_forms_register_fields', self::load_classes('Fields'));

        if (! apply_filters('ninja_forms_enable_credit_card_fields', false)) {
            unset(self::$instance->fields['creditcard']);
            unset(self::$instance->fields['creditcardcvc']);
            unset(self::$instance->fields['creditcardexpiration']);
            unset(self::$instance->fields['creditcardfullname']);
            unset(self::$instance->fields['creditcardnumber']);
            unset(self::$instance->fields['creditcardzip']);
        }
    }
    
    public function register_rewrite_rules()
    {
        add_rewrite_tag('%nf_public_link%', '([a-zA-Z0-9]+)');
        add_rewrite_rule('^ninja-forms/([a-zA-Z0-9]+)/?', 'index.php?nf_public_link=$matches[1]', 'top');
    }

    public function admin_init()
    {
        do_action( 'nf_admin_init', self::$instance );
        if ( isset ( $_GET[ 'nf-upgrade' ] ) && 'complete' == $_GET[ 'nf-upgrade' ] ) {
            Ninja_Forms()->dispatcher()->send( 'upgrade' );
        }

        add_filter( 'ninja_forms_dashboard_menu_items', array( $this, 'maybe_hide_dashboard_items' ) );

        // Remove already completed updates from our filtered list of required updates.
        add_filter( 'ninja_forms_required_updates', array( $this, 'remove_completed_updates' ), 99 );
        add_filter( 'ninja_forms_required_updates', array( $this, 'remove_bad_updates' ), 99 );

        // Sets up a weekly cron to run the promotion manager.
        if ( ! wp_next_scheduled( 'nf_weekly_promotion_update' ) ) {
            wp_schedule_event( current_time( 'timestamp' ), 'nf-weekly', 'nf_weekly_promotion_update' );
        }


        // Get our list of required updates.
        $required_updates = Ninja_Forms()->config( 'RequiredUpdates' );
        global $wpdb;
        $sql = "SELECT COUNT( `id` ) AS total FROM `{$wpdb->prefix}nf3_forms`;";
        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        $threshold = 0; // Threshold percentage for our required updates.
        if ( get_transient( 'ninja_forms_prevent_updates' ) ) {
            update_option( 'ninja_forms_needs_updates', 0 );
        }
        // If we got back a list of updates...
        // AND If we have any forms on the site...
        // AND If the gate is open...
        // To avoid errors on older upgrades, ignore the gatekeeper if the db version is the baseline (1.0)...
        elseif ( ! empty( $required_updates )
            && 0 < $result[ 0 ][ 'total' ]
            &&  ( WPN_Helper::gated_release( $threshold )
            || '1.0' == self::$db_version ) ) {
            // Record that we have updates to run.
            update_option( 'ninja_forms_needs_updates', 1 );
        } // Otherwise... (Sanity check)
        else {
            // Record that there are no required updates.
            update_option( 'ninja_forms_needs_updates', 0 );
        }

        //Enqueue forms scripts for WP bakery frontend editor
        if(isset($_GET['vc_action']) && $_GET['vc_action'] === "vc_inline"){
            $forms_list = Ninja_Forms()->form()->get_forms();
            if ( ! empty( $forms_list ) ) {
                foreach ( $forms_list as $form ) {
                    NF_Display_Render::enqueue_scripts($form->get_id());
                }
            }
        }

    }

    function maybe_load_public_form($template) {
        if($public_link_key = sanitize_text_field(get_query_var('nf_public_link'))){
            // @TODO Move this functionality behind a boundry.
            global $wpdb;
            $query = $wpdb->prepare( "SELECT `parent_id` FROM {$wpdb->prefix}nf3_form_meta WHERE `key` = 'public_link_key' AND `value` = %s", $public_link_key );
            $results = $wpdb->get_col($query);
            $form_id = reset($results);

            $page_template = get_page_template();
            if( ! empty( $page_template ) )
                $template = $page_template;

            new NF_Display_PagePublicLink($form_id);
        }
        return $template;
    }

    /**
     * Privacy policy suggested content for Ninja Forms
     */
    function nf_plugin_add_suggested_privacy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) return;
        $content = $this->plugin_get_default_privacy_content();
        wp_add_privacy_policy_content(
            __( 'Ninja Forms' ),
            wp_kses_post( wpautop( $content, false) ) );

    }


    /**
     * Return the default suggested privacy policy content.
     *
     * @return string The default policy content.
     */
    function plugin_get_default_privacy_content() {
        return
            '<h2>' . esc_html__( 'Ninja Forms allows you to collect personal information', 'ninja-forms' ) . '</h2>' .
            '<p>' . esc_html__( 'If you are using Ninja Forms to collect personal information, you should consult a legal professional for your use case.', 'ninja-forms' ) . '</p>';
    }

    /**
     * NF PHP Version Whip Notice
     * If the user is on a version below PHP 7.2 then we get an instance of the
     * NF PHP Version Whip class which will add a non-dismissible admin notice.
     *
     * @return NF_Php_Version_Whip
     */
    public function nf_php_version_whip_notice()
    {
        require_once self::$dir . '/includes/Libraries/Whip/NF_Php_Version_Whip.php';
        return new NF_Php_Version_Whip();
    }

    /**
     * Function to launch our various telemetry calls on admin_init.
     */
    public function nf_do_telemetry() {
        if ( ! has_filter( 'ninja_forms_settings_licenses_addons' ) && ( ! Ninja_Forms()->tracking->is_opted_in() || Ninja_Forms()->tracking->is_opted_out() ) ) {
            return false;
        }
        global $wpdb;
        // If we've not already sent table collation...
        if ( ! get_option( 'nf_tel_collate' ) ) {
            $collate = array();
            // Get the collation of the wp_options table.
            $sql = "SHOW FULL COLUMNS FROM `" . $wpdb->prefix . "options` WHERE Field = 'option_value'";
            $result = $wpdb->get_results( $sql, 'ARRAY_A' );
            $collate[ 'cache' ] = $result[ 0 ][ 'Collation' ];
            // Get the collation of the nf3_forms table.
            $sql = "SHOW FULL COLUMNS FROM `" . $wpdb->prefix . "nf3_forms` WHERE Field = 'title'";
            $result = $wpdb->get_results( $sql, 'ARRAY_A' );
            $collate[ 'forms' ] = $result[ 0 ][ 'Collation' ];
            // Send our data to api.ninjaforms.com.
            Ninja_Forms()->dispatcher()->send( 'table_collate', $collate );
            // Record an option so that we don't run this again.
            add_option( 'nf_tel_collate', '1', '', 'no' );
        }
    }

    public function maybe_hide_dashboard_items( $items )
    {
        $disable_marketing = false;

        if ( apply_filters( 'ninja_forms_disable_marketing', $disable_marketing ) ) {
            unset(
                $items[ 'apps' ],
                $items[ 'memberships' ],
                $items[ 'services' ],
                $items[ 'user_access' ]
            );
        }

        if ( 1 == get_option( 'ninja_forms_needs_updates' ) ) {
            unset(
                $items[ 'widgets' ],
                $items[ 'apps' ],
                $items[ 'memberships' ],
                $items[ 'services' ],
                $items[ 'user_access' ]
            );
        }

        $onboarding_step = apply_filters( 'nf_onboarding_step_now', 0 );
        if ( 1 === $onboarding_step || 2 === $onboarding_step ) {
            unset(
                $items[ 'apps' ],
                $items[ 'memberships' ],
                $items[ 'services' ],
                $items[ 'user_access' ]
            );
        }

        return $items;
    }

    public function scrub_available_actions( $actions )
    {
        foreach( $actions as $key => $action ){
            if ( ! is_plugin_active( $action[ 'plugin_path' ] ) )  continue;
            unset( $actions[ $key ] );
        }
        return $actions;
    }

    /**
     * Call back function for the promo manager cron.
     * Grabs a fresh copy of the promotions and stores them in an option.
     *
     * @return void
     */
    public function nf_run_promotion_manager()
    {
        $promotion_manager = new NF_PromotionManager();
        $promomotions = json_encode( $promotion_manager->get_promotions() );
        update_option( 'nf_active_promotions', $promomotions, false );
    }

    /**
     * Listens for plugin activation and runs check to see if any
     * promotions need to be added or removed.
     *
     * @return void
     */
    public function nf_bust_promotion_cache_on_plugin_activation( $plugin, $network_activation )
    {
        $addons_with_promotions = $this->get_promotion_addons_lookup_table();
        $plugin = explode( '/', $plugin );
        $this->nf_maybe_bust_promotion_cache( $addons_with_promotions, $plugin[ 0 ] );
    }

    /**
     * Build a look up table for the add-ons that have promotions.
     * TODO: maybe come up with a better name for this class.
     *
     * @return array of promotions.
     */
    public function get_promotion_addons_lookup_table()
    {
        // @TODO: Maybe use ninja_forms_addons_feed option to populate this later?
        $nf_promotion_addons = array(
            'ninja-forms-conditional-logic', // Account for development environments.
            'ninja-forms-conditionals',
            'ninja-forms-uploads',
            'ninja-forms-multi-part',
            'ninja-forms-layout-styles', // Account for development environments.
            'ninja-forms-style',
            'ninja-mail', // Account for Ninja Mail as legacy for SendWP.
            'sendwp'
        );
        return $nf_promotion_addons;
    }

    /**
     * Loops over our add-ons that have promotions and
     * runs the promotion manager class.
     *
     * @return void
     */
    public function nf_maybe_bust_promotion_cache( $promo_addons, $plugin_being_activated )
    {
        if ( in_array( $plugin_being_activated, $promo_addons ) ) {
            $this->nf_run_promotion_manager();
        }

    }

    public function admin_notices()
    {
        // Notices filter and run the notices function.
        $admin_notices = Ninja_Forms()->config( 'AdminNotices' );
        self::$instance->notices->admin_notice( apply_filters( 'nf_admin_notices', $admin_notices ) );
    }

    public function plugins_loaded()
    {
        unload_textdomain('ninja-forms');
        load_plugin_textdomain( 'ninja-forms', false, basename( dirname( __FILE__ ) ) . '/lang' );



        /*
            * Form Action Registration
            */
        $actions = self::load_classes( 'Actions' ) ;
        uksort( $actions, [ $this, 'sort_actions' ] );
        self::$instance->actions = apply_filters( 'ninja_forms_register_actions', $actions );

        /*
            * Merge Tag Registration
            */
        self::$instance->merge_tags = apply_filters( 'ninja_forms_register_merge_tags', self::$instance->merge_tags );

        /*
            * It's Ninja Time: Hook for Extensions
            */
        do_action( 'ninja_forms_loaded' );

    }

    /**
     * Autoloader
     *
     * Autoload Ninja Forms classes
     *
     * @param $class_name
     */
    public function autoloader( $class_name )
    {
        if( class_exists( $class_name ) ) return;

        /* Ninja Forms Prefix */
        if (false !== strpos($class_name, 'NF_')) {
            $class_name = str_replace('NF_', '', $class_name);
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }

        /* WP Ninjas Prefix */
        if (false !== strpos($class_name, 'WPN_')) {
            $class_name = str_replace('WPN_', '', $class_name);
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }
    }

    /*
        * PUBLIC API WRAPPERS
        */

    /**
     * Form Model Factory Wrapper
     *
     * @param $id
     * @return NF_Abstracts_ModelFactory
     */
    public function form( $id = '' )
    {
        global $wpdb;

        static $forms;
        if ( isset ( $forms[ $id ] ) ) {
            return $forms[ $id ];
        }

        $forms[ $id ] = new NF_Abstracts_ModelFactory( $wpdb, $id );
        return $forms[ $id ];
    }

    /**
     * Logger Class Wrapper
     *
     * Example Use:
     * Ninja_Forms()->logger()->log( 'debug', "Hello, {name}!", array( 'name' => 'world' ) );
     * Ninja_Forms()->logger()->debug( "Hello, {name}!", array( 'name' => 'world' ) );
     *
     * @return string
     */
    public function logger()
    {
        return $this->_logger;
    }

    /**
     * Return dispatcher
     *
     * @return NF_Dispatcher
     */
    public function dispatcher()
    {
        return $this->_dispatcher;
    }

    public function eos()
    {
        return new NF_EOS_Parser();
    }

    public function session()
    {
        if( ! $this->session ){
            $this->session = new NF_Session();
            $this->session->init();
        }
        return $this->session;
    }

    public function request( $action )
    {
        if( ! isset( $this->requests[ $action ] ) ) return new NF_AJAX_Requests_NullRequest();

        return $this->requests[ $action ];
    }

    public function background_process( $action )
    {
        if( ! isset( $this->requests[ $action ] ) ) return new NF_AJAX_Processes_NullProcess();

        return $this->requests[ $action ];
    }

    /**
     * Get a setting
     *
     * @param string     $key
     * @param bool|false $default
     * @return bool
     */
    public function get_setting( $key = '', $default = false )
    {
        if( empty( $key ) || ! isset( $this->settings[ $key ] ) || empty( $this->settings[ $key ] ) ) return $default;

        return $this->settings[ $key ];
    }

    /**
     * Get all the settings
     *
     * @return array
     */
    public function get_settings()
    {
        return ( is_array( $this->settings ) ) ? $this->settings : array();
    }

    /**
     * Update a setting
     *
     * @param string           $key
     * @param mixed           $value
     * @param bool|false $defer_update Defer the database update of all settings
     */
    public function update_setting( $key, $value, $defer_update = false )
    {
        $this->settings[ $key ] = $value;
        if ( ! $defer_update ) {
            $this->update_settings();
        }
    }

    /**
     * Save settings to database
     *
     * @param array $settings
     */
    public function update_settings( $settings = array() )
    {
        if( ! is_array( $this->settings ) ) $this->settings = array();

        if( $settings && is_array( $settings ) ) {
            $this->settings = array_merge($this->settings, $settings);
        }

        update_option( 'ninja_forms_settings', $this->settings );
    }


    /**
     * Display Wrapper
     *
     * @param $form_id
     */
    public function display( $form_id, $preview = FALSE )
    {
        if( ! $form_id ) return;

        $noscript_message = esc_html__( 'Notice: JavaScript is required for this content.', 'ninja-forms' );
        $noscript_message = apply_filters( 'ninja_forms_noscript_message', $noscript_message );

        Ninja_Forms()->template( 'display-noscript-message.html.php', array( 'message' => $noscript_message ) );

        //Detect Page builder editor
        $visual_composer_screen = isset( $_GET['vcv-ajax'] );
        $elementor_screen = isset($_GET['elementor-preview']) || (isset($_GET['action']) && $_GET['action'] === 'elementor') || (isset($_POST['action']) && $_POST['action'] === 'elementor_ajax');
        //Set a list of conditions that would lead to loading the iFrame
        $set_load_iframe_condition = $visual_composer_screen || $elementor_screen;
        //Filter the current result of the conditions
        $load_iframe = apply_filters("ninja_forms_display_iframe",  $set_load_iframe_condition, $form_id);

        //Detect cases when page needs to refresh and load the iFrame onlmy on that first load
        $wp_bakery_frontend_first_display = isset( $_POST['action'] ) && $_POST['action'] === "vc_load_shortcode";
        //Set list of condition that would lead to refresh page needed
        $set_refresh_page_needed_condition = $wp_bakery_frontend_first_display;
        //Filter the current result of the conditions
        $refresh_page_needed = apply_filters("ninja_forms_display_reload_page_message", $set_refresh_page_needed_condition, $form_id);
        
        if( $load_iframe  ) {
            NF_Display_Render::localize_iframe($form_id);
        } else if( $refresh_page_needed ) {
            NF_Display_Render::localize_iframe($form_id);
        } else {
            if( ! $preview ) {
                NF_Display_Render::localize($form_id);
            } else {
                NF_Display_Render::localize_preview($form_id);
            }
        }
    }

    /*
        * PRIVATE METHODS
        */

    private function sort_actions( $a, $b )
    {
        // Create a numeric lookup by flipping the non-associative array.
        $custom_order = array_flip( $this->config( 'ActionTypeOrder' ) );

        $a_order = ( isset( $custom_order[ $a ] ) ) ? $custom_order[ $a ] : 9001;
        $b_order = ( isset( $custom_order[ $b ] ) ) ? $custom_order[ $b ] : 9001;

        return intval( $a_order >= $b_order );
    }

    /**
     * Load Classes from Directory
     *
     * @param string $prefix
     * @return array
     */
    private static function load_classes( $prefix = '' )
    {
        $return = array();

        $subdirectory = str_replace( '_', DIRECTORY_SEPARATOR, str_replace( 'NF_', '', $prefix ) );

        $directory = 'includes/' . $subdirectory;

        foreach (scandir( self::$dir . $directory ) as $path) {

            $path = explode( DIRECTORY_SEPARATOR, str_replace( self::$dir, '', $path ) );
            $filename = str_replace( '.php', '', end( $path ) );

            $class_name = 'NF_' . $prefix . '_' . $filename;

            if( ! class_exists( $class_name ) ) continue;

            $return[ strtolower( $filename ) ] = new $class_name;
        }

        return $return;
    }



    /*
        * STATIC METHODS
        */

    /**
     * Template
     *
     * @param string $file_name
     * @param array $data
     */
    public static function template( $file_name = '', array $data = array(), $return = FALSE )
    {
        if( ! $file_name ) return FALSE;

        extract( $data );

        $path = self::$dir . 'includes/Templates/' . $file_name;

        if( ! file_exists( $path ) ) return FALSE;

        if( $return ) return file_get_contents( $path );

        include $path;
    }

    /**
     * Config
     *
     * @param $file_name
     * @return mixed
     */
    public static function config( $file_name )
    {
        return include self::$dir . 'includes/Config/' . $file_name . '.php';
    }

    /**
     * Activation
     */
    public function activation() {

        $migrations = new NF_Database_Migrations();
        $migrations->migrate();

        if( Ninja_Forms()->form()->get_forms() ) return;

        // Go ahead and create our randomn number for gated releases in the future
        $zuul = WPN_Helper::get_zuul();

        $form = Ninja_Forms::template( 'formtemplate-contactform.nff', array(), TRUE );
        Ninja_Forms()->form()->import_form( $form );

        Ninja_Forms()->flush_rewrite_rules();

        // Enable "Light" Opinionated Styles for new installtion.
        // Ninja_Forms()->update_setting('opinionated_styles', 'light'); //issue 7271

        // Disable "Dev Mode" for new installation.
        Ninja_Forms()->update_setting('builder_dev_mode', 0);

        // Grab our initial add-on feed from api.ninjaforms.com
        nf_update_marketing_feed();

        // Setup our add-on feed wp cron so that our add-on list is up to date on a weekly basis.
        nf_marketing_feed_cron_job();

        // Disable the survey promo for 7 days on new installations.
        set_transient('ninja_forms_disable_survey_promo', 1, DAY_IN_SECONDS * 7);
    }

    /**
     * Deprecated Notice
     *
     * Example: Ninja_Forms::deprecated_hook( 'ninja_forms_old', '3.0', 'ninja_forms_new', debug_backtrace() );
     *
     * @param $deprecated
     * @param $version
     * @param null $replacement
     * @param null $backtrace
     */
    public static function deprecated_notice( $deprecated, $version, $replacement = null, $backtrace = null )
    {
        do_action( 'ninja_forms_deprecated_call', $deprecated, $replacement, $version );

        $show_errors = current_user_can( 'manage_options' );

        // Allow plugin to filter the output error trigger
        if ( WP_DEBUG && apply_filters( 'ninja_forms_deprecated_function_trigger_error', $show_errors ) ) {
            if ( ! is_null( $replacement ) ) {
                trigger_error( sprintf( esc_html__( '%1$s is <strong>deprecated</strong> since Ninja Forms version %2$s! Use %3$s instead.', 'ninja-forms' ), $deprecated, $version, $replacement ) );
                // trigger_error(  print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
                // Alternatively we could dump this to a file.
            } else {
                trigger_error( sprintf( esc_html__( '%1$s is <strong>deprecated</strong> since Ninja Forms version %2$s.', 'ninja-forms' ), $deprecated, $version ) );
                // trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
                // Alternatively we could dump this to a file.
            }
        }
    }

    /**
     * Function to deregister already completed updates from the list of required updates.
     *
     * @since 3.3.14
     *
     * @codeCoverageIgnore WP hook only; tests in called class
     * 
     * @param $updates (Array) Our array of required updates.
     * @return $updates (Array) Our array of required updates.
     */
    public function remove_completed_updates($updates)
    {
        $manageUpdates = new ManageUpdates();

        $return = $manageUpdates->removeCompletedUpdates($updates);

        return $return;
    }

    /**
     * Function to deregister updates that have required updates that either
     * don't exist, or are malformed
     *
     * @since UPDATE_TO_LATEST version
     *
     * @codeCoverageIgnore WP hook only; tests in called class
     * 
     * @param $updates (Array) Our array of required updates.
     * @return $updates (Array) Our array of required updates.
     */
    public function remove_bad_updates( $updates ) {

        $manageUpdates = new ManageUpdates();

        $return = $manageUpdates->removeBadUpdates($updates);

        return $return;
    }

} // End Class Ninja_Forms



/**
 * The main function responsible for returning The Highlander Ninja_Forms
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $nf = Ninja_Forms(); ?>
 *
 * @since 2.7
 * @return Ninja_Forms Highlander Instance
 */
function Ninja_Forms()
{
    return Ninja_Forms::instance();
}

Ninja_Forms();

/*
|--------------------------------------------------------------------------
| Uninstall Hook
|--------------------------------------------------------------------------
*/

register_uninstall_hook( __FILE__, 'ninja_forms_uninstall' );

function ninja_forms_uninstall(){

    if( Ninja_Forms()->get_setting( 'delete_on_uninstall' ) ) {
        require_once plugin_dir_path(__FILE__) . '/includes/Database/Migrations.php';
        $migrations = new NF_Database_Migrations();
        $migrations->nuke(TRUE, TRUE);
        $migrations->nuke_settings(TRUE, TRUE);
        $migrations->nuke_deprecated(TRUE, TRUE);
    }
}

// Scheduled Action Hook
function nf_optin_update_environment_vars() {
    /**
     * Send updated environment variables.
     */
    Ninja_Forms()->dispatcher()->sendTelemetryData();

    /**
     * Make sure that we've reported our opt-in.
     */
    if( get_option( 'ninja_forms_optin_reported', 0 ) ) return;

    Ninja_Forms()->dispatcher()->send( 'optin', array( 'send_email' => 1 ) );
    // Debounce opt-in dispatch.
    update_option( 'ninja_forms_optin_reported', 1 );
}
add_action( 'nf_optin_cron', 'nf_optin_update_environment_vars' );

/**
 * Function to register our Custom Cron Recurrences.
 *
 * @param $schedules (Array) The available cron recurrences.
 * @return (Array) The filtered cron recurrences.
 *
 */
function nf_custom_cron_job_recurrence( $schedules ) {
    $schedules[ 'nf-monthly' ] = array(
        'display' => esc_html__( 'Once per month', 'ninja-forms' ),
        'interval' => 2678400,
    );
    $schedules[ 'nf-weekly' ] = array(
        'display' => esc_html__( 'Once per week', 'ninja-forms' ),
        'interval' => 604800,
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'nf_custom_cron_job_recurrence' );

// Schedule Cron Job Event
function nf_optin_send_admin_email_cron_job() {
    if ( ! wp_next_scheduled( 'nf_optin_cron' ) ) {
        wp_schedule_event( current_time( 'timestamp' ), 'nf-monthly', 'nf_optin_cron' );
    }
}
add_action( 'wp', 'nf_optin_send_admin_email_cron_job' );

/**
 * Function called via weekly wp_cron to update our marketing feeds.
 *
 * @since 3.3.17
 */
function nf_update_marketing_feed() {
    // Fetch our addon data.
    $data = wp_remote_get( 'http://api.ninjaforms.com/feeds/?fetch=addons' );
    // If we got a valid response...
    if ( is_array($data) && 200 == $data[ 'response' ][ 'code' ] ) {
        // Save the data to our option.
        $data = wp_remote_retrieve_body( $data );
        update_option( 'ninja_forms_addons_feed', $data, false );
    }
}
add_action( 'nf_marketing_feed_cron', 'nf_update_marketing_feed' );

/**
 * Function called by our marketing feed cron.
 *
 * @since 3.3.17
 */
function nf_marketing_feed_cron_job() {
    if ( ! wp_next_scheduled( 'nf_marketing_feed_cron' ) ) {
        wp_schedule_event( current_time( 'timestamp' ), 'nf-weekly', 'nf_marketing_feed_cron' );
    }
}

/**
 * Make sure the marketing feed is updated after an update
 *
 * @since 3.8.1
 */
add_action("upgrader_process_complete", function($upgrader_object, $options){
    if(
        $options["type"] === "plugin" && 
        $options["action"] === "update" && 
        $upgrader_object->result["destination_name"] === "ninja-forms" &&
        function_exists("nf_update_marketing_feed")
    ){
        nf_update_marketing_feed();
    }
}, 10, 2);


/**
 * Call our survey promo on relevant pages.
 */
add_action( 'in_admin_header', function() {
    $surveyPromo = new NF_Admin_SurveyPromo();
    $surveyPromo->show();
});