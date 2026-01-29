<?php

declare(strict_types=1);

namespace ACA\RankMath;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI\Container;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;
use ACA\RankMath\Filtering\DefaultActiveFilters;
use ACP;
use ACP\Service\IntegrationStatus;

class RankMath implements Registerable
{

    private Absolute $location;

    private Container $container;

    public function __construct(Absolute $location, Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
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