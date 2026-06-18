<?php

namespace ACP\Updates;

use AC\Registerable;
use ACP\AdminColumnsPro;
use ACP\Asset\Script\PluginUpdatesCheck;
use ACP\Transient\TimeTransientFactory;

class PeriodicUpdateCheck implements Registerable
{

    private AdminColumnsPro $plugin;

    public function __construct(AdminColumnsPro $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts(): void
    {
        $cache = TimeTransientFactory::create_update_check();

        if ($cache->is_expired()) {
            $script = new PluginUpdatesCheck(
                $this->plugin->get_location()->with_suffix('assets/core/js/update-plugins-check.js')
            );
            $script->enqueue();
        }
    }

}