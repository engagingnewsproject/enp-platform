<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing\Service\Relation;

use AC\Helper\Select\Options\Paginated;
use ACA;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;
use WP_Term;

class Term extends ACA\MetaBox\Editing\Service\Relation
{

    public function get_value(int $id): array
    {
        $results = [];

        foreach (parent::get_value($id) as $term_id) {
            $term = get_term((int)$term_id);

            if ($term instanceof WP_Term) {
                $results[$term_id] = ac_helper()->taxonomy->get_term_display_name($term);
            }
        }

        return $results;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'   => $search,
            'page'     => $page,
            'taxonomy' => $this->relation->get_related_field_settings()['taxonomy'],
        ]);
    }

}