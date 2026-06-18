<?php

namespace ACP\Sorting\FormatValue;

use AC\Helper;
use ACP\Sorting\FormatValue;

class FileMeta implements FormatValue
{

    private array $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public function format_value($value)
    {
        $value = maybe_unserialize($value);

        if (is_array($value) && $this->keys) {
            $value = Helper\Arrays::create()->get_nested_value($value, $this->keys);
        }

        return is_scalar($value)
            ? $value
            : null;
    }

}
