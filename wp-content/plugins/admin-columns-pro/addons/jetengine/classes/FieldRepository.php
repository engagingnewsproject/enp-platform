<?php

declare(strict_types=1);

namespace ACA\JetEngine;

use AC;
use AC\PostType;
use AC\Taxonomy;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Utils\Api;

final class FieldRepository
{

    private FieldFactory $field_factory;

    public function __construct(FieldFactory $field_factory)
    {
        $this->field_factory = $field_factory;
    }

    public function find(string $field_name, AC\TableScreen $table_screen): ?Field
    {
        foreach ($this->find_all($table_screen) as $field) {
            if ($field->get_name() === $field_name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return Field[]
     */
    public function find_all(AC\TableScreen $table_screen): array
    {
        switch (true) {
            case $table_screen instanceof PostType:
                return $this->map_meta_types(
                    Api::metaboxes()->get_fields_for_context('post_type', (string)$table_screen->get_post_type())
                );
            case $table_screen instanceof Taxonomy:
                return $this->map_meta_types(
                    Api::metaboxes()->get_fields_for_context('taxonomy', (string)$table_screen->get_taxonomy())
                );
            case $table_screen instanceof AC\TableScreen\User:
                $fields = array_merge(...array_values(Api::metaboxes()->get_fields_for_context('user')));

                return $this->map_meta_types($fields);
        }

        return [];
    }

    /**
     * @return Field[]
     */
    private function map_meta_types(array $meta_types): array
    {
        $fields = [];

        foreach ($meta_types as $field) {
            if (isset($field['object_type']) && $field['object_type'] === 'field') {
                $fields[] = $this->field_factory->create($field);
            }
        }

        return array_filter($fields);
    }

}