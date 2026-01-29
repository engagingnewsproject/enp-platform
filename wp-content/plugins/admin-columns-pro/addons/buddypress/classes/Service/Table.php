<?php

declare(strict_types=1);

namespace ACA\BP\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Type\TableId;

class Table implements Registerable
{

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'table_scripts'], 1);
    }

    private function is_bp_list_screen(AC\TableScreen $table_screen): bool
    {
        return $table_screen->get_id()->equals(new TableId('bp-groups')) ||
               $table_screen->get_id()->equals(new TableId('bp-email'));
    }

    public function table_scripts(AC\ListScreen $list_screen): void
    {
        if ( ! $this->is_bp_list_screen($list_screen->get_table_screen())) {
            return;
        }

        $style = new AC\Asset\Style('aca-bp-table', $this->location->with_suffix('assets/css/table.css'));
        $style->enqueue();
    }

}