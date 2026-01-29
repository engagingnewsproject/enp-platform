<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Asset\Location;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI\Container;
use ACP\AdminColumnsPro;

final class Addon implements Registerable
{

    private Location\Absolute $location;

    private Container $container;

    public function __construct(AdminColumnsPro $plugin, Container $container)
    {
        $this->location = $plugin->get_location();
        $this->container = $container;
    }

    public function register(): void
    {
        $this->create_services()
             ->register();
    }

    private function create_services(): Services
    {
        return new Services([
            $this->container->make(Service\Assets::class, ['location' => $this->location]),
            $this->container->make(Service\ListScreenSettings::class),
        ]);
    }

}