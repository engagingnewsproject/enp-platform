<?php

declare(strict_types=1);

namespace ACA\MetaBox\Service;

use AC;
use AC\Asset\Location;
use AC\Registerable;

final class Scripts implements Registerable
{

    private Location\Absolute $location;

    public function __construct(Location\Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/table_scripts/editing', [$this, 'table_scripts_editing']);
    }

    public function table_scripts_editing(): void
    {
        $style = new AC\Asset\Style('aca-metabox-table', $this->location->with_suffix('assets/css/table.css'));
        $style->enqueue();
    }

}