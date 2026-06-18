<?php

declare(strict_types=1);

namespace ACA\ACF\Storage;

use AC\Type\TableScreenContext;
use ACA\ACF\Utils\AcfId;
use acf_field_clone;

class CloneFieldStorage
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

    public function update(int $id, string $field_hash, string $clone_hash, $value): bool
    {
        $clone_field = acf_get_field($clone_hash);

        if ( ! $clone_field || ! is_array($clone_field)) {
            return false;
        }

        $value_key = $clone_field['display'] === 'group'
            ? $field_hash
            : $clone_hash . '_' . $field_hash;

        $values = [
            $value_key => $value,
        ];

        return null !== (new acf_field_clone())->update_value(
                $values,
                $this->create_id($id),
                $clone_field
            );
    }

    public function delete(int $id, string $parent_key): bool
    {
        return false !== delete_field($parent_key, $this->create_id($id));
    }

}