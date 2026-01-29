<?php

declare(strict_types=1);

namespace ACA\MetaBox;

use AC;
use AC\Registerable;
use AC\Services;
use ACA\MetaBox\TableScreen\MenuGroupFactory;
use ACP\Service\IntegrationStatus;

final class MetaBox implements Registerable
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
        if ( ! $this->is_metabox_active()) {
            return;
        }

        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());

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