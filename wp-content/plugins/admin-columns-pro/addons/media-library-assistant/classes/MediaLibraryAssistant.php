<?php

declare(strict_types=1);

namespace ACA\MLA;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;
use ACA\MLA\Export\StrategyFactory;
use ACA\MLA\TableScreen\MenuGroupFactory;
use ACA\MLA\TableScreen\TableRowsFactory;
use ACP;
use ACP\Service\IntegrationStatus;

class MediaLibraryAssistant implements Registerable
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
        if ( ! defined('MLA_PLUGIN_PATH')) {
            return;
        }

        $column_factories = [
            ColumnFactories\Original\MlaCustomFactory::class,
            ColumnFactories\Original\MlaCustomFieldsFactory::class,
            ColumnFactories\Original\MlaTaxonomiesFactory::class,
        ];

        foreach ($column_factories as $factory) {
            AC\ColumnFactories\Aggregate::add($this->container->get($factory));
        }

        AC\Admin\MenuGroupFactory\Aggregate::add($this->container->get(MenuGroupFactory::class));
        AC\TableScreen\TableRowsFactory\Aggregate::add($this->container->get(TableRowsFactory::class));
        ACP\Editing\Strategy\AggregateFactory::add($this->container->make(Editing\StrategyFactory::class));
        ACP\Export\Strategy\AggregateFactory::add($this->container->make(StrategyFactory::class));

        AC\Service\ManageValue::add(
            $this->container->make(
                ACP\ConditionalFormat\ManageValue\RenderableServiceFactory::class,
                [
                    'factory' => $this->container->get(
                        AC\ThirdParty\MediaLibraryAssistant\TableScreen\ManageValueServiceFactory::class
                    ),
                ]
            )
        );

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroup(),
            new Service\Export(),
            new Service\TableScreen($this->location),
            new IntegrationStatus('ac-addon-media-library-assistant'),
        ]);
    }

}