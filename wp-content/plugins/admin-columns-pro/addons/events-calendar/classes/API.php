<?php

declare(strict_types=1);

namespace ACA\EC;

class API
{

    public static function is_pro(): bool
    {
        return function_exists('Tribe_ECP_Load');
    }

    /**
     * @return AdditionalField[]
     */
    public static function get_additional_fields(): array
    {
        if ( ! self::is_pro()) {
            return [];
        }

        $fields = [];

        foreach (tribe_get_option('custom-fields', []) as $field) {
            $fields[] = new AdditionalField($field);
        }

        return $fields;
    }

}