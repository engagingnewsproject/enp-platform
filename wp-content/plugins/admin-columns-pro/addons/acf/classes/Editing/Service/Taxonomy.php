<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\Service;

use AC\Helper\Select\Options\Paginated;
use ACP\Editing\PaginatedOptions;
use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;

class Taxonomy extends Service\BasicStorage implements PaginatedOptions
{

    private string $taxonomy;

    public function __construct(string $taxonomy, Storage $storage)
    {
        parent::__construct($storage);

        $this->taxonomy = $taxonomy;
    }

    public function get_view(string $context): ?View
    {
        $view = new View\AjaxSelect();
        $view->set_clear_button(true);

        return $view;
    }

    public function get_value(int $id): array
    {
        $terms = ac_helper()->taxonomy->get_terms_by_ids(
            (array)$this->storage->get($id),
            $this->taxonomy
        );

        $values = [];

        foreach ($terms as $term) {
            $values[$term->term_id] = $term->name;
        }

        return $values;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'   => $search,
            'page'     => $page,
            'taxonomy' => $this->taxonomy,
        ]);
    }

}