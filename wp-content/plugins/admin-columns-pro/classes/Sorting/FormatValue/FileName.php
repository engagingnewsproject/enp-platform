<?php

namespace ACP\Sorting\FormatValue;

use ACP\Sorting\FormatValue;

class FileName implements FormatValue
{

    public function format_value($file): string
    {
        if ( ! is_string($file) || ! $file) {
            return '';
        }

        return strtolower(basename($file));
    }

}
