<?php

declare(strict_types=1);

namespace ACA\EC\Search\Event;

use AC;
use AC\Helper\Select\Options\Paginated;
use ACP\Helper\Select\Post\LabelFormatter\PostTitle;
use ACP\Helper\Select\Post\PaginatedFactory;
use ACP\Search\Comparison\Meta;
use ACP\Search\Comparison\SearchableValues;
use ACP\Search\Operators;

class Relation extends Meta
    implements SearchableValues
{

    private AC\Type\PostTypeSlug $post_type;

    public function __construct(string $meta_key, AC\Type\PostTypeSlug $post_type)
    {
        parent::__construct(new Operators([
            Operators::EQ,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]), $meta_key);

        $this->post_type = $post_type;
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
            'post_type' => (string)$this->post_type,
        ]);
    }

}