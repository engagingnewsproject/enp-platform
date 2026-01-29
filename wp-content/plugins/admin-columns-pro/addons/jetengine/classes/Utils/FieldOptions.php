<?php

declare(strict_types=1);

namespace ACA\JetEngine\Utils;

final class FieldOptions
{

    public static function get_checked_options(array $options): array
    {
        foreach ($options as $key => $selected) {
            if ($selected !== 'true') {
                unset($options[$key]);
            }
        }

        return array_keys($options);
    }

}