<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\DateFormatter;

use AC\Expression\DateOperators;
use AC\Formatter\Aggregate;
use AC\Formatter\Date\DateFormat;
use AC\FormatterCollection;
use AC\Type\Value;
use ACP\ConditionalFormat\Formatter\DateFormatter;

class BaseDateFormatter extends DateFormatter
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters, ?string $date_source_format = null)
    {
        parent::__construct();

        $this->formatters = $formatters->with_formatter(
            new DateFormat('Y-m-d', $date_source_format)
        );
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if ($operator_group === DateOperators::class) {
            $formatted_value = (new Aggregate($this->formatters))->format(new Value($id));

            return $formatted_value instanceof Value
                ? (string)$formatted_value
                : $value;
        }

        return $value;
    }

}