<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Routes_SubmissionsActions
 */
final class NF_Routes_Telemetry extends NF_Abstracts_Routes
{
    
    /**
     * Register REST API routes related to Telemetry
     * 
     * @since 3.8.18
     * 
     * @route "nf-be-data/store"
     */
    function register_routes() {
        
        $opted_out = !has_filter( 'ninja_forms_settings_licenses_addons' ) && ( ! (bool) get_option( 'ninja_forms_allow_tracking' ) || (bool) get_option( 'ninja_forms_do_not_allow_tracking' ) );
        if(!$opted_out) {  
            register_rest_route('nf-be-data', 'store', array(
                'methods' => 'POST',
                'callback' => [ $this, 'nf_be_store_data' ],
                'permission_callback' => [ $this, 'nf_be_store_data_permission_callback' ]
            ));
        }
        
    }

    // Security check for the rest route
    function nf_be_store_data_permission_callback() {
        return is_user_logged_in();
    }

    // Action for the REST route
    function nf_be_store_data($request) {
        $data = json_decode($request['data'], true);
        if(!$data || $data[2] <= 0) {
            return new WP_REST_Response('Invalid data', 400);
        }
        $data[] = get_current_user_id();
        $stored_data = get_option('nf_be_data', []);
        $stored_data[] = $data;
        update_option('nf_be_data', $stored_data);
        return new WP_REST_Response('Data stored', 200);
    }

}