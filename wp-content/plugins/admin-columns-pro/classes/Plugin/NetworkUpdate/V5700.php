<?php

namespace ACP\Plugin\NetworkUpdate;

use ACP;

class V5700 extends ACP\Plugin\Update\V5700
{

    protected function update_option($name, $value): void
    {
        update_site_option($name, $value);
    }

    protected function get_option($name)
    {
        return get_site_option($name);
    }

    protected function clear_cache_api(): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE 'ac_api_request_%'");
    }

}