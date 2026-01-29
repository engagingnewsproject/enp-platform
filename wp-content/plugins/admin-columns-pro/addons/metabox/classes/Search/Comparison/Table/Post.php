<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use AC\Helper\Select\Options\Paginated;
use ACP;
use ACP\Helper\Select\Post\LabelFormatter\PostTitle;
use ACP\Helper\Select\Post\PaginatedFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class Post extends TableStorage implements ACP\Search\Comparison\SearchableValues
{

    private $post_type;

    private $query_args;

    public function __construct(
        string $table,
        string $column,
        array $post_type = [],
        array $query_args = []
    ) {
        parent::__construct(new Operators([
            Operators::EQ,
            Operators::NEQ,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]), $table, $column, Value::INT);

        $this->post_type = $post_type;
        $this->query_args = $query_args;
    }

    public function format_label($value): string
    {
        $post = get_post($value);

        return $post
            ? $this->formatter()->format_label($post)
            : '';
    }

    private function formatter(): PostTitle
    {
        return new PostTitle();
    }

    public function get_values(string $search, int $page): Paginated
    {
        $args = wp_parse_args($this->query_args, [
            's'         => $search,
            'paged'     => $page,
            'post_type' => $this->post_type,
        ]);

        return (new PaginatedFactory())->create($args, $this->formatter());
    }

}