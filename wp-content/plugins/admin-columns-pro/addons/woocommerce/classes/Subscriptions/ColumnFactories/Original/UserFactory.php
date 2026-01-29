<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\Subscriptions\ColumnFactory;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class UserFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\User) {
            return [];
        }

        return [
            'woocommerce_active_subscriber' => ColumnFactory\User\Original\ActiveSubscriber::class,
        ];
    }
}