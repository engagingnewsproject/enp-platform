<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC\PostType;
use AC\TableScreen;
use ACP\ColumnFactory\Post\Original;

class PostFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType) {
            return [];
        }

        return [
            'title'      => Original\Title::class,
            'author'     => Original\Author::class,
            'date'       => Original\Date::class,
            'comments'   => Original\Comments::class,
            'categories' => Original\Categories::class,
            'tags'       => Original\Tags::class,
        ];
    }

}