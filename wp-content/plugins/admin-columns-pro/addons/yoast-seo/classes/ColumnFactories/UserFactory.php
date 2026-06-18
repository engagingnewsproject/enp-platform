<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use ACA\YoastSeo\ColumnFactory;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return $collection;
        }

        $factories = [
            ColumnFactory\User\AuthorMetaDescriptionFactory::class,
            ColumnFactory\User\AuthorMetaTitleFactory::class,
            ColumnFactory\User\DisableReadabilityAnalysisFactory::class,
            ColumnFactory\User\DisableSeoAnalysisFactory::class,
            ColumnFactory\User\NoIndexAuthorFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}