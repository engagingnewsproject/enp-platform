<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class CheckboxToggleValue implements Formatter
{

    private string $true_value;

    private string $false_value;

    public function __construct(string $true_value, string $false_value)
    {
        $this->true_value = $true_value;
        $this->false_value = $false_value;
    }

    public function format(Value $value)
    {
        $raw_value = $value->get_value()
            ? $this->true_value
            : $this->false_value;

        return $value->with_value(
            $raw_value
        );
    }
}