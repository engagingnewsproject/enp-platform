<?php

declare(strict_types=1);

namespace ACP\Export;

use AC\ColumnCollection;
use AC\ListScreen;
use ACP\Export\ColumnRepository\Filter;
use ACP\Table\TableSupport;

class ColumnRepository
{

    public function find_all(ListScreen $list_screen): ColumnCollection
    {
        if ( ! TableSupport::is_export_enabled($list_screen)) {
            return new ColumnCollection();
        }

        return (new Filter\ExportableColumns())->filter(
            $list_screen->get_columns()
        );
    }

}