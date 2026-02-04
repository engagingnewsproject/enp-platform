<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter;

use ACP\ConditionalFormat;

class FloatFormatter implements ConditionalFormat\Formatter
{

    public function get_type(): string
    {
        return self::FLOAT;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        return $value;
    }
}