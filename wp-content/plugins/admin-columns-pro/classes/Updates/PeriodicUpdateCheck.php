<?php

namespace ACP\Updates;

use AC\Asset\Location\Absolute;
use AC\Registerable;
use ACP\Asset\Script\PluginUpdatesCheck;
use ACP\Transient\TimeTransientFactory;

class PeriodicUpdateCheck implements Registerable
{

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts(): void
    {
        $cache = TimeTransientFactory::create_update_check();

        if ($cache->is_expired()) {
            $script = new PluginUpdatesCheck($this->location->with_suffix('assets/core/js/update-plugins-check.js'));
            $script->enqueue();
        }
    }

}