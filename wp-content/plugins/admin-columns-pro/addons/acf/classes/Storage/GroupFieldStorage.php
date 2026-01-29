<?php

declare(strict_types=1);

namespace ACA\ACF\Storage;

use AC\Type\TableScreenContext;
use ACA\ACF\Utils\AcfId;

class GroupFieldStorage
{

    private TableScreenContext $context;

    public function __construct(TableScreenContext $context)
    {
        $this->context = $context;
    }

    private function create_id(int $id): string
    {
        return AcfId::get_id($id, $this->context);
    }

    public function get(int $id, string $parent_key)
    {
        return get_field($parent_key, $this->create_id($id), false);
    }

    public function update(int $id, string $group_key, string $sub_key, $value): bool
    {
        $values = $this->get($id, $group_key);

        if ( ! is_array($values)) {
            return false;
        }

        $data = [];

        foreach ($values as $field_key => $field_value) {
            $field = acf_get_field($field_key);

            if ( ! $field || ! isset($field['name'])) {
                exit;
            }

            $data[$field['name']] = $field_value;
        }

        $data[$sub_key] = $value;

        return false !== update_field($group_key, $data, $this->create_id($id));
    }

    public function delete(int $id, string $parent_key): bool
    {
        return false !== delete_field($parent_key, $this->create_id($id));
    }

}