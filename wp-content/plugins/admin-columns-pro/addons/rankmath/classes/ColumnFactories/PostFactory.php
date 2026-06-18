<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\RankMath\ColumnFactory;

class PostFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post) {
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
            ColumnFactory\Post\CanonicalUrl::class,
            ColumnFactory\Post\Description::class,
            ColumnFactory\Post\FacebookDescription::class,
            ColumnFactory\Post\FacebookTitle::class,
            ColumnFactory\Post\FacebookImage::class,
            ColumnFactory\Post\FacebookShowIconOverlay::class,
            ColumnFactory\Post\FacebookIconOverlay::class,
            ColumnFactory\Post\FocusKeyWord::class,
            ColumnFactory\Post\PillarContent::class,
            ColumnFactory\Post\PrimaryTerm::class,
            ColumnFactory\Post\Score::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}