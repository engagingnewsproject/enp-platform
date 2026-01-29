<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting\FormatValue;

use ACP\Sorting\FormatValue;

class File implements FormatValue
{

    public function format_value($value)
    {
        return basename(get_attached_file($value));
    }

}