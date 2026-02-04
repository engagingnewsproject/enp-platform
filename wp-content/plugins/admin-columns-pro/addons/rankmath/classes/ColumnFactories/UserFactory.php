<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\RankMath\ColumnFactory;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Robots\Index::class,
            ColumnFactory\Robots\NoIndex::class,
            ColumnFactory\Robots\NoFollow::class,
            ColumnFactory\Robots\NoImageIndex::class,
            ColumnFactory\Robots\NoArchive::class,
            ColumnFactory\Robots\NoSnippet::class,
            ColumnFactory\Robots\Robots::class,

            ColumnFactory\User\CanonicalUrl::class,
            ColumnFactory\User\FacebookProfileUrl::class,
            ColumnFactory\User\TwitterUsername::class,
            ColumnFactory\User\ProfileUrls::class,
            ColumnFactory\User\FocusKeyword::class,
            ColumnFactory\User\RankMathTitle::class,
            ColumnFactory\User\RankMathDescription::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}