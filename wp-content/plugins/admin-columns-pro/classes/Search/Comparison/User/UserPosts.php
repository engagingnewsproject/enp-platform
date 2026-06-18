<?php

namespace ACP\Search\Comparison\User;

use ACP\Query\Bindings;
use ACP\Query\SqlTrait;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class UserPosts extends Comparison
{

    use SqlTrait;

    private array $post_types;

    private array $post_status;

    public function __construct(array $post_types, array $post_status)
    {
        $this->post_types = $post_types;
        $this->post_status = $post_status;

        parent::__construct(new Operators([
            Operators::EQ,
            Operators::NEQ,
            Operators::LT,
            Operators::LTE,
            Operators::GT,
            Operators::GTE,
            Operators::BETWEEN,
        ]), Value::INT);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('sq_posts');

        $sub_query = "SELECT COUNT(ID) as num_posts, post_author 
            FROM $wpdb->posts
            WHERE 1=1";

        if ($this->post_types) {
            $sub_query .= "\nAND post_type IN ( " . $this->esc_sql_array($this->post_types) . ")";
        }
        if ($this->post_status) {
            $sub_query .= "\nAND post_status IN ( " . $this->esc_sql_array($this->post_status) . ")";
        }

        $sub_query .= "\nGROUP BY post_author";

        $bindings->join("LEFT JOIN ($sub_query) AS $alias ON $wpdb->users.ID = $alias.post_author");

        $bindings->where(ComparisonFactory::create("$alias.num_posts", $operator, $value)());

        return $bindings;
    }

}