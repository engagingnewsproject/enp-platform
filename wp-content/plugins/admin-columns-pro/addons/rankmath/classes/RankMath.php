<?php

declare(strict_types=1);

namespace ACA\RankMath;

use AC;
use AC\Asset\Location\Absolute;
use AC\DI\Container;
use AC\Services;
use ACA\RankMath\Filtering\DefaultActiveFilters;
use ACP;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;

class RankMath implements Addon
{

    private Absolute $location;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->location = $container->get(AdminColumnsPro::class)->get_addon_location($this->get_id());
    }

    public function get_id(): string
    {
        return 'rankmath';
    }

    public function register(): void
    {
        if ( ! class_exists('RankMath')) {
            return;
        }

        ACP\Filtering\DefaultFilters\Aggregate::add($this->container->get(DefaultActiveFilters::class));

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\PostFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PostFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\UserFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroup($this->location),
            new IntegrationStatus('ac-addon-rankmath'),
        ]);
    }

}