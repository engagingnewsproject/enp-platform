<?php

declare(strict_types=1);

namespace ACA\MetaBox\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ($this->is_valid_screen($table_screen)) {
            return new MenuGroup(
                'metabox',
                'MetaBox',
                14
            );
        }

        return null;
    }

    private function is_valid_screen(TableScreen $table_screen): bool
    {
        return $table_screen instanceof AC\PostType
               && in_array(
                   (string)$table_screen->get_post_type(),
                   [
                       'meta-box',
                       'mb-taxonomy',
                       'mb-relationship',
                       'mb-post-type',
                   ],
                   true
               );
    }

}