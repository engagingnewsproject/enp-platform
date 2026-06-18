<?php

declare(strict_types=1);

namespace ACA\JetEngine;

use AC;
use AC\DI\Container;
use AC\Services;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;

final class JetEngine implements Addon
{

    private AC\Asset\Location $location;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->location = $container->get(AdminColumnsPro::class)->get_addon_location($this->get_id());
    }

    public function get_id(): string
    {
        return 'jetengine';
    }

    public function register(): void
    {
        if ( ! class_exists('Jet_Engine', false) || ! $this->check_minimum_jet_engine_version()) {
            return;
        }

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