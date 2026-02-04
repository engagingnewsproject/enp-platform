<?php

declare(strict_types=1);

namespace ACA\EC\Search\Event;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class HasSeries extends Comparison
{

    public function __construct()
    {
        $operators = new Operators([
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, Value::INT);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acrel');
        $bindings->join(
            "LEFT JOIN {$wpdb->prefix}tec_series_relationships AS {$alias} ON {$alias}.event_post_id = {$wpdb->posts}.ID"
        );

        $compare = $operator === Operators::IS_EMPTY ? 'IS NULL' : 'IS NOT NULL';
        $bindings->where("$alias.event_id $compare");

        return $bindings;
    }

}