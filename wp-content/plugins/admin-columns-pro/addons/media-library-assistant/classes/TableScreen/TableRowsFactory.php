<?php

declare(strict_types=1);

namespace ACA\MLA\TableScreen;

use AC;

class TableRowsFactory implements AC\TableScreen\TableRowsFactory
{

    public function create(AC\TableScreen $table_screen): ?AC\TableScreen\TableRows
    {
        if ($table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return new TableRows($table_screen);
        }

        return null;
    }

}