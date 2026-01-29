<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactories;

use AC;
use AC\Collection;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA;
use ACA\Types\ColumnFactory;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\RelationshipQuery\RelationshipQuery;
use Toolset_Element_Domain;

class RelationFactory implements AC\ColumnFactoryCollectionFactory
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $columns = new Collection\ColumnFactories();

        if ( ! $table_screen instanceof AC\PostType) {
            return $columns;
        }

        if ( ! apply_filters('toolset_is_m2m_enabled', false)) {
            return $columns;
        }

        $query = new RelationshipQuery();

        $query->add(
            $query->has_domain_and_type(
                (string)$table_screen->get_post_type(),
                Toolset_Element_Domain::POSTS
            )
        );

        $relationships = $query->get_results();

        foreach ($relationships as $relationship) {
            $arguments = [
                'column_type'  => 'column-types_relationship_' . $relationship->get_slug(),
                'label'        => sprintf(
                    '%s: %s',
                    __('Relationship', 'codepress-admin-colums'),
                    $relationship->get_display_name()
                ),
                'relationship' => $relationship,
            ];

            if ($this->is_parent_relation_type($relationship, (string)$table_screen->get_post_type())) {
                $factory = $this->container->make(ColumnFactory\Post\ParentRelationship::class, $arguments);
            } else {
                $factory = $this->container->make(ColumnFactory\Post\ChildRelationship::class, $arguments);
            }

            $columns->add(
                $factory
            );
        }

        return $columns;
    }

    private function is_parent_relation_type(IToolset_Relationship_Definition $relationship, string $post_type): bool
    {
        return in_array($post_type, $relationship->get_parent_type()->get_types());
    }

}