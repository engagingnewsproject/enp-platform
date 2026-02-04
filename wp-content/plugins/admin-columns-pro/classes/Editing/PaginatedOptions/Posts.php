<?php

namespace ACP\Editing\PaginatedOptions;

use AC\Helper\Select\Options\Paginated;
use ACP\Editing\PaginatedOptionsFactory;
use ACP\Helper\Select\Post\PaginatedFactory;

class Posts implements PaginatedOptionsFactory
{

    private array $post_types;

    private array $args;

    public function __construct(array $post_types = [], array $args = [])
    {
        $this->post_types = $post_types ?: ['any'];
        $this->args = $args;
    }

    public function create(string $search, int $page, ?int $id = null): Paginated
    {
        $args = array_merge([
            'paged'     => $page,
            's'         => $search,
            'post_type' => $this->post_types,
        ], $this->args);

        return (new PaginatedFactory())->create($args);
    }

}