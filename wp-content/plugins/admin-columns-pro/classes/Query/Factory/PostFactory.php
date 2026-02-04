<?php

declare(strict_types=1);

namespace ACP\Query\Factory;

use AC\PostType;
use AC\TableScreen;
use ACP\Query;
use ACP\Query\QueryFactory;

class PostFactory implements QueryFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof PostType;
    }

    public function create(array $bindings): Query
    {
        return new Query\Type\Post($bindings);
    }

}