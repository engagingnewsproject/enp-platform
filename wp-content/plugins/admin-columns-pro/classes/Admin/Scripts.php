<?php

namespace ACP\Admin;

use AC\Asset;
use AC\Asset\Enqueueable;
use AC\Entity\Plugin;
use AC\Registerable;
use ACP\Access\PermissionsStorage;
use ACP\ActivationTokenFactory;
use ACP\Asset\Script;
use ACP\Transient\TimeTransientFactory;

class Scripts implements Registerable
{

    private $location;

    private $permission_storage;

    private $plugin;

    private ActivationTokenFactory $token_factory;

    public function __construct(
        Asset\Location\Absolute $location,
        PermissionsStorage $permission_storage,
        Plugin $plugin,
        ActivationTokenFactory $token_factory
    ) {
        $this->location = $location;
        $this->permission_storage = $permission_storage;
        $this->plugin = $plugin;
        $this->token_factory = $token_factory;
    }

    public function register(): void
    {
        add_action('ac/admin_scripts', [$this, 'register_usage_limiter']);
        add_action('admin_enqueue_scripts', [$this, 'register_daily_license_check']);
    }

    public function register_usage_limiter(): void
    {
        if ($this->permission_storage->retrieve()->has_usage_permission()) {
            return;
        }

        $assets = [
            new Asset\Style('acp-usage-limiter', $this->location->with_suffix('assets/core/css/usage-limiter.css')),
            new Asset\Script('acp-usage-limiter', $this->location->with_suffix('assets/core/js/usage-limiter.js')),
        ];

        array_map([$this, 'enqueue'], $assets);
    }

    public function register_daily_license_check(): void
    {
        $activation_token = $this->token_factory->create();

        if ( ! $activation_token) {
            return;
        }

        $cache = TimeTransientFactory::create_license_check_daily();

        if ( ! $cache->is_expired()) {
            return;
        }

        $script = new Script\LicenseCheck($this->location->with_suffix('assets/core/js/license-check.js'));
        $script->enqueue();
    }

    private function enqueue(Enqueueable $assets)
    {
        $assets->enqueue();
    }

}