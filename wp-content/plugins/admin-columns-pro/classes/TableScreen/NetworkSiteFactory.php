<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use WP_Screen;

class NetworkSiteFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId('wp-ms_sites'));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'sites-network' === $screen->base && 'sites-network' === $screen->id && $screen->in_admin('network');
    }

    private function create_table_screen(): NetworkSite
    {
        return new NetworkSite();
    }

}