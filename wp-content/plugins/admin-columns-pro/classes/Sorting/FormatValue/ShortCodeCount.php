<?php

namespace ACP\Sorting\FormatValue;

use AC\Helper;
use ACP\Sorting\FormatValue;

class ShortCodeCount implements FormatValue
{

    public function format_value($value): ?int
    {
        $shortcodes = Helper\Strings::create()->get_shortcodes((string)$value);

        return $shortcodes
            ? (int)array_sum($shortcodes)
            : null;
    }

}
