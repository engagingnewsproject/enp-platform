<?php

declare(strict_types=1);

namespace ACP\Query\Factory;

use AC\TableScreen;
use ACP;
use ACP\Query;
use ACP\Query\QueryFactory;

class TermFactory implements QueryFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof ACP\TableScreen\Taxonomy;
    }

    public function create(array $bindings): Query
    {
        return new Query\Type\Term($bindings);
    }

}