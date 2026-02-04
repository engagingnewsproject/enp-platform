<?php

declare(strict_types=1);

namespace ACP\Editing\BulkDelete\Deletable;

use AC\TableScreen;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\BulkDelete\StrategyFactory;

class CommentFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable
    {
        if ( ! $table_screen instanceof TableScreen\Comment) {
            return null;
        }

        return new Comment();
    }

}