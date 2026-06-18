<?php

declare(strict_types=1);

namespace ACA\ACF;

use AC;
use AC\TableScreen;

class FieldRepository
{

    private FieldGroup\QueryFactory $query_factory;

    private FieldFactory $field_factory;

    public function __construct(FieldGroup\QueryFactory $query_factory, FieldFactory $field_factory)
    {
        $this->query_factory = $query_factory;
        $this->field_factory = $field_factory;
    }

    public function find_all(TableScreen $table_screen): array
    {
        static $fields;

        $id = (string)$table_screen->get_id();

        if ( ! isset($fields[$id])) {
            $fields[$id] = $this->find_by_table_screen($table_screen);
        }

        return $fields[$id];
    }

    private function is_root_field(array $field): bool
    {
        return false !== acf_get_field_group($field['parent'] ?? 0);
    }

    public function find_by_field_key(string $field_key): ?Field
    {
        $field = acf_get_field($field_key);

        if ( ! $field) {
            return null;
        }

        $field_type = $field['type'] ?? '';

        // Clone field type contains multiple subfields and can not be matched with a single field
        if (FieldType::TYPE_CLONE === $field_type) {
            return null;
        }

        if ($this->is_root_field($field)) {
            return $this->field_factory->create($field);
        }

        $parent = acf_get_field($field['parent'] ?? 0);

        if ($parent) {
            switch ($parent['type'] ?? '') {
                case FieldType::TYPE_REPEATER:
                    return $this->is_root_field($parent)
                        ? $this->field_factory->create($parent)
                        : null;
                case FieldType::TYPE_GROUP:
                    return $this->is_root_field($parent)
                        ? $this->create_group_field($field, $parent)
                        : null;
            }
        }

        return null;
    }

    private function find_by_table_screen(TableScreen $table_screen): array
    {
        $group_query = $this->query_factory->create($table_screen);

        if ( ! $group_query instanceof AC\Acf\FieldGroup\Query) {
            return [];
        }

        do_action('ac/acf/before_get_field_options', $table_screen);
        $groups = $group_query->get_groups();
        do_action('ac/acf/after_get_field_options', $table_screen);

        if ( ! $groups) {
            return [];
        }

        $acf_fields = array_filter(array_map('acf_get_fields', $groups), 'is_array');

        if ( ! $acf_fields) {
            return [];
        }

        $acf_fields = array_filter(array_merge(...$acf_fields));

        if ( ! $acf_fields) {
            return [];
        }

        $fields = [];

        foreach ($acf_fields as $field) {
            switch ($field['type']) {
                case FieldType::TYPE_GROUP:
                    $fields[] = $this->create_group_fields($field);
                    break;

                case FieldType::TYPE_CLONE:
                    $fields[] = $this->create_clone_fields($field);
                    break;
                default:
                    $fields[] = [$this->field_factory->create($field)];
            }
        }

        return array_filter(array_merge(...$fields));
    }

    private function create_clone_fields(array $field): array
    {
        $fields = [];

        foreach ((array)($field['sub_fields'] ?? []) as $sub_field) {
            $fields[] = $this->field_factory->create($sub_field);
        }

        return $fields;
    }

    private function create_group_fields(array $group_field): array
    {
        $fields = [];

        $exclude = [
            FieldType::TYPE_FLEXIBLE_CONTENT,
            FieldType::TYPE_GROUP,
            FieldType::TYPE_REPEATER,
        ];

        foreach ($group_field['sub_fields'] ?? [] as $field) {
            if (in_array($field['type'], $exclude, true)) {
                continue;
            }

            $fields[] = $this->create_group_field($field, $group_field);
        }

        return $fields;
    }

    private function create_group_field(array $field, array $group_field): ?Field
    {
        $field['_ac_type'] = 'group';
        $field['_ac_group'] = $group_field;

        return $this->field_factory->create($field);
    }

}