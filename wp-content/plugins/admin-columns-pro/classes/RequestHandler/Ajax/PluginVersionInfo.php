<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce\Ajax;
use AC\Plugin\Version;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\AdminColumnsPro;
use ACP\Type\Url\Changelog;

final class PluginVersionInfo implements RequestAjaxHandler
{

    private AdminColumnsPro $plugin;

    public function __construct(AdminColumnsPro $plugin)
    {
        $this->plugin = $plugin;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            wp_send_json_error();
        }

        $request = new Request();

        if ( ! (new Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $plugin_update = $this->get_plugin_update();
        $update_ready = $plugin_update && $plugin_update['package'] && current_user_can('update_plugins');
        $changelog = $this->plugin->is_network_active()
            ? Changelog::create_network($this->plugin->get_dirname())
            : Changelog::create_site($this->plugin->get_dirname());

        wp_send_json_success([
            'plugin_update_basename' => $this->plugin->get_basename(),
            'plugin_update_slug'     => $this->plugin->get_dirname(),
            'plugin_update_nonce'    => wp_create_nonce('updates'),
            'plugin_update_ready'    => $update_ready,
            'current_version'        => (string)$this->plugin->get_version(),
            'available_version'      => $plugin_update ? $plugin_update['version'] : null,
            'changelog_link'         => (string)$changelog,
        ]);
    }

    private function get_plugin_update(): ?array
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $update = get_plugin_updates()[$this->plugin->get_basename()]->update ?? null;

        if ( ! $update || ! $update->new_version) {
            return null;
        }

        $version = new Version($update->new_version);

        if ( ! $version->is_valid() || $version->is_lte($this->plugin->get_version())) {
            return null;
        }

        return [
            'version' => $update->new_version,
            'package' => $update->package ?? null,
        ];
    }

}