<?php

declare(strict_types=1);

namespace ACA\MetaBox;

use AC;
use AC\TableScreen;
use ACA\MetaBox\Entity\Relation;
use MB_Relationships_API;

class RelationshipRepository
{

    public function find(string $column_type, TableScreen $table_screen): ?Relation
    {
        if ( ! class_exists('MB_Relationships_API', false) ||
             ! method_exists('MB_Relationships_API', 'get_all_relationships')) {
            return null;
        }

        $relation_type = $this->get_relation_type($column_type);
        $relation_id = $this->get_relation_id($column_type);

        if ( ! $relation_type || ! $relation_id) {
            return null;
        }

        foreach ($this->find_all($table_screen) as $relation) {
            if ($relation_type === $relation->get_type() && $relation_id === $relation->get_id()) {
                return $relation;
            }
        }

        return null;
    }

    private function get_relation_type(string $column_type): ?string
    {
        if (str_starts_with($column_type, 'from__')) {
            return 'from';
        }
        if (str_starts_with($column_type, 'to__')) {
            return 'to';
        }

        return null;
    }

    private function get_relation_id(string $column_type): ?string
    {
        if (str_starts_with($column_type, 'from__')) {
            return substr($column_type, strlen('from__'));
        }
        if (str_starts_with($column_type, 'to__')) {
            return substr($column_type, strlen('to__'));
        }

        return null;
    }

    /**
     * @return Relation[]
     */
    public function find_all(TableScreen $table_screen): array
    {
        if ( ! class_exists('MB_Relationships_API', false) ||
             ! method_exists('MB_Relationships_API', 'get_all_relationships')) {
            return [];
        }

        $results = [];

        foreach (MB_Relationships_API::get_all_relationships() as $relation) {
            if ($this->is_relation_type_for_table_screen($relation->from, $table_screen)) {
                $results[] = new Relation($relation, 'from');
            }
            if ($this->is_relation_type_for_table_screen($relation->to, $table_screen)) {
                $results[] = new Relation($relation, 'to');
            }
        }

        return $results;
    }

    private function is_relation_type_for_table_screen(array $relation, TableScreen $table_screen): bool
    {
        $object_type = $relation['object_type'] ?? null;

        switch (true) {
            case $table_screen instanceof AC\PostType:
                return 'post' === $object_type &&
                       (string)$table_screen->get_post_type() === $relation['field']['post_type'];
            case $table_screen instanceof AC\TableScreen\User;
                return 'user' === $object_type;
            case $table_screen instanceof AC\Taxonomy;
                return 'term' === $object_type;
            default:
                return false;
        }
    }

}