<?php

declare(strict_types=1);

namespace ACP;

use AC;
use AC\ColumnIterator;
use AC\ColumnRepository\Filter\ColumnNames;
use AC\ColumnRepository\Filter\ColumnSetting;
use AC\ListScreen;
use ACP\ConditionalFormat\ColumnRepository\FilterByConditionalFormat;
use ACP\Export\ColumnRepository\Filter\ExportableColumns;

class ColumnRepository
{

    public function find_all_with_export(ListScreen $list_screen, ?array $column_names = null): ColumnIterator
    {
        $columns = (new ExportableColumns())->filter($list_screen->get_columns());

        if ( ! $column_names) {
            return $columns;
        }

        $columns = (new ColumnNames($column_names))->filter($columns);

        return (new AC\ColumnRepository\Sort\ColumnNames($column_names))->sort($columns);
    }

    public function find_all_with_conditional_formatting(ListScreen $list_screen): ColumnIterator
    {
        return (new FilterByConditionalFormat())->filter($list_screen->get_columns());
    }

    public function find_all_with_editing(ListScreen $list_screen): ColumnIterator
    {
        return (new ColumnSetting('edit'))->filter($list_screen->get_columns());
    }

    public function find_all_with_sorting(ListScreen $list_screen): ColumnIterator
    {
        return (new ColumnSetting('sort'))->filter($list_screen->get_columns());
    }

    public function find_all_with_search(ListScreen $list_screen): ColumnIterator
    {
        return (new ColumnSetting('search'))->filter($list_screen->get_columns());
    }

}