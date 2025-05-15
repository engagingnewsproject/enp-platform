<?php

namespace ACP\Service;

use AC\Capabilities;
use AC\Registerable;
use ACP\ActivationTokenFactory;
use ACP\Transient\TimeTransientFactory;
use ACP\Updates\PluginDataUpdater;

class ForcePluginUpdate implements Registerable
{

    private ActivationTokenFactory $activation_token_factory;

    private PluginDataUpdater $updater;

    public function __construct(
        ActivationTokenFactory $activation_token_factory,
        PluginDataUpdater $updater
    ) {
        $this->activation_token_factory = $activation_token_factory;
        $this->updater = $updater;
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'force_plugin_updates']);
        add_action('load-plugins.php', [$this, 'force_plugin_updates_cached'], 9);
        add_action('load-update-core.php', [$this, 'force_plugin_updates_cached'], 9);
        add_action('load-update.php', [$this, 'force_plugin_updates_cached'], 9);
    }

    private function is_force_check_request(): bool
    {
        global $pagenow;

        return '1' === filter_input(INPUT_GET, 'force-check')
               && $pagenow === 'update-core.php'
               && current_user_can(Capabilities::MANAGE);
    }

    /**
     * Forces to check for updates on a manual request
     */
    public function force_plugin_updates(): void
    {
        if ( ! $this->is_force_check_request()) {
            return;
        }

        $this->update_plugin_data();
    }

    /**
     * Forces to check for updates on plugins page
     */
    public function force_plugin_updates_cached(): void
    {
        $cache = TimeTransientFactory::create_update_check_hourly();

        if ($cache->is_expired()) {
            $cache->save();

            $this->update_plugin_data();
        }
    }

    private function update_plugin_data(): void
    {
        $this->updater->update($this->activation_token_factory->create());
    }

}