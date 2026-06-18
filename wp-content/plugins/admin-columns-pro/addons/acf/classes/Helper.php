<?php

declare(strict_types=1);

namespace ACA\ACF;

class Helper
{

    public function get_field_edit_link(string $field_hash): ?string
    {
        $group = $this->get_field_group(
            $this->parse_field_hash($field_hash)
        );

        if (empty($group['ID'])) {
            return null;
        }

        return acf_get_field_group_edit_link($group['ID']);
    }

    public function parse_field_hash(string $field_hash): string
    {
        // Group field
        if (str_starts_with($field_hash, 'acfgroup__')) {
            return substr($field_hash, strlen('acfgroup__'));
        }

        return $field_hash;
    }

    private function get_field_group(string $field_hash, int $depth = 0): ?array
    {
        if ($depth > 10) {
            return null;
        }

        $field = acf_get_field($field_hash);

        if (empty($field['parent'])) {
            return null;
        }

        if ( ! function_exists('acf_get_raw_field_group')) {
            return null;
        }

        $group = acf_get_raw_field_group($field['parent']);

        if ( ! $group) {
            return $this->get_field_group((string)$field['parent'], $depth + 1);
        }

        return $group;
    }

}