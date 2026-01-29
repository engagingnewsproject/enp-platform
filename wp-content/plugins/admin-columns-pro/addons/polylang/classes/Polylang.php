<?php

declare(strict_types=1);

namespace ACA\Polylang;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;

class Polylang implements Registerable
{

    private DI\Container $container;

    public function __construct(AC\Asset\Location\Absolute $location, DI\Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function register(): void
    {
        if ( ! defined('POLYLANG_VERSION')) {
            return;
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnTypesFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\Columns(),
            new Service\Table(),
        ]);
    }

}