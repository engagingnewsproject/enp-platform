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

    /**
     * Permission callback for telemetry data storage
     *
     * Requires manage_options capability to prevent unauthorized users
     * from injecting arbitrary data into wp_options.
     *
     * @since 3.8.18
     * @since 3.14.5 Changed from is_user_logged_in() to current_user_can('manage_options')
     *
     * @return bool
     */
    function nf_be_store_data_permission_callback() {
        return current_user_can('manage_options');
    }

    /**
     * Store telemetry data
     *
     * Legacy arrays without schema_version are rejected with 410 and not stored.
     *
     * @since 3.8.18
     * @since 3.14.5 Added input validation and sanitization
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    function nf_be_store_data($request) {
        $data = json_decode($request['data'], true);

        if (is_array($data) && isset($data['schema_version'])) {
            if (!NF_Telemetry_BehavioralEvent::valid($data)) {
                return new WP_REST_Response('Invalid event', 400);
            }
            // Identity comes from the authenticated WordPress user.
            $data['user_id'] = get_current_user_id();
            if (!NF_Telemetry_BehavioralQueue::enqueue($data)) {
                return new WP_REST_Response('Event not stored; retry later', 503);
            }
            return new WP_REST_Response(['accepted_event_ids' => [$data['event_id']]], 200);
        }

        // Only tabs opened before an upgrade still send the legacy array, which carried
        // captured page HTML. It is no longer stored, so the monthly backlog can only shrink.
        return new WP_REST_Response('Legacy format is no longer accepted', 410);
    }

}
