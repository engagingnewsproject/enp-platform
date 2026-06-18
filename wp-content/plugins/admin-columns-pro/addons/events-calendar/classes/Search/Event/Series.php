<?php

declare(strict_types=1);

namespace ACA\EC\Search\Event;

use AC\Helper\Select\Options\Paginated;
use ACP\Helper\Select\Post\LabelFormatter\PostTitle;
use ACP\Helper\Select\Post\PaginatedFactory;
use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Comparison\SearchableValues;
use ACP\Search\Operators;
use ACP\Search\Value;

class Series extends Comparison implements SearchableValues
{

    public function __construct()
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, Value::INT);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acser');

        $join_type = $operator === Operators::IS_EMPTY ? 'LEFT' : 'INNER';
        $bindings->join(
            "{$join_type} JOIN {$wpdb->prefix}tec_series_relationships AS {$alias} ON {$alias}.event_post_id = {$wpdb->posts}.ID"
        );

        switch ($operator) {
            case Operators::IS_EMPTY:
                $bindings->where("$alias.event_id IS NULL");
                break;
            case Operators::EQ:
                $bindings->where($wpdb->prepare("$alias.series_post_id = %d", (int)$value->get_value()));
                break;
        }

        return $bindings;
    }

    public function format_label($value): string
    {
        $post = get_post($value);

        return $post
            ? (new PostTitle())->format_label($post)
            : '';
    }

    public function get_values(string $search, int $page): Paginated
    {
        return (new PaginatedFactory())->create([
            's'         => $search,
            'paged'     => $page,
            'post_type' => 'tribe_event_series',
        ]);
    }

}
