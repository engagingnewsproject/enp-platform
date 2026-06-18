<?php

declare(strict_types=1);

namespace ACP\Export\ColumnRepository\Filter;

use AC;
use AC\ColumnCollection;
use AC\ColumnIterator;
use AC\ColumnRepository\Filter;

class ExportableColumns implements Filter
{

    public function filter(ColumnIterator $columns): ColumnCollection
    {
        return new ColumnCollection(
            array_filter(
                iterator_to_array($columns),
                [
                    $this,
                    'is_exportable',
                ]
            )
        );
    }

    private function is_exportable(AC\Column $column): bool
    {
        $setting = $column->get_setting('export');

        return $setting && 'on' === $setting->get_input()->get_value();
    }

}