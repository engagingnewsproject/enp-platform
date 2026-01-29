<?php

declare(strict_types=1);

namespace ACA\JetEngine;

use AC;
use AC\Registerable;
use AC\Services;
use ACA\JetEngine\TableScreen\MenuGroupFactory;
use ACP\Service\IntegrationStatus;

final class JetEngine implements Registerable
{

    private AC\Asset\Location\Absolute $location;

    private AC\Vendor\DI\Container $container;

    public function __construct(AC\Asset\Location\Absolute $location, AC\Vendor\DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    public function register(): void
    {
        if ( ! class_exists('Jet_Engine', false) || ! $this->check_minimum_jet_engine_version()) {
            return;
        }

        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\MetaFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\RelationFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroups($this->location),
            new IntegrationStatus('ac-addon-jetengine'),
        ]);
    }

    private function check_minimum_jet_engine_version(): bool
    {
        $jet_engine = jet_engine();

        return $jet_engine && version_compare($jet_engine->get_version(), '2.11.0', '>=');
    }

}