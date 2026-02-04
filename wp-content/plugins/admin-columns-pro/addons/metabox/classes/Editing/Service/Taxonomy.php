<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing\Service;

use AC\Helper\Select\Options\Paginated;
use ACA;
use ACP;
use ACP\Editing\Storage;
use ACP\Editing\View;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;
use WP_Term;

class Taxonomy implements ACP\Editing\Service, ACP\Editing\PaginatedOptions
{

    private Storage $storage;

    private array $taxonomies;

    public function __construct(Storage $storage, array $taxonomies)
    {
        $this->storage = $storage;
        $this->taxonomies = $taxonomies;
    }

    public function get_view(string $context): ?View
    {
        return new ACP\Editing\View\AjaxSelect();
    }

    public function update(int $id, $data): void
    {
        $this->storage->update($id, $data);
    }

    public function get_value(int $id)
    {
        $term_id = (int)$this->storage->get($id);

        $term = $term_id
            ? get_term($term_id)
            : null;

        return $term instanceof WP_Term
            ? [
                (string)$term_id => ac_helper()->taxonomy->get_term_display_name($term),
            ]
            : false;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'   => $search,
            'page'     => $page,
            'taxonomy' => $this->taxonomies,
        ]);
    }

}