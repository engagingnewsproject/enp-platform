<?php

declare(strict_types=1);

namespace ACA\BP;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;
use ACA\BP\TableScreen\MenuGroupFactory;
use ACA\BP\TableScreen\TableIds;
use ACP;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;
use ACP\Editing\Strategy\AggregateFactory;
use ACP\Service\IntegrationStatus;

final class BuddyPress implements Registerable
{

    private AC\Asset\Location\Absolute $location;

    private DI\Container $container;

    public function __construct(AC\Asset\Location\Absolute $location, DI\Container $container)
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
        if ( ! class_exists('BuddyPress', false)) {
            return;
        }

        AC\TableScreenFactory\Aggregate::add(new TableScreen\GroupFactory());
        AC\TableScreenFactory\Aggregate::add(new TableScreen\ActivityFactory());

        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());
        AC\TableIdsFactory\Aggregate::add(new TableIds());
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\GroupFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\UserFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\ActivityFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\GroupFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\ProfileFieldsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\UserFactory::class));

        AC\TableScreen\TableRowsFactory\Aggregate::add(new TableScreen\TableRowsFactory());

        AggregateFactory::add($this->container->get(Editing\Strategy\GroupFactory::class));

        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                ['factory' => $this->container->get(ListTable\ManageValue\GroupServiceFactory::class)]
            ),
        );
        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeadings\GroupFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeadings\GroupFactory::class));

        ACP\Export\Strategy\AggregateFactory::add($this->container->get(Export\Strategy\GroupFactory::class));

        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                ['factory' => $this->container->get(ListTable\ManageValue\ActivityServiceFactory::class)]
            ),
        );
        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeadings\ActivityFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeadings\ActivityFactory::class));

        $this->create_services()
             ->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroups($this->location),
            new Service\Table($this->location),
            new IntegrationStatus('ac-addon-buddypress'),
        ]);
    }

}