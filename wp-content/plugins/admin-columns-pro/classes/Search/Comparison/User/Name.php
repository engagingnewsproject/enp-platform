<?php

namespace ACP\Search\Comparison\User;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\MetaQuery\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class Name extends Comparison
{

    private array $meta_keys;

    public function __construct(array $meta_keys)
    {
        $operators = new Operators([
            Operators::CONTAINS,
            Operators::BEGINS_WITH,
            Operators::ENDS_WITH,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        $this->meta_keys = $meta_keys;

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        $bindings = new Bindings();
        $bindings->meta_query(
            $this->get_meta_query($operator, $value)
        );

        return $bindings;
    }

    protected function get_meta_query($operator, Value $value): array
    {
        $meta_query = [
            'relation' => Operators::IS_EMPTY === $operator ? 'AND' : 'OR',
        ];

        foreach ($this->meta_keys as $key) {
            $mq = ComparisonFactory::create(
                $key,
                $operator,
                $value
            );
            $meta_query[] = $mq();
        }

        return $meta_query;
    }

}