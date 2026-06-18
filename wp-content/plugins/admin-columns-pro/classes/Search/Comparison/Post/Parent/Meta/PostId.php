<?php

namespace ACP\Search\Comparison\Post\Parent\Meta;

use AC\Helper\Select\Options\Paginated;
use ACP\Helper\Select\Post\LabelFormatter\PostTitle;
use ACP\Helper\Select\Post\PaginatedFactory;
use ACP\Search\Comparison;
use ACP\Search\Comparison\Post\Parent\Meta;
use ACP\Search\Operators;

class PostId extends Meta implements Comparison\SearchableValues
{

    private array $post_type;

    public function __construct(string $meta_key, ?array $post_type = null)
    {
        parent::__construct(
            $meta_key,
            new Operators([
                Operators::EQ,
            ], false)
        );

        $this->post_type = $post_type ?? ['any'];
    }

    public function format_label($value): string
    {
        $post = get_post($value);

        return $post
            ? $this->get_label_formatter()->format_label($post)
            : '';
    }

    protected function get_label_formatter(): PostTitle
    {
        return new PostTitle();
    }

    public function get_values(string $search, int $page): Paginated
    {
        return (new PaginatedFactory())->create([
            's'         => $search,
            'paged'     => $page,
            'post_type' => $this->post_type,
        ], $this->get_label_formatter());
    }
}