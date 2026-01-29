<?php

declare(strict_types=1);

namespace ACA\EC;

/**
 * Class ACA_EC_API
 * Interface to the EC API that works across the free and pro version
 */
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

    /**
     * @param string $meta_key
     *
     * @return array
     */
    public static function get_field(string $meta_key)
    {
        $fields = self::get_additional_fields();

        foreach ($fields as $field) {
            if ($meta_key === $field->get_id()) {
                return $field;
            }
        }

        return [];
    }

    /**
     * @return false|mixed
     */
    public static function get(string $meta_key, string $var)
    {
        $settings = self::get_field($meta_key);

        if ( ! array_key_exists($var, $settings)) {
            return false;
        }

        return $settings[$var];
    }

}