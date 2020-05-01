<?php
/*
*   scheduled-events.php
*   run scheduled events
*
*   since v 0.0.4
*/

// Add a five minute event
function enp_custom_cron_job_recurrence( $schedules ) {
    $schedules['fiveminutes'] = array(
        'display' => __( 'Five Minutes', 'textdomain' ),
        'interval' => 300,
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'enp_custom_cron_job_recurrence' );

// Schedule enp cron jobs
function enp_create_build_button_data_cron() {

    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled( 'enp_build_button_data' );

    //If $timestamp == false schedule daily backups since it hasn't been done previously
    if( $timestamp == false ){
        wp_schedule_event( time(), 'fiveminutes', 'enp_build_button_data' );
    }
}
add_action( 'enp_build_button_data', 'enp_build_popular_button_data' );

function enp_build_popular_button_data() {
    // check to see if anything has changed
    $rebuild_popular_data = get_option('enp_rebuild_popular_data');

    if($rebuild_popular_data !== '1') {
        return false; // Stop the process. Nothing has changed.
    }

    // build the popular button data
    enp_popular_button_save();

    // reset our flag for if we should rebuild the popular data or not
    update_option('enp_rebuild_popular_data', '0');
}

function enp_remove_cron_jobs() {
    wp_clear_scheduled_hook( 'enp_build_button_data' );
    // leave this here until the plugin has been deactived on the enp dev site
    wp_clear_scheduled_hook( 'enp_send_data' );
}
?>
