<?php

namespace ACP\Search\Comparison\Post;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class ChildPages extends Comparison
{

    private $post_type;

    public function __construct(string $post_type)
    {
        $operators = new Operators([
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        $this->post_type = $post_type;

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $operator = $operator === Operators::NOT_IS_EMPTY
            ? 'IN'
            : 'NOT IN';

        $where = $wpdb->prepare(
            "$wpdb->posts.ID $operator (
		                SELECT DISTINCT $wpdb->posts.post_parent
                        FROM $wpdb->posts 
                        WHERE $wpdb->posts.post_parent > 1
                            AND $wpdb->posts.post_status = 'publish'
                            AND $wpdb->posts.post_type = %s
                   )",
            $this->post_type
        );

        $bindings = new Bindings();
        $bindings->where($where);

        return $bindings;
    }

}