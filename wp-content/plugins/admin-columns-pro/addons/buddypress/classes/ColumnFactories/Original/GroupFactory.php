<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories\Original;

use AC\TableScreen;
use ACA\BP\ColumnFactory;
use ACP;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class GroupFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ((string)$table_screen->get_id() !== 'bp-groups') {
            return [];
        }

        return [
            'description' => ColumnFactory\Group\Original\Description::class,
            'comment'     => ColumnFactory\Group\Original\Name::class,
            'status'      => ColumnFactory\Group\Original\Status::class,
            'members'     => ACP\Column\OriginalColumnFactory::class,
            'last_active' => ACP\Column\OriginalColumnFactory::class,
        ];
    }

}