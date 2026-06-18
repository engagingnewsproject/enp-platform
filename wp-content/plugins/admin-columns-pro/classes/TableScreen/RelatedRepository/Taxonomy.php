<?php

declare(strict_types=1);

namespace ACP\TableScreen\RelatedRepository;

use AC\TableScreenFactory;
use AC\Type\TableId;
use AC\Type\TableIdCollection;
use ACP\Table\TaxonomyRepository;
use ACP\TableScreen\RelatedRepository;

class Taxonomy implements RelatedRepository
{

    use TableTrait;

    private TaxonomyRepository $taxonomy_repository;

    public function __construct(
        TaxonomyRepository $taxonomy_repository,
        TableScreenFactory\Aggregate $table_screen_factory
    ) {
        $this->taxonomy_repository = $taxonomy_repository;

        $this->set_table_screen_factory($table_screen_factory);
    }

    protected function get_keys(): TableIdCollection
    {
        $keys = new TableIdCollection();

        foreach ($this->taxonomy_repository->find_all() as $taxonomy) {
            $keys->add(new TableId($taxonomy));
        }

        return $keys;
    }

}