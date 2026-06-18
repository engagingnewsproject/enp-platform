<?php

namespace ACP\QuickAdd\Model;

use AC\PostType;
use AC\TableScreen;

class PostFactory implements ModelFactory
{

    public function create(TableScreen $table_screen): ?Create
    {
        $post_type = $table_screen instanceof PostType
            ? (string)$table_screen->get_post_type()
            : null;

        return $post_type && post_type_exists($post_type)
            ? new Create\Post($post_type)
            : null;
    }

}