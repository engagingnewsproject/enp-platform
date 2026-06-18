<?php

namespace ACP\ConditionalFormat;

use ACP\ConditionalFormat\Formatter\StringFormatter;

final class FormattableConfig
{

    private Formatter $formatter;

    public function __construct(?Formatter $formatter = null)
    {
        $this->formatter = $formatter ?? new StringFormatter();
    }

    public function get_value_formatter(): Formatter
    {
        return $this->formatter;
    }

}