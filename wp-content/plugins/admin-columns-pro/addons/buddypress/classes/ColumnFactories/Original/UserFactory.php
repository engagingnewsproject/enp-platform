<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories\Original;

use AC\TableScreen;
use ACA\BP\ColumnFactory;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class UserFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\User) {
            return [];
        }

        return [
            'bp_member_type' => ColumnFactory\User\Original\MemberTypeFactory::class,
        ];
    }

}