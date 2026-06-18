<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use ACP;
use ACP\Search\Operators;
use ACP\Search\UserValuesTrait;
use ACP\Search\Value;

class User extends TableStorage implements ACP\Search\Comparison\SearchableValues
{

    use UserValuesTrait;

    protected array $query_args;

    public function __construct(
        string $table,
        string $column,
        array $query_args = [],
        string $value_type = Value::INT
    ) {
        $operators = new Operators([
            Operators::EQ,
            Operators::CURRENT_USER,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, $table, $column, $value_type);

        $this->query_args = $query_args;
    }

}