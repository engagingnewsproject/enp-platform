<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Type\TableId;
use ACP\ColumnFactory\NetworkSite;

class NetworkSiteFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen->get_id()->equals(new TableId('wp-ms_sites'))) {
            return $collection;
        }

        $factories = [
            AC\ColumnFactory\ActionsFactory::class,
            NetworkSite\BlogIdFactory::class,
            NetworkSite\BlogNameFactory::class,
            NetworkSite\CommentCountFactory::class,
            NetworkSite\DomainFactory::class,
            NetworkSite\OptionsFactory::class,
            NetworkSite\PathFactory::class,
            NetworkSite\PluginsFactory::class,
            NetworkSite\PostCountFactory::class,
            NetworkSite\SiteIdFactory::class,
            NetworkSite\StatusFactory::class,
            NetworkSite\ThemeFactory::class,
            NetworkSite\UploadSpaceFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}