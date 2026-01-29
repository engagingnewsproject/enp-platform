<?php

declare(strict_types=1);

namespace ACP\Editing\BulkDelete\Deletable;

use AC\TableScreen;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\BulkDelete\StrategyFactory;

class PostFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable
    {
        if ( ! $table_screen instanceof TableScreen\Post) {
            return null;
        }

        return new Post(
            get_post_type_object((string)$table_screen->get_post_type())
        );
    }

}