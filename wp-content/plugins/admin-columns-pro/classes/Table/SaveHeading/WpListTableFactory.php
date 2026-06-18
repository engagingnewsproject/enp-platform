<?php

declare(strict_types=1);

namespace ACP\Table\SaveHeading;

use AC;
use AC\Table\SaveHeading\ScreenColumnsFactory;
use ACP\TableScreen;

class WpListTableFactory extends ScreenColumnsFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\NetworkSite ||
               $table_screen instanceof TableScreen\NetworkUser ||
               $table_screen instanceof TableScreen\Taxonomy;
    }

}