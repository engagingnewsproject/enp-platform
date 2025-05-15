<?php

namespace ACP\Search\Helper\MetaQuery;

use ACP\Search\Operators;
use ACP\Search\Value;
use LogicException;

final class SerializedComparisonFactory
{

    public static function create(string $key, string $operator, Value $value): Comparison
    {
        $value = new Value(
            serialize($value->get_value()),
            $value->get_type()
        );

        $operators = [
            Operators::EQ           => 'LIKE',
            Operators::CURRENT_USER => 'LIKE',
            Operators::NEQ          => 'NOT LIKE',
            Operators::CONTAINS     => 'LIKE',
            Operators::NOT_CONTAINS => 'NOT LIKE',
        ];

        if (array_key_exists($operator, $operators)) {
            return new Comparison($key, $operators[$operator], $value);
        }

        $operators = [
            Operators::IS_EMPTY     => 'IsEmpty',
            Operators::NOT_IS_EMPTY => 'NotEmpty',
        ];

        if ( ! array_key_exists($operator, $operators)) {
            throw new LogicException('Unsupported operator found.');
        }

        $class = __NAMESPACE__ . '\Comparison\\' . $operators[$operator];

        return new $class($key, $value);
    }

}