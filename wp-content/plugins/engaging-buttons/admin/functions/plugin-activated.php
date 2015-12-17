<?
// set wp_option to let all functions know that the Engaging Buttons function was activated
function enp_on_plugin_activate() {
    // add the option to trigger the activation hooks
    add_option( 'enp_button_activated', 'activated' );
}

// Controller to tell functions activation happened
function enp_on_plugin_activate_hooks() {
    // if we've just activated the plugin, run all activation hooks
    if ( get_option( 'enp_button_activated' ) === 'activated' ) {
        // check if they already have data activated
        $data_collection = get_option( 'enp_button_allow_data_tracking' );
        // if it's activated, display the message
        if($data_collection !== '1') {
            // add action for admin_notices to display the optin message
            add_action( 'admin_notices', 'enp_data_optin_message' );
        }

        /*
        Put any other activation hooks here
        */

        // remove the button activated option so this won't run until the
        // next time we've activated
        delete_option( 'enp_button_activated' );
    }

}

// call the plugin_activate_hooks to check if anything needs to run
enp_on_plugin_activate_hooks();
?>
