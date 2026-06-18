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
        if ( ! $table_screen instanceof TableScreen\Post && ! $table_screen instanceof TableScreen\Media) {
            return null;
        }

        $post_type = get_post_type_object((string)$table_screen->get_post_type());

        if ($post_type === null) {
            return null;
        }

        return new Post($post_type);
    }

}