<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA;
use ACP;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;

class Subscriptions implements Registerable
{

    private bool $use_hpos;

    private DI\Container $container;

    public function __construct(bool $use_hpos, DI\Container $container)
    {
        $this->use_hpos = $use_hpos;
        $this->container = $container;

        $column_factories = [
            ColumnFactories\Original\UserFactory::class,
            ColumnFactories\ProductFactory::class,
            ColumnFactories\UserFactory::class,
        ];

        AC\Admin\MenuGroupFactory\Aggregate::add(new TableScreen\MenuGroupFactory());

        if ($this->use_hpos) {
            ACP\Export\Strategy\AggregateFactory::add(
                $this->container->get(Export\Strategy\OrderSubscriptionFactory::class)
            );
            AC\TableScreenFactory\Aggregate::add(new TableScreen\OrderSubscriptionFactory());

            $column_factories[] = ColumnFactories\Original\OrderSubscriptionFactory::class;
            $column_factories[] = ColumnFactories\OrderSubscriptionFactory::class;
            $column_factories[] = ColumnFactories\OrderFactory::class;

            ACP\Query\QueryRegistry::add($container->get(Query\OrderSubscriptionFactory::class));
        } else {
            $column_factories[] = ColumnFactories\Original\ShopSubscriptionFactory::class;
            $column_factories[] = ColumnFactories\ShopSubscriptionFactory::class;
        }

        ACP\Filtering\TableScreenFactory::register(
            TableScreen\OrderSubscription::class,
            ACA\WC\Filtering\Table\Order::class
        );

        ACP\Search\TableMarkupFactory::register(
            TableScreen\OrderSubscription::class,
            Search\OrderSubscription::class
        );

        if ($this->use_hpos) {
            AC\TableScreen\TableRowsFactory\Aggregate::add(new TableScreen\TableRowsFactory());
        }

        foreach ($column_factories as $factory) {
            AC\ColumnFactories\Aggregate::add($this->container->make($factory));
        }

        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                [
                    'factory' => $this->container->get(
                        ACA\WC\Subscriptions\ListTable\ManageValue\OrderSubscriptionServiceFactory::class
                    ),
                ]
            )
        );
        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\OrderSubscriptionFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\OrderSubscriptionFactory::class));
    }

    private function create_services(): Services
    {
        $services = new Services();

        $services_fqn[] = Service\Columns::class;
        $services_fqn[] = Service\TableScreen::class;

        foreach ($services_fqn as $service) {
            $services->add($this->container->get($service));
        }

        return $services;
    }

    public function register(): void
    {
        $this->create_services()
             ->register();
    }

}