<?php

class Ga_Admin {

    const GA_WEB_PROPERTY_ID_OPTION_NAME                = 'googleanalytics_web_property_id';
    const GA_EXCLUDE_ROLES_OPTION_NAME                  = 'googleanalytics_exclude_roles';
    const GA_SHARETHIS_TERMS_OPTION_NAME                = 'googleanalytics_sharethis_terms';
    const GA_HIDE_TERMS_OPTION_NAME                     = 'googleanalytics_hide_terms';
    const GA_VERSION_OPTION_NAME                        = 'googleanalytics_version';
    const GA_SELECTED_ACCOUNT                           = 'googleanalytics_selected_account';
    const GA_OAUTH_AUTH_CODE_OPTION_NAME                = 'googleanalytics_oauth_auth_code';
    const GA_OAUTH_AUTH_TOKEN_OPTION_NAME               = 'googleanalytics_oauth_auth_token';
    const GA_ACCOUNT_DATA_OPTION_NAME                   = 'googleanalytics_account_data';
    const GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME       = 'googleanalytics_web_property_id_manually';
    const GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME = 'googleanalytics_web_property_id_manually_value';
    const GA_SHARETHIS_PROPERTY_ID                      = 'googleanalytics_sherethis_property_id';
    const GA_SHARETHIS_PROPERTY_SECRET                  = 'googleanalytics_sherethis_property_secret';
    const GA_SHARETHIS_VERIFICATION_RESULT              = 'googleanalytics_sherethis_verification_result';
    const MIN_WP_VERSION                                = '3.8';
    const GA_SHARETHIS_API_ALIAS                        = 'sharethis';
    const GA_DISABLE_ALL_FEATURES                       = 'googleanalytics_disable_all_features';
    const GA_HEARTBEAT_API_CACHE_UPDATE                 = false;
    const NOTICE_SUCCESS								= 'success';
    const NOTICE_WARNING								= 'warning';
    const NOTICE_ERROR								    = 'error';

    /**
     * Instantiate API client.
     *
     * @return Ga_Lib_Google_Api_Client|null
     */
    public static function api_client( $type = '' ) {
        if ( self::GA_SHARETHIS_API_ALIAS === $type ) {
            $instance = Ga_Lib_Sharethis_Api_Client::get_instance();
        } else {
            $instance = Ga_Lib_Google_Api_Client::get_instance();
            $token    = Ga_Helper::get_option( self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME );
            try {
                if ( ! empty( $token ) ) {
                    $token = json_decode( $token, true );
                    $instance->set_access_token( $token );
                }
            } catch ( Exception $e ) {
                Ga_Helper::ga_oauth_notice( $e->getMessage() );
            }
        }

        return $instance;
    }

