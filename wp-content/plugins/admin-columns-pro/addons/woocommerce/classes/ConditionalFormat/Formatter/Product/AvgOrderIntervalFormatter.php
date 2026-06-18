<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter\Product;

use AC;
use AC\Expression\ComparisonOperators;
use AC\Type;
use ACP\ConditionalFormat\Formatter;

class AvgOrderIntervalFormatter implements Formatter
{

    private AC\Formatter $formatter;

    public function __construct(AC\Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function get_type(): string
    {
        return self::INTEGER;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if (ComparisonOperators::class === $operator_group) {
            // return days
            return (string)$this->formatter->format(new Type\Value($id))
                                           ->get_value();
        }

        return $value;
    }

}