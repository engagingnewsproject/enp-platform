<?php

declare(strict_types=1);

namespace ACA\GravityForms\Utils;

class FormField
{

    /**
     * @param mixed $choices
     */
    public static function formatChoices($choices): array
    {
        if (empty($choices) || ! is_array($choices)) {
            return [];
        }

        $options = [];

        foreach ($choices as $choice) {
            $options[$choice['value']] = $choice['text'];
        }

        return $options;
    }

}