<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC\TableScreen;
use AC\TableScreen\Comment;
use ACP\ColumnFactory\Comment\Original;

final class CommentFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        return $table_screen instanceof Comment
            ? [
                'author'   => Original\Author::class,
                'comment'  => Original\Comment::class,
                'date'     => Original\Date::class,
                'response' => Original\Response::class,
            ] : [];
    }

}