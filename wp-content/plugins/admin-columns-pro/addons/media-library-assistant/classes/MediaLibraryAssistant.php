<?php

declare(strict_types=1);

namespace ACA\MLA;

use AC;
use AC\DI\Container;
use AC\Services;
use ACA\MLA\Export\StrategyFactory;
use ACA\MLA\TableScreen\TableRowsFactory;
use ACP;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;

class MediaLibraryAssistant implements Addon
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
        return 'media-library-assistant';
    }

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