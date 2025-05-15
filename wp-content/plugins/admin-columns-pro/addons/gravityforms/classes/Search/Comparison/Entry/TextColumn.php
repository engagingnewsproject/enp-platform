<?php

namespace ACA\GravityForms\Search\Comparison\Entry;

use ACA\GravityForms\Search\Query\Bindings;
use ACP;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class TextColumn extends ACP\Search\Comparison
{

    private $column;

    public function __construct(string $column)
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::CONTAINS,
            Operators::NOT_CONTAINS,
        ]);

        parent::__construct($operators, Value::STRING);

        $this->column = $column;
    }

    protected function create_query_bindings(string $operator, Value $value): ACP\Query\Bindings
    {
        $comparison = ComparisonFactory::create($this->column, $operator, $value);

        return (new Bindings())->where($comparison());
    }

}