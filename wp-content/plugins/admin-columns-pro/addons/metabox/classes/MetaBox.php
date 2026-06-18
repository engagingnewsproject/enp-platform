<?php

declare(strict_types=1);

namespace ACA\MetaBox;

use AC;
use AC\DI\Container;
use AC\Services;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;

final class MetaBox implements Addon
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
        return 'metabox';
    }

    public function register(): void
    {
        if ( ! $this->is_metabox_active()) {
            return;
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\FieldFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\MetaBoxFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PostTypeFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\RelationFactory::class));

        $this->create_services()->register();
    }

    private function is_metabox_active(): bool
    {
        if (class_exists('RWMB_Loader', false)) {
            return true;
        }

        // All-in-one loader needs MetaBox to be disabled, all logic is loaded in the `admin_init` hook
        if (class_exists('MBAIO\Loader', false)) {
            return true;
        }

        return false;
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroups($this->location),
            new Service\QuickAdd(),
            new Service\Scripts($this->location),
            new IntegrationStatus('ac-addon-metabox'),
        ]);
    }

}