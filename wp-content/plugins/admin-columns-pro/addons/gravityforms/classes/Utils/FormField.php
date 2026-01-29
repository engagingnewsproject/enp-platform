<?php

declare(strict_types=1);

namespace ACA\GravityForms\Utils;

class FormField
{

    /**
     * @param mixed $choices
     *
     * @return array
     */
    public static function formatChoices($choices)
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