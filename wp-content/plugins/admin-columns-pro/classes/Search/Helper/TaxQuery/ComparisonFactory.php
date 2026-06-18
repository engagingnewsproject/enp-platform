<?php

namespace ACP\Search\Helper\TaxQuery;

use ACP\Search\Operators;
use ACP\Search\Value;
use LogicException;

final class ComparisonFactory
{

    public static function create(
        string $taxonomy,
        string $operator,
        Value $terms,
        string $field = 'term_id'
    ): Comparison {
        $operators = [
            Operators::EQ           => 'IN',
            Operators::NEQ => 'NOT IN',
            Operators::IS_EMPTY     => 'NOT EXISTS',
            Operators::NOT_IS_EMPTY => 'EXISTS',
        ];

        if ( ! array_key_exists($operator, $operators)) {
            throw new LogicException('Invalid operator found.');
        }

        return new Comparison($taxonomy, $operators[$operator], $terms, $field);
    }

}