    /**
     * Initializes plugin's options during plugin activation process.
     */
    public static function activate_googleanalytics() {
        add_option( self::GA_WEB_PROPERTY_ID_OPTION_NAME, Ga_Helper::GA_DEFAULT_WEB_ID );
        add_option( self::GA_EXCLUDE_ROLES_OPTION_NAME, wp_json_encode( array() ) );
        add_option( self::GA_VERSION_OPTION_NAME );
        add_option( self::GA_OAUTH_AUTH_CODE_OPTION_NAME );
        add_option( self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME );
        add_option( self::GA_ACCOUNT_DATA_OPTION_NAME );
        add_option( self::GA_SELECTED_ACCOUNT );
        add_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME );
        add_option( self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME );
        add_option( self::GA_DISABLE_ALL_FEATURES );
        Ga_Cache::add_cache_options();
    }

    /**
     * Deletes plugin's options during plugin activation process.
     */
    public static function deactivate_googleanalytics() {
        delete_option( self::GA_WEB_PROPERTY_ID_OPTION_NAME );
        delete_option( self::GA_EXCLUDE_ROLES_OPTION_NAME );
        delete_option( self::GA_OAUTH_AUTH_CODE_OPTION_NAME );
        delete_option( self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME );
        delete_option( self::GA_ACCOUNT_DATA_OPTION_NAME );
        delete_option( self::GA_SELECTED_ACCOUNT );
        delete_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME );
        delete_option( self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME );
        delete_option( self::GA_DISABLE_ALL_FEATURES );
        delete_option( Ga_SupportLogger::LOG_OPTION );
        delete_option('googleanalytics_gdpr_config');
        delete_option('googleanalytics_demographic');
        delete_option('googleanalytics_demo_data');
	    delete_option('googleanalytics_demo_date');
        delete_option('googleanalytics_send_data');
        delete_option('googleanalytics_hide_terms');
        delete_option('googleanalytics_sharethis_terms');
        delete_option('googleanalytics_sherethis_property_id');
        delete_option('googleanalytics_sherethis_property_secret');
        delete_option(self::GA_SHARETHIS_TERMS_OPTION_NAME );
        Ga_Cache::delete_cache_options();
    }

    /**
     * Deletes plugin's options during plugin uninstallation process.
     */
    public static function uninstall_googleanalytics() {
        delete_option( self::GA_SHARETHIS_TERMS_OPTION_NAME );
        delete_option( self::GA_HIDE_TERMS_OPTION_NAME );
        delete_option( self::GA_VERSION_OPTION_NAME );
        delete_option( self::GA_SHARETHIS_PROPERTY_ID );
        delete_option( self::GA_SHARETHIS_PROPERTY_SECRET );
        delete_option( self::GA_SHARETHIS_VERIFICATION_RESULT );
    }

    /**
     * Do actions during plugin load.
     */
    public static function loaded_googleanalytics() {
        self::update_googleanalytics();
    }

    /**
     * Update hook fires when plugin is being loaded.
     */
    public static function update_googleanalytics() {
        $version            = get_option( self::GA_VERSION_OPTION_NAME );
        $installed_version  = get_option( self::GA_VERSION_OPTION_NAME, '2.4.0' );
        $old_property_value = Ga_Helper::get_option( 'web_property_id' );

        if ( version_compare( $installed_version, GOOGLEANALYTICS_VERSION, 'eq' ) ) {
            return;
        }

        if ( version_compare( $installed_version, GOOGLEANALYTICS_VERSION, 'lt' ) ) {

            if ( ! empty( $old_property_value ) ) {
                Ga_Helper::update_option( self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME, $old_property_value );
                Ga_Helper::update_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME, 1 );
                delete_option( 'web_property_id' );
            }
        }

        update_option( self::GA_VERSION_OPTION_NAME, GOOGLEANALYTICS_VERSION );
    }

    public static function preupdate_exclude_roles( $new_value, $old_value ) {
        if ( ! Ga_Helper::are_features_enabled() ) {
            return '';
        }

        return wp_json_encode( $new_value );
    }

    /**
     * Pre-update hook for preparing JSON structure.
     *
     * @param $new_value
     * @param $old_value
     *
     * @return mixed
     */
    public static function preupdate_selected_account( $new_value, $old_value ) {
        $data = null;
        if ( ! empty( $new_value ) ) {
            $data = explode( '_', $new_value );

            if ( ! empty( $data[1] ) ) {
                Ga_Helper::update_option( self::GA_WEB_PROPERTY_ID_OPTION_NAME, $data[1] );
            }
        }

        return wp_json_encode( $data );
    }

    public static function preupdate_disable_all_features( $new_value, $old_value ) {
        if ( 'on' === $old_value ) {
            Ga_Helper::update_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME, false );
        }

        return $new_value;
    }

    public static function preupdate_optimize_code( $new_value, $old_value ) {
        if ( ! empty( $new_value ) ) {
            $new_value = sanitize_text_field( wp_unslash( $new_value ) );
        }

        return $new_value;
    }

    public static function preupdate_ip_anonymization( $new_value, $old_value ) {
        return $new_value;
    }

    /**
     * Registers plugin's settings.
     */
    public static function admin_init_googleanalytics() {
        register_setting( GA_NAME, self::GA_WEB_PROPERTY_ID_OPTION_NAME );
        register_setting( GA_NAME, self::GA_EXCLUDE_ROLES_OPTION_NAME );
        register_setting( GA_NAME, self::GA_SELECTED_ACCOUNT );
        register_setting( GA_NAME, self::GA_OAUTH_AUTH_CODE_OPTION_NAME );
        register_setting( GA_NAME, self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME );
        register_setting( GA_NAME, self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME );
        register_setting( GA_NAME, self::GA_DISABLE_ALL_FEATURES );
        register_setting( GA_NAME, 'googleanalytics_optimize_code' );
        register_setting( GA_NAME, 'googleanalytics_ip_anonymization' );
        add_filter( 'pre_update_option_' . self::GA_EXCLUDE_ROLES_OPTION_NAME, 'Ga_Admin::preupdate_exclude_roles', 1, 2 );
        add_filter( 'pre_update_option_' . self::GA_SELECTED_ACCOUNT, 'GA_Admin::preupdate_selected_account', 1, 2 );
        add_filter( 'pre_update_option_googleanalytics_optimize_code', 'Ga_Admin::preupdate_optimize_code', 1, 2 );
        add_filter( 'pre_update_option_googleanalytics_ip_anonymization', 'Ga_Admin::preupdate_ip_anonymization', 1, 2 );
    }

    /**
     * Builds plugin's menu structure.
     */
    public static function admin_menu_googleanalytics() {
        $gdpr = get_option('googleanalytics_gdpr_config');

        if ( current_user_can( 'manage_options' ) ) {
            add_menu_page( 'Google Analytics', 'Google Analytics', 'manage_options', 'googleanalytics', 'Ga_Admin::statistics_page_googleanalytics', 'dashicons-chart-line', 1000 );
            add_submenu_page( 'googleanalytics', 'Google Analytics', __( 'Dashboard' ), 'manage_options', 'googleanalytics', 'Ga_Admin::statistics_page_googleanalytics' );
            add_submenu_page( 'googleanalytics', 'Google Analytics', __( 'Settings' ), 'manage_options', 'googleanalytics/settings', 'Ga_Admin::options_page_googleanalytics' );

            if (!empty($gdpr)) {
                add_submenu_page('googleanalytics', 'Google Analytics', __('GDPR'), 'manage_options',
                    'googleanalytics/gdpr', 'Ga_Admin::gdpr_page_googleanalytics');
            }
        }
    }

    /**
     * Prepares and displays plugin's stats page.
     */
    public static function statistics_page_googleanalytics() {

        if ( ! Ga_Helper::is_wp_version_valid() || ! Ga_Helper::is_php_version_valid() ) {
            return false;
        }

        $data = self::get_stats_page();
        Ga_View_Core::load(
            'statistics',
            array(
                'data' => $data,
            )
        );

        if ( Ga_Cache::is_data_cache_outdated( '', Ga_Helper::get_account_id() ) ) {
            self::api_client()->add_own_error( '1', __( 'Saved data is shown, it will be refreshed soon' ), 'Ga_Data_Outdated_Exception' );
        }

        self::display_api_errors();
    }

    /**
     * Prepares and displays plugin's settings page.
     */
    public static function options_page_googleanalytics() {

        if ( ! Ga_Helper::is_wp_version_valid() || ! Ga_Helper::is_php_version_valid() ) {
            return false;
        }
        if ( Ga_Helper::are_features_enabled() && Ga_Helper::is_curl_disabled() ) {
            echo wp_kses_post( Ga_Helper::ga_wp_notice( __( 'Looks like cURL is not configured on your server. In order to authenticate your Google Analytics account and display statistics, cURL is required. Please contact your server administrator to enable it, or manually enter your Tracking ID.' ), 'warning' ) );
        }
        /**
         * Keeps data to be extracted as variables in the view.
         *
         * @var array $data
         */
        $data = array();

        $data[ self::GA_WEB_PROPERTY_ID_OPTION_NAME ]                = get_option( self::GA_WEB_PROPERTY_ID_OPTION_NAME );
        $data[ self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME ] = get_option( self::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME );
        $data[ self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ]       = get_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME );
        $data[ self::GA_DISABLE_ALL_FEATURES ]                       = get_option( self::GA_DISABLE_ALL_FEATURES );

        $roles = Ga_Helper::get_user_roles();
        $saved = json_decode( get_option( self::GA_EXCLUDE_ROLES_OPTION_NAME ), true );

        $tmp = array();
        if ( ! empty( $roles ) ) {
            foreach ( $roles as $role ) {
                $role_id = Ga_Helper::prepare_role_id( $role );
                $tmp[]   = array(
                    'name'    => $role,
                    'id'      => $role_id,
                    'checked' => ( ! empty( $saved[ $role_id ] ) && 'on' === $saved[ $role_id ] ),
                );
            }
        }
        $data['roles'] = $tmp;

        if ( Ga_Helper::is_authorized() ) {
            $data['ga_accounts_selector'] = self::get_accounts_selector();
            $data['auth_button']          = self::get_auth_button( 'reauth' );
        } else {
            $data['popup_url']   = self::get_auth_popup_url();
            $data['auth_button'] = self::get_auth_button( 'auth' );
        }
        $data['debug_modal'] = self::get_debug_modal();
        $data['debug_info']  = Ga_SupportLogger::$debug_info;

        if ( ! empty( $_GET['err'] ) ) { // WPCS: CSRF ok.
            switch ( $_GET['err'] ) { // WPCS: CSRF ok.
                case 1:
                    $data['error_message'] = Ga_Helper::ga_oauth_notice( 'There was a problem with Google Oauth2 authentication process. Please verify your site has a valid SSL Certificate in place and is using the HTTPS protocol.' );
                    break;
                case 2:
                    $data['error_message'] = Ga_Helper::ga_wp_notice( 'Authentication code is incorrect.', 'error', true );
                    break;
            }
        }
        Ga_View_Core::load(
            'page',
            array(
                'data'    => $data,
                'tooltip' => Ga_Helper::get_tooltip(),
            )
        );

        self::display_api_errors();
    }

    /**
     * Prepares and displays plugin's gdpr page.
     */
    public static function gdpr_page_googleanalytics() {

        if ( ! Ga_Helper::is_wp_version_valid() || ! Ga_Helper::is_php_version_valid() ) {
            return false;
        }
        if ( Ga_Helper::are_features_enabled() && Ga_Helper::is_curl_disabled() ) {
            echo wp_kses_post( Ga_Helper::ga_wp_notice( __( 'Looks like cURL is not configured on your server. In order to authenticate your Google Analytics account and display statistics, cURL is required. Please contact your server administrator to enable it, or manually enter your Tracking ID.' ), 'warning' ) );
        }

        $vendor_data = self::getVendors();
        $vendors = $vendor_data['vendors'];
        $purposes = array_column($vendor_data['purposes'], 'name', 'id');

        include plugin_dir_path(__FILE__) . '../view/templates/gdpr-config.php';
    }

    /**
     * Prepares and returns a plugin's URL to be opened in a popup window
     * during Google authentication process.
     *
     * @return mixed
     */
    public static function get_auth_popup_url() {
        return admin_url( Ga_Helper::create_url( Ga_Helper::GA_SETTINGS_PAGE_URL, array( Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_auth' ) ) );
    }

    /**
     * Prepares and returns Google Account's dropdown code.
     *
     * @return string
     */
    public static function get_accounts_selector() {
        $selected = Ga_Helper::get_selected_account_data();
        $selector = json_decode( get_option( self::GA_ACCOUNT_DATA_OPTION_NAME ), true );
        if ( ! Ga_Helper::is_code_manually_enabled() && empty( $selector ) ) {
            echo wp_kses_post( Ga_Helper::ga_wp_notice( "Hi there! It seems like we weren't able to locate a Google Analytics account attached to your email account. Can you please register for Google Analytics and then deactivate and reactivate the plugin?", 'warning' ) );
        }

        return Ga_View_Core::load(
            'ga_accounts_selector',
            array(
                'selector'             => $selector,
                'selected'             => $selected ? implode( '_', $selected ) : null,
                'add_manually_enabled' => Ga_Helper::is_code_manually_enabled() || Ga_Helper::is_all_feature_disabled(),
            ),
            true
        );
    }

    /**
     * Adds JS scripts for the settings page.
     */
    public static function enqueue_ga_scripts() {
        $property_id = get_option( 'googleanalytics_sherethis_property_id', true );
        $secret = get_option( 'googleanalytics_sherethis_property_secret', true );
        $config = wp_json_encode(get_option('googleanalytics_gdpr_config'));

        wp_register_script(
            GA_NAME . '-page-js',
            Ga_Helper::get_plugin_url_with_correct_protocol() . '/js/' . GA_NAME . '_page.js',
            [ 'jquery' ],
            time(),
            false
        );

        wp_enqueue_script(GA_NAME . '-page-js');
        wp_add_inline_script(
            GA_NAME . '-page-js',
            'var siteAdminUrl = \'' .
            admin_url() .
            '\'; var gaGdprConfig = \''.
            $config .
            '\'; var ga_demo_nonce = "' .
            wp_create_nonce('ga_demo_nonce') .
            '"; var ga_property_id = "' . $property_id .
            '"; var ga_secret_id = "' .
            $secret .
            '";'
        );
    }

    /**
     * Adds CSS plugin's scripts.
     */
    public static function enqueue_ga_css() {
        wp_register_style( GA_NAME . '-css', Ga_Helper::get_plugin_url_with_correct_protocol() . '/css/' . GA_NAME . '.css', false, time(), 'all' );
        wp_register_style( GA_NAME . '-additional-css', Ga_Helper::get_plugin_url_with_correct_protocol() . '/css/ga_additional.css', false, GOOGLEANALYTICS_VERSION, 'all' );
        wp_enqueue_style( GA_NAME . '-css');
        wp_enqueue_style( GA_NAME . '-additional-css' );
        if ( Ga_Helper::is_wp_old() ) {
            wp_register_style( GA_NAME . '-old-wp-support-css', Ga_Helper::get_plugin_url_with_correct_protocol() . '/css/ga_old_wp_support.css', false, GOOGLEANALYTICS_VERSION, 'all' );
            wp_enqueue_style( GA_NAME . '-old-wp-support-css' );
        }
        wp_register_style( GA_NAME . '-modal-css', Ga_Helper::get_plugin_url_with_correct_protocol() . '/css/ga_modal.css', false, GOOGLEANALYTICS_VERSION, 'all' );
        wp_enqueue_style( GA_NAME . '-modal-css' );
    }

    /**
     * Enqueues dashboard JS scripts.
     */
    private static function enqueue_dashboard_scripts() {
        wp_register_script(
            GA_NAME . '-dashboard-js',
            Ga_Helper::get_plugin_url_with_correct_protocol() . '/js/' . GA_NAME . '_dashboard.js',
            [ 'jquery' ],
            GOOGLEANALYTICS_VERSION,
            false
        );
        wp_enqueue_script( GA_NAME . '-dashboard-js' );
    }

    /**
     * Enqueues plugin's JS and CSS scripts.
     */
    public static function enqueue_scripts() {
        $domain = str_replace('http://','', str_replace('https://', '', str_replace( '/wp-admin/', '', admin_url() )));
        $st_prop = get_option(self::GA_SHARETHIS_PROPERTY_ID);
        $st_secret = get_option(self::GA_SHARETHIS_PROPERTY_SECRET);

        if ( Ga_Helper::is_dashboard_page() || Ga_Helper::is_plugin_page() ) {
            wp_register_script(
                GA_NAME . '-js',
                Ga_Helper::get_plugin_url_with_correct_protocol() . '/js/' . GA_NAME . '.js',
                [ 'jquery' ],
                GOOGLEANALYTICS_VERSION,
                false
            );
            wp_enqueue_script( GA_NAME . '-js' );

            wp_register_script( 'googlecharts', 'https://www.gstatic.com/charts/loader.js', null, 1, false );
            wp_enqueue_script( 'googlecharts' );
            wp_add_inline_script(GA_NAME . '-js', 'var ga_demo_nonce = "' . wp_create_nonce('ga_demo_nonce') . '";');

            if ( empty($st_prop) || empty($st_secret) ) {
                wp_register_script( 'googlecreateprop', Ga_Helper::get_plugin_url_with_correct_protocol() . '/js/googleanalytics_createprop.js', ['jquery', 'wp-util'], time(), false );
                wp_enqueue_script('googlecreateprop');
                wp_add_inline_script('googlecreateprop', '
                    var gaNonce = "' . wp_create_nonce('googleanalyticsnonce') . '";
                    var gasiteURL = "' . $domain . '";
                    var gaAdminEmail = "' . get_option('admin_email') . '";'
                );
            }

            self::enqueue_ga_css();
        }

        if ( Ga_Helper::is_dashboard_page() ) {
            self::enqueue_dashboard_scripts();
        }

        if ( Ga_Helper::is_plugin_page() ) {
            self::enqueue_ga_scripts();
        }
    }

    /**
     * Prepares plugin's statistics page and return HTML code.
     *
     * @return string HTML code
     */
    public static function get_stats_page() {
        $chart   = null;
        $age_chart = null;
        $gender_chart = null;
        $boxes   = null;
        $labels  = null;
        $sources = null;
        if ( Ga_Helper::is_authorized() && Ga_Helper::is_account_selected() && ! Ga_Helper::is_all_feature_disabled() ) {
            list( $chart, $age_chart, $gender_chart, $boxes, $labels, $sources ) = self::generate_stats_data();
        }

        return Ga_Helper::get_chart_page(
            'stats',
            array(
                'chart'   => $chart,
                'gender_chart' => $gender_chart,
                'age_chart' => $age_chart,
                'boxes'   => $boxes,
                'labels'  => $labels,
                'sources' => $sources,
            )
        );
    }

    /**
     * Shows plugin's notice on the admin area.
     */
    public static function admin_notice_googleanalytics() {
        if ( (!get_option( self::GA_SHARETHIS_TERMS_OPTION_NAME ) && Ga_Helper::is_plugin_page() ) || (!get_option( self::GA_SHARETHIS_TERMS_OPTION_NAME ) && !get_option( self::GA_HIDE_TERMS_OPTION_NAME ) ) ) {
            $current_url = Ga_Helper::get_current_url();
            $url		 = ( strstr( $current_url, '?' ) ? $current_url . '&' : $current_url . '?' ) . http_build_query( array( Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_update_terms' ) );
            Ga_View_Core::load( 'ga_notice', array(
                'url' => $url
            ) );
        }

        if ( !empty( $_GET[ 'settings-updated' ] ) && Ga_Helper::is_plugin_page() ) {
            echo Ga_Helper::ga_wp_notice( _( 'Settings saved' ), self::NOTICE_SUCCESS );
        }

        if ( Ga_Helper::get_option( self::GA_DISABLE_ALL_FEATURES ) ) {
            echo wp_kses_post(
                Ga_Helper::ga_wp_notice(
                    __( 'You have disabled all extra features, click here to enable Dashboards, Viral Alerts and Google API.' ),
                    'warning',
                    false,
                    array(
                        'url'   => admin_url( Ga_Helper::create_url( Ga_Helper::GA_SETTINGS_PAGE_URL, array( Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_enable_all_features' ) ) ),
                        'label' => __( 'Enable' ),
                    )
                )
            );
        }
    }

    /**
     * Prepare required PHP version warning.
     * @return string
     */
    public static function admin_notice_googleanalytics_php_version() {
        echo wp_kses_post( Ga_Helper::ga_wp_notice( 'Cannot use Google Analytics plugin. PHP version ' . phpversion() . ' is to low. Required PHP version: ' . Ga_Helper::PHP_VERSION_REQUIRED, 'error' ) );
    }

    /**
     * Prepare required WP version warning
     * @return string
     */
    public static function admin_notice_googleanalytics_wp_version() {
        echo wp_kses_post( Ga_Helper::ga_wp_notice( 'Google Analytics plugin requires at least WordPress version ' . self::MIN_WP_VERSION, 'error' ) );
    }

    /**
     * Hides plugin's notice
     */
    public static function admin_notice_hide_googleanalytics() {
        update_option( self::GA_HIDE_TERMS_OPTION_NAME, true );
    }

    /**
     * Adds GA dashboard widget only for administrators.
     */
    public static function add_dashboard_device_widget() {
        if ( Ga_Helper::is_administrator() ) {
            wp_add_dashboard_widget( 'ga_dashboard_widget', __( 'Google Analytics Dashboard' ), 'Ga_Helper::add_ga_dashboard_widget' );
        }
    }

    /**
     * Adds plugin's actions
     */
    public static function add_actions() {
        add_action( 'admin_init', 'Ga_Admin::admin_init_googleanalytics' );
        add_action( 'admin_menu', 'Ga_Admin::admin_menu_googleanalytics' );
        add_action( 'admin_enqueue_scripts', 'Ga_Admin::enqueue_scripts' );
        add_action( 'wp_dashboard_setup', 'Ga_Admin::add_dashboard_device_widget' );
        add_action( 'wp_ajax_ga_ajax_data_change', 'Ga_Admin::ga_ajax_data_change' );
        add_action( 'wp_ajax_ga_ajax_hide_review', 'Ga_Admin::ga_ajax_hide_review' );
        add_action( 'wp_ajax_ga_ajax_enable_gdpr', 'Ga_Admin::gaAjaxGdprEnable' );
        add_action( 'wp_ajax_ga_ajax_enable_demographic', 'Ga_Admin::gaAjaxEnableDemo' );
        add_action( 'admin_notices', 'Ga_Admin::admin_notice_googleanalytics' );
        add_action( 'heartbeat_tick', 'Ga_Admin::run_heartbeat_jobs' );
        add_action( 'wp_ajax_googleanalytics_send_debug_email', 'Ga_SupportLogger::send_email' );
        add_action( 'wp_ajax_set_ga_credentials', 'Ga_Admin::createGAProperty' );

        if ( !get_option( self::GA_SHARETHIS_TERMS_OPTION_NAME ) && !get_option( self::GA_HIDE_TERMS_OPTION_NAME ) ) {
            add_action( 'wp_ajax_googleanalytics_hide_terms', 'Ga_Admin::admin_notice_hide_googleanalytics' );
        }
    }

    /**
     * Runs jobs
     *
     * @param $response
     * @param $screen_id
     */
    public static function run_heartbeat_jobs( $response, $screen_id = '' ) {

        if ( self::GA_HEARTBEAT_API_CACHE_UPDATE ) {
            // Disable cache for ajax request
            self::api_client()->set_disable_cache( true );

            // Try to regenerate cache if needed
            self::generate_stats_data();
        }
    }

    /**
     * Adds plugin's filters
     */
    public static function add_filters() {
        add_filter( 'plugin_action_links', 'Ga_Admin::ga_action_links', 10, 5 );
    }

    /**
     * Adds new action links on the plugin list.
     *
     * @param $actions
     * @param $plugin_file
     *
     * @return mixed
     */
    public static function ga_action_links( $actions, $plugin_file ) {

        if ( basename( $plugin_file ) === GA_NAME . '.php' ) {
            array_unshift( $actions, '<a href="' . esc_url( get_admin_url( null, Ga_Helper::GA_SETTINGS_PAGE_URL ) ) . '">' . __( 'Settings' ) . '</a>' );
        }

        return $actions;
    }

    public static function init_oauth() {

        $code = Ga_Helper::get_option( self::GA_OAUTH_AUTH_CODE_OPTION_NAME );

        if ( ! empty( $code ) ) {
            Ga_Helper::update_option( self::GA_OAUTH_AUTH_CODE_OPTION_NAME, '' );

            // Get access token
            $response = self::api_client()->call( 'ga_auth_get_access_token', $code );
            if ( empty( $response ) ) {
                return false;
            }
            $param = '';
            if ( ! self::save_access_token( $response ) ) {
                $param  = '&err=1';
                $errors = self::api_client()->get_errors();
                if ( ! empty( $errors ) ) {
                    foreach ( $errors as $error ) {
                        if ( 'invalid_grant' === $error['message'] ) {
                            $param = '&err=2';
                        }
                    }
                }
            } else {
                self::api_client()->set_access_token( $response->getData() );
                // Get accounts data
                $account_summaries = self::api_client()->call( 'ga_api_account_summaries' );
                self::save_ga_account_summaries( $account_summaries->getData() );
                update_option( self::GA_SELECTED_ACCOUNT, '' );
            }

            wp_safe_redirect( admin_url( Ga_Helper::GA_SETTINGS_PAGE_URL . $param ) );
        }
    }

    /**
     * Save access token.
     *
     * @param Ga_Lib_Api_Response $response
     *
     * @return boolean
     */
    public static function save_access_token( $response, $refresh_token = '' ) {
        $access_token = $response->getData();
        if ( ! empty( $access_token ) ) {
            $access_token['created'] = time();
        } else {
            return false;
        }

        if ( ! empty( $refresh_token ) ) {
            $access_token['refresh_token'] = $refresh_token;
        }

        return update_option( self::GA_OAUTH_AUTH_TOKEN_OPTION_NAME, wp_json_encode( $access_token ) );
    }

    /**
     * Saves Google Analytics account data.
     *
     * @param $data
     *
     * @return array
     */
    public static function save_ga_account_summaries( $data ) {
        $return = array();
        if ( ! empty( $data['items'] ) ) {
            foreach ( $data['items'] as $item ) {
                $tmp         = array();
                $tmp['id']   = $item['id'];
                $tmp['name'] = $item['name'];
                if ( is_array( $item['webProperties'] ) ) {
                    foreach ( $item['webProperties'] as $property ) {
                        $profiles = array();
                        if ( is_array( $property['profiles'] ) ) {
                            foreach ( $property['profiles'] as $profile ) {
                                $profiles[] = array(
                                    'id'   => $profile['id'],
                                    'name' => $profile['name'],
                                );
                            }
                        }

                        $tmp['webProperties'][] = array(
                            'internalWebPropertyId' => $property['internalWebPropertyId'],
                            'webPropertyId' => $property['id'],
                            'name'          => $property['name'],
                            'profiles'      => $profiles,
                        );
                    }
                }

                $return[] = $tmp;
            }

            update_option( self::GA_ACCOUNT_DATA_OPTION_NAME, wp_json_encode( $return ) );
        } else {
            update_option( self::GA_ACCOUNT_DATA_OPTION_NAME, '' );
        }
        update_option( self::GA_WEB_PROPERTY_ID_OPTION_NAME, '' );

        return $return;
    }

    /**
     * Handle AJAX data for the GA dashboard widget.
     */
    public static function ga_ajax_data_change() {
        if ( Ga_Admin_Controller::validate_ajax_data_change_post( $_POST ) ) {
            $date_range = ! empty( $_POST['date_range'] ) ? $_POST['date_range'] : null; // WPCS: CSRF ok.
            $metric     = ! empty( $_POST['metric'] ) ? $_POST['metric'] : null; // WPCS: CSRF ok.
            echo wp_kses_post( Ga_Helper::get_ga_dashboard_widget_data_json( $date_range, $metric, false, true ) );
        } else {
            echo wp_json_encode( array( 'error' => __( 'Invalid request.' ) ) );
        }

        wp_die();
    }

    /**
     * Displays API error messages.
     */
    public static function display_api_errors( $alias = '' ) {
        $errors = self::api_client( $alias )->get_errors();
        if ( ! empty( $errors ) ) {
            foreach ( $errors as $error ) {
                echo wp_kses_post( Ga_Notice::get_message( $error ) );
            }
        }
    }

    /**
     * Gets dashboard data.
     *
     * @return array
     */
    public static function generate_stats_data() {
        $selected = Ga_Helper::get_selected_account_data( true );
        $update_data = self::checkDataDate();
        $query_params = isset( $_GET['th'] ) ? Ga_Stats::get_query( 'main_chart', $selected['view_id'], '30daysAgo' ) : Ga_Stats::get_query( 'main_chart', $selected['view_id'] );
        $stats_data   = self::api_client()->call(
            'ga_api_data',
            [ $query_params ]
        );
        $gender_params = isset( $_GET['th'] ) ? Ga_Stats::get_query( 'gender', $selected['view_id'], '30daysAgo' ) : Ga_Stats::get_query( 'gender', $selected['view_id'] );
        $gender_data = self::api_client()->call(
            'ga_api_data',
            [$gender_params]
        );
        $age_params = isset( $_GET['th'] ) ? Ga_Stats::get_query( 'age', $selected['view_id'], '30daysAgo' ) : Ga_Stats::get_query( 'age', $selected['view_id'] );
        $age_data = self::api_client()->call(
            'ga_api_data',
            [$age_params]
        );
        $boxes_data      = self::api_client()->call(
            'ga_api_data',
            [ Ga_Stats::get_query( 'boxes', $selected['view_id'] ) ]
        );
        $sources_data    = self::api_client()->call(
            'ga_api_data',
            [ Ga_Stats::get_query( 'sources', $selected['view_id'] ) ]
        );

        $chart           = ! empty( $stats_data ) ? Ga_Stats::get_chart( $stats_data->getData() ) : array();
        $gender_chart    = ! empty($gender_data) ? Ga_Stats::get_gender_chart($gender_data->getData()) : [];
        $age_chart       = ! empty($age_data) ? Ga_Stats::get_age_chart($age_data->getData()) : [];
        $boxes           = ! empty( $boxes_data ) ? Ga_Stats::get_boxes( $boxes_data->getData() ) : array();
        $last_chart_date = ! empty( $chart ) ? $chart['date'] : strtotime( 'now' );

        unset( $chart['date'] );
        $labels  = array(
            'thisWeek' => date( 'M d, Y', strtotime( '-6 day', $last_chart_date ) ) . ' - ' . date( 'M d, Y', $last_chart_date ),
            'thisMonth' => date( 'M d, Y', strtotime( '-29 day', $last_chart_date ) ) . ' - ' . date( 'M d, Y', $last_chart_date ),
        );
        $sources = ! empty( $sources_data ) ? Ga_Stats::get_sources( $sources_data->getData() ) : array();

        // Add gender/age data for 30 days.
        if ($update_data) {
            $gender_params = Ga_Stats::get_query( 'gender', $selected['view_id'], '30daysAgo' );
            $gender_data = self::api_client()->call(
                'ga_api_data',
                [$gender_params]
            );
            $age_params = Ga_Stats::get_query( 'age', $selected['view_id'], '30daysAgo' );
            $age_data = self::api_client()->call(
                'ga_api_data',
                [$age_params]
            );

            $gender_chart    = ! empty($gender_data) ? Ga_Stats::get_gender_chart($gender_data->getData()) : [];
            $age_chart       = ! empty($age_data) ? Ga_Stats::get_age_chart($age_data->getData()) : [];

            self::updateDemoData(
                $gender_chart,
                $age_chart
            );
        }

        return [$chart, $age_chart, $gender_chart, $boxes, $labels, $sources];
    }

    private static function updateDemoData($gender_response = false, $age_response = false)
    {
        $demoSendData = [];
        if ($gender_response && $age_response) {
            foreach ($age_response as $type => $amount) {
                $demoSendData['age'][$type] = intval($amount);
                $x++;
            }
            foreach ($gender_response as $type => $amount) {
                $demoSendData['gender'][ucfirst($type)] = intval($amount);
                $x++;
            }
        }

        // Add data for send.
        update_option('googleanalytics_demo_data', wp_json_encode($demoSendData));

        // Trigger send.
        update_option('googleanalytics_send_data', "true");
    }

    /**
     * Check if we should send batch of demo data.
     *
     * @return bool
     */
    private static function checkDataDate()
    {
    	$demo_enabled = get_option('googleanalytics_demographic');
    	$demo_date = get_option('googleanalytics_demo_date');
        $demo_date = !empty($demo_date) ? strtotime($demo_date) : '';
        $thirty_date = '' !== $demo_date ? date("Y-m-d", strtotime("+1 month", $demo_date)) : '';

        if (empty($demo_enabled) || !$demo_enabled) {
        	return false;
        }

        if ('' !== $demo_date && $thirty_date <= $current_date) {
            return true;
        } elseif ('' === $demo_date) {
            return true;
        }

        return false;
    }

    /**
     * Returns auth or re-auth button
     *
     * @return string
     */
    public static function get_auth_button( $type ) {

        return Ga_View_Core::load(
            'ga_auth_button',
            [
                'label'       => 'auth' === $type ? 'Authenticate with Google' : 'Re-authenticate with Google',
                'type'        => $type,
                'url'         => self::get_auth_popup_url(),
                'manually_id' => get_option( self::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ),
            ],
            true
        );
    }

    /**
     * Returns debug modal
     *
     * @return string
     */
    public static function get_debug_modal() {

        return Ga_View_Core::load(
            'ga_debug_modal',
            [ 'debug_info' => Ga_SupportLogger::$debug_info, 'debug_help_message' => Ga_SupportLogger::$debug_help_message ],
            true
        );
    }

    public static function ga_ajax_hide_review( $post ) {
        $error = 0;

        if ( Ga_Controller_Core::verify_nonce( 'ga_ajax_data_change' ) ) {
            update_option('googleanalytics-hide-review', true);
        }

        wp_send_json_success('hidden');
    }

    public static function gaAjaxGdprEnable( $post ) {
        if (!isset($_POST['config'])) {
            wp_send_json_error('No config found.');
        }

        update_option('googleanalytics_gdpr_config', $_POST['config']);

        wp_send_json_success('gdpr_on');
    }

    public static function gaAjaxEnableDemo( $post ) {
        check_ajax_referer( 'ga_demo_nonce', 'nonce' );

        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true' ? true : false;

        update_option('googleanalytics_demographic', $enabled);

        wp_send_json_success('demo_on');
    }

    /**
     * New property creation method.
     */
    public static function createGAProperty() {
        check_ajax_referer( 'googleanalyticsnonce', 'nonce' );

        if (! isset($_POST['propid'], $_POST['secret'])) { // WPCS: input var ok.
            wp_send_json_error( 'Set credentials failed.' );
        }

        $secret = sanitize_text_field( wp_unslash( $_POST['secret'] ) ); // WPCS: input var ok.
        $propid = sanitize_text_field( wp_unslash( $_POST['propid'] ) ); // WPCS: input var ok.

        update_option(self::GA_SHARETHIS_PROPERTY_ID, $propid);
        update_option(self::GA_SHARETHIS_PROPERTY_SECRET, $secret);
    }

    /**
     * Helper function get vendors.
     *
     * @param string $page
     * @return array
     */
    private static function getVendors()
    {
        $response = wp_remote_get('https://vendorlist.consensu.org/v2/vendor-list.json');

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
