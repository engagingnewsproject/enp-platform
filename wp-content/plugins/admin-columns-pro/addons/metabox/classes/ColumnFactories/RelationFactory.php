<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Vendor\DI\Container;
use ACA\MetaBox\ColumnFactory;
use ACA\MetaBox\RelationshipRepository;

class RelationFactory extends AC\ColumnFactories\BaseFactory
{

    private RelationshipRepository $relationship_repository;

    public function __construct(Container $container, RelationshipRepository $relationship_repository)
    {
        parent::__construct($container);

        $this->relationship_repository = $relationship_repository;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        foreach ($this->relationship_repository->find_all($table_screen) as $relationship) {
            $properties = [
                'column_type' => $relationship->get_type() . '__' . $relationship->get_id(),
                'relation'    => $relationship,
            ];

            switch ((string)$relationship->get_related_meta_type()) {
                case 'user':
                    $collection->add(
                        new ColumnFactoryDefinition(ColumnFactory\Relation\UserRelation::class, $properties)
                    );
                    break;
                case 'post':
                    $collection->add(
                        new ColumnFactoryDefinition(ColumnFactory\Relation\PostRelation::class, $properties)
                    );
                    break;
                case 'term':
                    $collection->add(
                        new ColumnFactoryDefinition(ColumnFactory\Relation\TermRelation::class, $properties)
                    );
                    break;
            }
        }

        return $collection;
    }

}