<?php

namespace ACP\Search\Comparison\Taxonomy;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class Slug extends Comparison
{

    public function __construct()
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::NEQ,
            Operators::CONTAINS,
            Operators::NOT_CONTAINS,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        $bindings = new Bindings();
        $bindings->where(
            ComparisonFactory::create(
                't.slug',
                $operator,
                $value
            )->prepare()
        );

        return $bindings;
    }

}