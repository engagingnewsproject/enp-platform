<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use ACA\YoastSeo\ColumnFactory;
use ACP;

class TaxonomyFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Taxonomy\CanonicalUrlFactory::class,
            ColumnFactory\Taxonomy\FocusKeywordFactory::class,
            ColumnFactory\Taxonomy\IncludeInSitemapFactory::class,
            ColumnFactory\Taxonomy\MetaDescriptionFactory::class,
            ColumnFactory\Taxonomy\MetaTitleFactory::class,
            ColumnFactory\Taxonomy\NoIndexFactory::class,
            ColumnFactory\Taxonomy\RelatedKeyphrases::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}