<?php

declare(strict_types=1);

namespace ACA\ACF;

class Helper
{

    public function get_field_edit_link(string $field_hash): ?string
    {
        $group = $this->get_field_group($field_hash);

        if (empty($group['ID'])) {
            return null;
        }

        return acf_get_field_group_edit_link($group['ID']);
    }

    private function get_field_group(string $field_hash): ?array
    {
        $field = acf_get_field($field_hash);

        if (empty($field['parent'])) {
            return null;
        }

        if ( ! function_exists('acf_get_raw_field_group')) {
            return null;
        }

        $group = acf_get_raw_field_group($field['parent']);

        if ( ! $group) {
            return $this->get_field_group($field['parent']);
        }

        return $group;
    }

}