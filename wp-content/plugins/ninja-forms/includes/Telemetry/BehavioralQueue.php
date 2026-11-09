<?php

/** Per-event options avoid the shared read/append/write queue race. Never autoload. */
class NF_Telemetry_BehavioralQueue
{
    const PREFIX = 'nf_be_event_';
    const HOOK = 'nf_behavioral_delivery';
    const CAPACITY = 1000;
    const LEGACY_OPTION = 'nf_be_data';
    const LEGACY_ATTEMPTS = 'nf_be_legacy_attempts';
    const LEGACY_MAX_ATTEMPTS = 3;
    const LEGACY_MAX_BYTES = 1048576;

    public static function enqueue(array $event)
    {
        $key = self::PREFIX . $event['event_id'];
        $existing = get_option($key, null);
        if ($existing !== null) {
            self::schedule(300);
            return $existing === $event;
        }
        // Soft cap: one indexed COUNT, never a read of every queued event. Concurrent
        // writers can pass the check together, so the queue may briefly exceed it.
        if (self::count() >= self::CAPACITY) {
            return false;
        }
        $saved = add_option($key, $event, '', false);
        if ($saved) {
            self::schedule(300);
        }
        return $saved || get_option($key, null) === $event;
    }

    public static function count()
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like(self::PREFIX) . '%'
        ));
    }

    /**
     * Names and values come from one query, so a persistent object cache cannot serve a
     * stale copy of an event that another server has just written or acknowledged.
     */
    public static function pending($limit = 100)
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id LIMIT %d",
            $wpdb->esc_like(self::PREFIX) . '%', $limit
        ));
        $events = [];
        foreach ((array) $rows as $row) {
            $event = maybe_unserialize($row->option_value);
            if (is_array($event)) {
                $events[] = $event;
            }
        }
        return $events;
    }

    /**
     * Trust boundary: the authenticated collector's accepted_event_ids decide deletion. Only
     * IDs from the batch just sent can match, so a faulty acknowledgment affects that batch alone.
     */
    public static function acknowledge(array $sent, array $response)
    {
        if (!isset($response['accepted_event_ids']) || !is_array($response['accepted_event_ids'])) {
            return;
        }
        foreach ($sent as $event) {
            if (in_array($event['event_id'], $response['accepted_event_ids'], true)) {
                // Delete only this immutable event, never a concurrently appended batch.
                delete_option(self::PREFIX . $event['event_id']);
            }
        }
    }

    public static function schedule($delay)
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_single_event(time() + $delay, self::HOOK);
        }
    }

    /**
     * The pre-v2 backlog sent with the monthly report. Nothing appends to it any more, so it
     * can only shrink. A backlog too large to be accepted is dropped rather than resent.
     */
    public static function legacyBacklog()
    {
        $backlog = get_option(self::LEGACY_OPTION, []);
        if (!is_array($backlog) || strlen((string) wp_json_encode($backlog)) > self::LEGACY_MAX_BYTES) {
            self::discardLegacy();
            return [];
        }
        return $backlog;
    }

    /**
     * Record the outcome of a monthly report that carried the backlog. After three reports
     * without an acknowledgment it is dropped, so a site the collector never accepts cannot
     * keep it forever.
     */
    public static function settleLegacy(array $sent, $response)
    {
        if (!$sent) {
            return;
        }
        if (is_array($response) && self::acknowledgeLegacy($sent, $response)) {
            delete_option(self::LEGACY_ATTEMPTS);
            return;
        }
        $attempts = (int) get_option(self::LEGACY_ATTEMPTS, 0) + 1;
        if ($attempts >= self::LEGACY_MAX_ATTEMPTS) {
            self::discardLegacy();
            return;
        }
        update_option(self::LEGACY_ATTEMPTS, $attempts, false);
    }

    public static function discardLegacy()
    {
        delete_option(self::LEGACY_OPTION);
        delete_option(self::LEGACY_ATTEMPTS);
    }

    /** Older versions autoloaded the backlog; stop loading it on every request. */
    public static function unloadLegacy()
    {
        if (array_key_exists(self::LEGACY_OPTION, wp_load_alloptions())) {
            wp_set_option_autoload(self::LEGACY_OPTION, false);
        }
    }

    /** Remove a legacy snapshot only if no writer has changed it in the meantime. */
    public static function acknowledgeLegacy(array $sent, array $response)
    {
        if (empty($response['behavioral_batch_hash']) || !is_string($response['behavioral_batch_hash']) ||
            !hash_equals(hash('sha256', wp_json_encode($sent)), $response['behavioral_batch_hash'])) {
            return false;
        }
        global $wpdb;
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = BINARY %s",
            self::LEGACY_OPTION, maybe_serialize($sent)
        ));
        wp_cache_delete(self::LEGACY_OPTION, 'options');
        wp_cache_delete('alloptions', 'options');
        return $deleted > 0;
    }
}
