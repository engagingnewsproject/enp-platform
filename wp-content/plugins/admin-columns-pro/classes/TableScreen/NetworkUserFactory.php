<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use WP_Screen;

class NetworkUserFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return new NetworkUser();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId('wp-ms_users'));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return new NetworkUser();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'users-network' === $screen->base && 'users-network' === $screen->id && $screen->in_admin('network');
    }

}