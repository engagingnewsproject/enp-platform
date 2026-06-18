<?php

namespace ACP\Sorting\Table\Filter;

use AC\Column;
use AC\ColumnCollection;
use AC\ColumnIterator;
use AC\ColumnRepository\Filter;

class DisabledOriginalColumns implements Filter
{

    private array $column_ids;

    public function __construct(array $column_ids)
    {
        $this->column_ids = $column_ids;
    }

    public function filter(ColumnIterator $columns): ColumnCollection
    {
        return new ColumnCollection(array_filter(iterator_to_array($columns), [$this, 'is_disabled']));
    }

    private function is_disabled(Column $column): bool
    {
        // is original column and not active
        if ( ! in_array((string)$column->get_id(), $this->column_ids, true)) {
            return false;
        }

        $setting = $column->get_setting('sort');

        if ( ! $setting) {
            return false;
        }

        return 'on' !== $setting->get_input()->get_value();
    }
}