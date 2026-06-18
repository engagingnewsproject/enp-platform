<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\TableScreen;
use ACP\ColumnFactory\User\Original;

class UserFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return [];
        }

        return [
            'email'    => Original\Email::class,
            'name'     => Original\Name::class,
            'posts'    => Original\Posts::class,
            'role'     => Original\Role::class,
            'username' => Original\Username::class,
        ];
    }

}