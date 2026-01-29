<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\ManageHeadings;

use AC\Registerable;
use AC\Table\ManageHeading\ScreenColumnsFactory;
use AC\TableScreen;
use ACA\BP;

class GroupFactory extends ScreenColumnsFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof BP\TableScreen\Group;
    }

    public function create(TableScreen $table_screen, array $headings): Registerable
    {
        return new Group(
            $headings
        );
    }
}