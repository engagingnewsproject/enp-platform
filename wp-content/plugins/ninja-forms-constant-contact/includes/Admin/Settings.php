<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_ConstantContact_Admin_Settings
 */
final class NF_ConstantContact_Admin_Settings
{
    public function __construct()
    {
        add_filter( 'ninja_forms_plugin_settings', array( $this, 'plugin_settings' ), 10, 1 );
        add_filter( 'ninja_forms_plugin_settings_groups', array( $this, 'plugin_settings_groups' ), 10, 1 );
        /**
         * This is commented out to avoid unnecessary API hits.
         */
//        add_filter( 'ninja_forms_check_setting_constant_contact_access_token', array($this, 'validate_ninja_forms_constant_contact_access_token'), 10, 1);
        add_action( 'nf_init', array( $this, 'update_constant_contact_token' ) );
    }

    public function plugin_settings( $settings )
    {
        $settings[ 'constant_contact' ] = NF_ConstantContact()->config( 'PluginSettings' );
        return $settings;
    }

    public function plugin_settings_groups( $groups )
    {
        $groups = array_merge( $groups, NF_ConstantContact()->config( 'PluginSettingsGroups' ) );
        return $groups;
    }

    // TODO: Maybe remove this function?
    public function validate_ninja_forms_constant_contact_access_token( $setting )
    {
        $api_key = trim( $setting[ 'value' ] );

        $api_url  = 'https://api.constantcontact.com/v2/lists?api_key=' . NF_ConstantContact()->get_dev_api_key();

        $headers = array( 'Authorization' => 'Bearer ' . $api_key );

        $response = wp_remote_get( $api_url, array( 'headers' => $headers, 'sslverify' => false ) );
        
        if( 200 == $response[ 'response' ][ 'code' ] ){
          return $setting;
        } else{
            // TODO: Log Error, $e->getMessage(), for System Status Report
            $setting[ 'errors' ][] = __( 'The Constant Contact Access Token you have entered appears to be invalid.', 'ninja-forms-constant-contact' );
            }

        return $setting;
    }

    /**
     * Update Constant Contact Token
     * Listens for the token query string parameter and updates
     * nf settings with the new value from the oauth connection.
     *
     * @since 3.0.3
     * @updated 3.0.4
     */
    public function update_constant_contact_token()
    {
        // If the token parameter is set...
        if( isset( $_GET[ 'nf-cc-token' ] ) ) {
            // ...update our settings and redirect the page to the admin page.
            Ninja_Forms()->update_setting( 'constant_contact_access_token', $_GET[ 'nf-cc-token' ] );
            update_option( 'nf_const_ctc_dev_key_deprecated', 'false' );
            header( 'location: ' . admin_url() . 'admin.php?page=nf-settings#ninja_forms_metabox_constant_contact_settings' );
        }
    }
} // End Class NF_ConstantContact_Admin_Settings
