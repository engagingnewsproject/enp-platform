<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\DateFormatter;

use AC\Expression\DateOperators;
use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\Type\Value;
use ACP\ConditionalFormat\Formatter\DateFormatter;

class DateValueFormatter extends DateFormatter
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters)
    {
        parent::__construct();

        $this->formatters = $formatters;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if ($operator_group === DateOperators::class) {
            return (string)(new Aggregate($this->formatters))->format(new Value($id));
        }

        return $value;
    }

}