<?php

declare(strict_types=1);

namespace ACP\Editing\Strategy;

use AC\PostType;
use AC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class PostFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof PostType) {
            return null;
        }

        return new Post(get_post_type_object((string)$table_screen->get_post_type()));
    }

}