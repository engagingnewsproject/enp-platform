<?php

namespace ACP\Search\Comparison\Post\Date;

use ACP\Query\Bindings;
use ACP\Search\Operators;
use ACP\Search\Value;

class PostPublished extends PostDate
{

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $status = 'publish';

        if ($operator === Operators::FUTURE || $operator === Operators::WITHIN_DAYS) {
            $status = 'future';
        }

        $bindings = parent::create_query_bindings($operator, $value);
        $bindings->where(
            sprintf(
                "%s AND $wpdb->posts.post_status = '%s'",
                $bindings->get_where(),
                $status
            )
        );

        return $bindings;
    }

}