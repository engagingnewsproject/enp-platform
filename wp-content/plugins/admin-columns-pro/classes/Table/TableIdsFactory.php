<?php

declare(strict_types=1);

namespace ACP\Table;

use AC;
use AC\Type\TableId;
use AC\Type\TableIdCollection;

class TableIdsFactory implements AC\TableIdsFactory
{

    private $taxonomy_repository;

    public function __construct(TaxonomyRepository $taxonomy_repository)
    {
        $this->taxonomy_repository = $taxonomy_repository;
    }

    public function create(): TableIdCollection
    {
        $keys = [
            new TableId('wp-ms_users'),
            new TableId('wp-ms_sites'),
        ];

        foreach ($this->taxonomy_repository->find_all() as $taxonomy) {
            $keys[] = new TableId('wp-taxonomy_' . $taxonomy);
        }

        return new TableIdCollection($keys);
    }

}