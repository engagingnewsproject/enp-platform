<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter\Product;

use AC\Expression\ComparisonOperators;
use AC\Type\Value;
use ACA\WC\Value\Formatter;
use ACP;

class OrderTotal implements ACP\ConditionalFormat\Formatter
{

    private Formatter\Product\OrderTotal $formatter;

    public function __construct(Formatter\Product\OrderTotal $formatter)
    {
        $this->formatter = $formatter;
    }

    public function get_type(): string
    {
        return self::FLOAT;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if (ComparisonOperators::class === $operator_group) {
            return (string)$this->formatter->format(new Value($id));
        }

        return $value;
    }

}