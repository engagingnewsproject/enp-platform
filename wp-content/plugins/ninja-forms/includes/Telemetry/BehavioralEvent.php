<?php

class NF_Telemetry_BehavioralEvent
{
    const PAGES = ['ninja-forms', 'nf-submissions', 'nf-settings', 'nf-import-export', 'nf-system-status'];
    const ACTIONS = ['navigation_link', 'navigate_ninja-forms', 'navigate_nf-submissions',
        'navigate_nf-settings', 'navigate_nf-import-export', 'navigate_nf-system-status', 'add_button', 'cancel_button'];

    public static function valid($event)
    {
        $keys = ['event_id', 'session_id', 'schema_version', 'client_version', 'page', 'clicks',
            'start_session_time', 'end_session_time', 'end_session_motive'];
        if (!is_array($event) || array_diff($keys, array_keys($event)) || array_diff(array_keys($event), $keys)) {
            return false;
        }
        foreach (['event_id', 'session_id'] as $key) {
            if (!is_string($event[$key]) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $event[$key])) {
                return false;
            }
        }
        if (!is_string($event['client_version']) || !preg_match('/^[0-9A-Za-z.\-+]{1,32}$/D', $event['client_version'])
            || $event['schema_version'] !== 2 || !in_array($event['page'], self::PAGES, true)
            || !in_array($event['end_session_motive'], ['hidden', 'page_change', 'checkpoint', 'idle'], true)
            || !is_int($event['start_session_time']) || !is_int($event['end_session_time'])
            || $event['start_session_time'] < 1577836800000
            || $event['end_session_time'] < $event['start_session_time']
            || $event['end_session_time'] > (time() + 300) * 1000
            || !is_array($event['clicks']) || count($event['clicks']) > 100) {
            return false;
        }
        foreach ($event['clicks'] as $click) {
            if (!is_array($click) || array_keys($click) !== [0, 1] || !in_array($click[0], self::ACTIONS, true)
                || !is_int($click[1]) || $click[1] < $event['start_session_time'] || $click[1] > $event['end_session_time']) {
                return false;
            }
        }
        return true;
    }
}
