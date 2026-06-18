<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use ACP\Search\Operators;
use ACP\Search\Value;

class Number extends TableStorage
{

    public function __construct(
        string $table,
        string $column
    ) {
        parent::__construct(new Operators([
            Operators::EQ,
            Operators::NEQ,
            Operators::GT,
            Operators::LT,
            Operators::GTE,
            Operators::LTE,
            Operators::BETWEEN,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ], false), $table, $column, Value::INT);
    }

}