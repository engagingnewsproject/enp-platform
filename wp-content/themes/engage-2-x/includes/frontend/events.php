<?php
/**
 * Events-related functionality
 * 
 * This file contains all hooks and functions related to The Events Calendar plugin,
 * including event display modifications and date handling.
 */

// Only proceed if The Events Calendar is active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_admin() && is_plugin_active('the-events-calendar/the-events-calendar.php')) {
    add_filter('the_posts', 'tribe_past_reverse_chronological', 100);

    /**
     * When viewing previous events, they will be shown from most recent to oldest
     */
    function tribe_past_reverse_chronological($post_object) {
        $past_ajax = (defined('DOING_AJAX') && DOING_AJAX && $_REQUEST['tribe_event_display'] === 'past') ? true : false;

        if (tribe_is_past() || $past_ajax) {
            $event_title = tribe_get_events_title();
            $dates = get_dates_from_title($event_title);
            $current_date = date('m-d-Y');

            // Skip processing if the title does not contain a date range
            if (count($dates) < 2) {
                error_log('Skipping processing as title does not contain a date range: ' . $event_title);
                return $post_object;
            }

            if ($dates[1] < $current_date) {
                $post_object = array_reverse($post_object);
                add_filter('tribe_get_events_title', 'tribe_alter_event_archive_titles', 11, 2);

                // Debugging: Log the reversed posts object and dates
                error_log('Reversed post object: ' . print_r($post_object, true));
                error_log('Dates array: ' . print_r($dates, true));
                error_log('Current date: ' . $current_date);
            } else {
                error_log('Condition not met. Dates array: ' . print_r($dates, true) . ', Current date: ' . $current_date);
            }
        }

        return $post_object;
    }

    /**
     * Alter event archive titles to maintain chronological order
     */
    function tribe_alter_event_archive_titles($original_recipe_title, $depth) {
        $dates = get_dates_from_title($original_recipe_title);
        if (count($dates) < 2) {
            error_log('Unexpected dates array format in title alteration: ' . print_r($dates, true));
            return $original_recipe_title;
        }
        $title = sprintf(__('Events for %1$s - %2$s', 'the-events-calendar'), $dates[1], $dates[0]);
        return $title;
    }

    /**
     * Helper function to extract dates from event title
     */
    function get_dates_from_title($date_string) {
        $dates = explode(' - ', $date_string);
        $dates[0] = str_replace('Events for ', '', $dates[0]);

        if (count($dates) < 2) {
            error_log('Processed title without date range: ' . $date_string);
            return $dates;
        }

        error_log('Processed dates from title: ' . print_r($dates, true));
        return $dates;
    }
} 