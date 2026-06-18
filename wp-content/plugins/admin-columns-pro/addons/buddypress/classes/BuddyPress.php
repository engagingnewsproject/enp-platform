<?php

declare(strict_types=1);

namespace ACA\BP;

use AC;
use AC\DI\Container;
use AC\Services;
use ACA\BP\TableScreen\TableIds;
use ACP;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;
use ACP\Editing\Strategy\AggregateFactory;
use ACP\Service\IntegrationStatus;

final class BuddyPress implements Addon
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
        return 'buddypress';
    }

    public function register(): void
    {
        if ( ! class_exists('BuddyPress', false)) {
            return;
        }

        AC\TableScreenFactory\Aggregate::add(new TableScreen\GroupFactory());
        AC\TableScreenFactory\Aggregate::add(new TableScreen\ActivityFactory());

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