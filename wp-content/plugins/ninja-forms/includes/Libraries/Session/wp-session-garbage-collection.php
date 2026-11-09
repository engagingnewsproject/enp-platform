<?php
/**
 * Session garbage collection.
 *
 * Ninja Forms parks a halted submission in wp_options as `_wp_session_<id>` plus
 * `_wp_session_expires_<id>` whenever a form hands off to a payment provider.
 * This file removes those once they expire.
 *
 * It is deliberately separate from wp-session.php. That file also registers
 * wp_session_start() on plugins_loaded, so loading it on every request would
 * start a session for visitors who never need one — the regression issue #2579
 * fixed by making session loading lazy. Lazy loading is also what left garbage
 * collection unreachable: the collector lived in the lazily loaded file, and its
 * scheduling was hooked to `wp`, which never fires on cron or admin-ajax
 * requests. So this file carries the collector alone, is loaded on every
 * request, and schedules from `init`, which fires on every request type.
 *
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/8133
 *
 * @package Ninja Forms
 * @subpackage Libraries/Session
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'nf_session_collect_expired_option_names' ) ) {
    /**
     * Gather the option names of every expired session.
     *
     * Scans only the `_wp_session_expires_` rows, which hold a bare timestamp, so
     * this never reads the payload rows — those are the large ones, routinely
     * hundreds of kilobytes each. The scan is paged and performs no deletes, so
     * the offsets stay stable while it runs.
     *
     * @return array<int,string> Option names to delete, data and expiry rows interleaved.
     */
    function nf_session_collect_expired_option_names() {
        global $wpdb;

        $names  = array();
        $now    = time();
        $page   = 500;
        $offset = 0;
        $like   = $wpdb->esc_like( '_wp_session_expires_' ) . '%';

        do {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name, option_value FROM $wpdb->options
                    WHERE option_name LIKE %s
                    ORDER BY option_id ASC
                    LIMIT %d OFFSET %d",
                    $like,
                    $page,
                    $offset
                )
            );

            $rows = is_array( $rows ) ? $rows : array();

            foreach ( $rows as $row ) {
                // A non-numeric expiry is not ours to interpret; leave it alone.
                if ( ! is_numeric( $row->option_value ) ) {
                    continue;
                }

                if ( $now <= (int) $row->option_value ) {
                    continue;
                }

                // strlen( '_wp_session_expires_' ) === 20
                $session_id = substr( $row->option_name, 20 );

                // Skips the legacy -1 id and anything another plugin owns.
                if ( ! preg_match( '/^[a-f0-9]{32}$/', $session_id ) ) {
                    continue;
                }

                $names[] = $row->option_name;
                $names[] = '_wp_session_' . $session_id;
            }

            $offset += $page;
        } while ( count( $rows ) === $page );

        return $names;
    }
}

if ( ! function_exists( 'wp_session_cleanup' ) ) {
    /**
     * Clean up expired sessions by removing data and their expiration entries from
     * the WordPress options table.
     *
     * Deletes in bounded chunks. Sites that have been accumulating since 2017 hold
     * hundreds of megabytes of these rows, and a single unbounded DELETE is what
     * managed hosts abort part-way — which would leave the site exactly as full as
     * it started. Each chunk that completes is committed, so an interrupted run
     * still makes progress and the next run continues from there.
     *
     * This method should never be called directly and should instead be triggered as part
     * of a scheduled task or cron job.
     *
     * @return void
     */
    function wp_session_cleanup() {
        global $wpdb;

        if ( defined( 'WP_SETUP_CONFIG' ) ) {
            return;
        }

        if ( ! defined( 'WP_INSTALLING' ) ) {
            $expired_sessions = nf_session_collect_expired_option_names();

            if ( ! empty( $expired_sessions ) ) {
                /**
                 * Number of expired sessions removed per DELETE statement.
                 *
                 * @param int $batch_size Sessions per statement.
                 */
                $batch_size = (int) apply_filters( 'ninja_forms_session_cleanup_batch_size', 100 );

                if ( $batch_size < 1 ) {
                    $batch_size = 100;
                }

                // Two option names per session.
                foreach ( array_chunk( $expired_sessions, $batch_size * 2 ) as $chunk ) {
                    $placeholders = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );

                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are generated here; the values go through prepare().
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $wpdb->options WHERE option_name IN ($placeholders)",
                            $chunk
                        )
                    );
                }
            }
        }

        // Allow other plugins to hook in to the garbage collection process.
        do_action( 'wp_session_cleanup' );
    }
}

add_action( 'wp_session_garbage_collection', 'wp_session_cleanup' );

if ( ! function_exists( 'wp_session_register_garbage_collection' ) ) {
    /**
     * Register the garbage collector as a twice daily event.
     *
     * Hooked to `init` rather than `wp`. `wp` only fires on a rendered page view,
     * so a site whose Ninja Forms traffic is admin-ajax and cron — which is every
     * site — never scheduled the collector at all.
     *
     * Re-registers itself whenever the event is missing, so a cleared cron table
     * or a plugin that flushes schedules cannot silently switch collection off.
     *
     * @return void
     */
    function wp_session_register_garbage_collection() {
        if ( ! wp_next_scheduled( 'wp_session_garbage_collection' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'wp_session_garbage_collection' );
        }
    }
}

add_action( 'init', 'wp_session_register_garbage_collection' );
