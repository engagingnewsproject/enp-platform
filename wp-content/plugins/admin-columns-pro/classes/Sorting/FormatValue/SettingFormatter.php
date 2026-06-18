<?php

namespace ACP\Sorting\FormatValue;

use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\Type\Value;
use ACP\Sorting\FormatValue;

class SettingFormatter implements FormatValue
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters)
    {
        $this->formatters = $formatters;
    }

    public function format_value($value): string
    {
        if (null === $value) {
            return '';
        }

        $formatter = new Aggregate($this->formatters);

        return (string)$formatter->format(new Value($value));
    }

}