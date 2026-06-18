<?php

declare(strict_types=1);

namespace ACA\BP;

use BP_XProfile_Field;
use BP_XProfile_Group;

class FieldRepository
{

    /**
     * @return BP_XProfile_Field[]
     */
    public function find_all(): array
    {
        if ( ! class_exists('BP_XProfile_Group')) {
            return [];
        }

        $groups = BP_XProfile_Group::get(['fetch_fields' => true]);
        $fields = [];

        foreach ($groups as $group) {
            foreach ($group->fields as $field) {
                if ($field instanceof BP_XProfile_Field) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

}