<?php

declare(strict_types=1);

namespace ACA\JetEngine\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;

final class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ( ! $table_screen instanceof AC\PostType) {
            return null;
        }

        if ($table_screen->get_post_type()->equals('jet-engine')) {
            return new MenuGroup('jet-engine', 'JetEngine', 14);
        }

        return null;
    }

}