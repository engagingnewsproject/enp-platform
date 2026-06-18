<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\JetEngine\ColumnFactory;
use ACA\JetEngine\Utils\Api;
use Jet_Engine;
use LogicException;

final class RelationFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        foreach ($this->get_relations($table_screen) as $relation) {
            $is_parent = $this->is_parent($table_screen, $relation);
            $argument_key = $is_parent
                ? 'child_object'
                : 'parent_object';

            $meta_type = explode('::', $relation->get_args($argument_key))[0];

            $mapping = [
                'posts' => ColumnFactory\Relation\PostRelationFactory::class,
                'terms' => ColumnFactory\Relation\TermRelationFactory::class,
                'mix'   => ColumnFactory\Relation\UserRelationFactory::class,
            ];

            $arguments = [
                'column_type' => 'je_relation' . $relation->get_id(),
                'label'       => $relation->get_relation_name(),
                'relation'    => $relation,
                'is_parent'   => $is_parent,
            ];

            $factory_class = array_key_exists($meta_type, $mapping)
                ? $mapping[$meta_type]
                : ColumnFactory\Relation\RelationFactory::class;

            $collection->add(
                new AC\Type\ColumnFactoryDefinition($factory_class, $arguments)
            );
        }

        return $collection;
    }

    private function is_parent(TableScreen $table_screen, Jet_Engine\Relations\Relation $relation): bool
    {
        switch (true) {
            case $table_screen instanceof TableScreen\User:
                return $relation->is_parent('mix', 'users');
            case $table_screen instanceof AC\PostType:
                return $relation->is_parent('posts', (string)$table_screen->get_post_type());
            case $table_screen instanceof AC\Taxonomy:
                return $relation->is_parent('terms', (string)$table_screen->get_taxonomy());
            default:
                return false;
        }
    }

    private function get_relations(AC\TableScreen $table_screen): array
    {
        switch (true) {
            case $table_screen instanceof AC\TableScreen\User:
                return $this->get_relations_for_meta_type('mix', 'users');
            case $table_screen instanceof AC\PostType:
                return $this->get_relations_for_meta_type('posts', (string)$table_screen->get_post_type());
            case $table_screen instanceof AC\Taxonomy:
                return $this->get_relations_for_meta_type('terms', (string)$table_screen->get_taxonomy());
            default:
                return [];
        }
    }

    /**
     * @return Jet_Engine\Relations\Relation[]
     */
    private function get_relations_for_meta_type(string $type, string $value): array
    {
        if ( ! in_array($type, ['posts', 'terms', 'mix'], true)) {
            throw new LogicException('Type is not supported');
        }

        $relations = [];

        foreach (Api::relations()->get_active_relations() as $relation) {
            if ( ! $relation instanceof Jet_Engine\Relations\Relation) {
                continue;
            }

            $separator = sprintf('%s::', $type);

            $child = explode($separator, $relation->get_args('child_object'));
            $child = count($child) === 2 ? $child[1] : '';

            $parent = explode($separator, $relation->get_args('parent_object'));
            $parent = count($parent) === 2 ? $parent[1] : '';

            if ( ! $relation->get_args('is_legacy') && in_array($value, [$child, $parent])) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

}