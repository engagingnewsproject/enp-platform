<?php

namespace ACP\Sorting\FormatValue;

use ACP\Sorting\FormatValue;

class ShortCodeCount implements FormatValue
{

    public function format_value($value): ?int
    {
        $shortcodes = ac_helper()->string->get_shortcodes((string)$value);

        return $shortcodes
            ? (int)array_sum($shortcodes)
            : null;
    }

}
