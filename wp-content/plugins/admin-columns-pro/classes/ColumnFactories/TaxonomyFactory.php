<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use ACP\ColumnFactory\CustomFieldFactory;
use ACP\ColumnFactory\Taxonomy;
use ACP\TableScreen;

class TaxonomyFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(AC\TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof TableScreen\Taxonomy) {
            return $collection;
        }

        $factories = [
            CustomFieldFactory::class,
            Taxonomy\Id::class,
            Taxonomy\Description::class,
            Taxonomy\CountForPostType::class,
            Taxonomy\Menu::class,
            Taxonomy\TermParent::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}