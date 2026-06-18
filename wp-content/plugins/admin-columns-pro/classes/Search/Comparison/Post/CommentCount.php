<?php

namespace ACP\Search\Comparison\Post;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class CommentCount extends Comparison
{

    public const STATUS_APPROVED = '1';
    public const STATUS_SPAM = 'spam';
    public const STATUS_TRASH = 'trash';
    public const STATUS_PENDING = '0';

    private array $stati;

    public function __construct(array $stati = [])
    {
        parent::__construct(
            new Operators([
                Operators::GT,
                Operators::GTE,
                Operators::LT,
                Operators::LTE,
                Operators::BETWEEN,
            ]),
            Value::INT
        );

        $this->stati = $stati;
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('cc_search');
        $join_alias = $bindings->get_unique_alias('cci_search');

        $sub_query = "
                SELECT comment_post_ID, count(*) as comment_count
                FROM $wpdb->comments as $alias
            ";

        if ($this->stati) {
            $sub_query .= sprintf(
                "\nWHERE $alias.comment_approved IN ( '%s' )",
                implode("','", array_map('esc_sql', $this->stati))
            );
        }

        $sub_query .= "\nGROUP BY comment_post_ID";

        $comparison = ComparisonFactory::create($join_alias . '.comment_count', $operator, $value);

        $bindings->join(" INNER JOIN($sub_query) AS $join_alias ON $wpdb->posts.ID = $join_alias.comment_post_ID")
                 ->where($comparison());

        return $bindings;
    }

}