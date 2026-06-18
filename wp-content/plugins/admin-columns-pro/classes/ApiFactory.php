<?php

declare(strict_types=1);

namespace ACP;

use ACP\Access\Platform;

class ApiFactory
{

    private AdminColumnsPro $plugin;

    public function __construct(AdminColumnsPro $plugin)
    {
        $this->plugin = $plugin;
    }

    public function create(): API
    {
        $api = new API();
        $api
            ->set_url('https://www.admincolumns.com')
            ->set_proxy('https://api.admincolumns.com')
            ->set_request_meta($this->get_meta());

        do_action('acp/api', $api);

        return $api;
    }

    private function get_meta(): array
    {
        $meta = [
            'php_version' => PHP_VERSION,
            'acp_version' => (string)$this->plugin->get_version(),
            'is_network'  => $this->plugin->is_network_active(),
        ];

        if (Platform::is_local()) {
            $meta['ip'] = '127.0.0.1';
        }

        return $meta;
    }

}