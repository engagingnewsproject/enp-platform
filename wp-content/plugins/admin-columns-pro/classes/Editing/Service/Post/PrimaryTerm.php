<?php

declare(strict_types=1);

namespace ACP\Editing\Service\Post;

use AC\Helper\Select\Options\Paginated;
use ACP\Editing\PaginatedOptions;
use ACP\Editing\Service;
use ACP\Editing\Storage\Post\Meta;
use ACP\Editing\View;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;

class PrimaryTerm implements Service, PaginatedOptions
{

    private string $taxonomy;

    private Meta $storage;

    public function __construct(string $meta_key, string $taxonomy)
    {
        $this->storage = new Meta($meta_key);
        $this->taxonomy = $taxonomy;
    }

    public function get_value(int $id)
    {
        $term_id = $this->storage->get($id);

        if ( ! $term_id) {
            $terms = wp_get_post_terms($id, $this->taxonomy);

            return empty($terms) || is_wp_error($terms)
                ? null
                : false;
        }

        $term = get_term($term_id, $this->taxonomy);

        return [
            $term->term_id => $term->name,
        ];
    }

    public function update(int $id, $data): void
    {
        $this->storage->update($id, $data);
    }

    public function get_view(string $context): ?View
    {
        return self::CONTEXT_SINGLE === $context ? new View\AjaxSelect() : null;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'     => $search,
            'page'       => $page,
            'taxonomy'   => $this->taxonomy,
            'object_ids' => [$id],
        ]);
    }

}