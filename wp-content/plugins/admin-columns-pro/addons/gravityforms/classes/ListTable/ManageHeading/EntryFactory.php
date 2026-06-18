<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\ManageHeading;

use AC\Registerable;
use AC\TableScreen;
use ACA\GravityForms\TableScreen\Entry;

class EntryFactory implements TableScreen\ManageHeadingFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Entry;
    }

    public function create(TableScreen $table_screen, array $headings): Registerable
    {
        return new EntryHeadings($headings);
    }

}