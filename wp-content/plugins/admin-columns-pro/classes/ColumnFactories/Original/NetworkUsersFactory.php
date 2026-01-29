<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC\TableScreen;
use AC\Type\TableId;
use ACP\ColumnFactory\User\Original;

class NetworkUsersFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen->get_id()->equals(new TableId('wp-ms_users'))) {
            return [];
        }

        return [
            'username'   => Original\Username::class,
            'name'       => Original\Name::class,
            'email'      => Original\Email::class,
            'registered' => Original\Registered::class,
            'blogs'      => Original\Blogs::class,
        ];
    }

}