<?php

namespace ACP\ConditionalFormat\Formatter;

use ACP\ConditionalFormat\Formatter;

final class FilterHtmlFormatter implements Formatter
{

    private Formatter $formatter;

    public function __construct(?Formatter $formatter = null)
    {
        $this->formatter = $formatter ?? new StringFormatter();
    }

    public function get_type(): string
    {
        return $this->formatter->get_type();
    }

    public function format(string $value, $id, string $operator_group): string
    {
        $value = trim(strip_tags($value));

        return $this->formatter->format($value, $id, $operator_group);
    }

}