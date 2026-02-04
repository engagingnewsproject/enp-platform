<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use WP_Screen;

class ActivityFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId('bp-activity'));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen();
    }

    private function create_table_screen(): Activity
    {
        $url = new AC\Type\Url\AdminUrl('admin.php');
        $url = $url->with_arg('page', 'bp-activity');

        return new Activity($url);
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'toplevel_page_bp-activity' === $screen->id &&
               'toplevel_page_bp-activity' === $screen->base &&
               'edit' !== filter_input(INPUT_GET, 'action');
    }

}