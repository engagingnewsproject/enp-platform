<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\ColumnRepository;

use AC\Column;
use AC\ColumnCollection;
use AC\ColumnIterator;
use AC\ColumnRepository\Filter;
use ACP;

class FilterByConditionalFormat implements Filter
{

    public function filter(ColumnIterator $columns): ColumnCollection
    {
        return new ColumnCollection(array_filter(iterator_to_array($columns), [$this, 'is_valid']));
    }

    private function is_valid(Column $column): bool
    {
        return $column instanceof ACP\Column && $column->conditional_format();
    }

}