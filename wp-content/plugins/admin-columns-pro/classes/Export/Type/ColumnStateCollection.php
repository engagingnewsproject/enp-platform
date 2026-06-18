<?php

declare(strict_types=1);

namespace ACP\Export\Type;

use AC\Collection;

final class ColumnStateCollection extends Collection
{

    public function __construct(array $column_states = [])
    {
        array_map([$this, 'add'], $column_states);
    }

    public function add(ColumnState $column_state): void
    {
        $this->data[] = $column_state;
    }

    public function current(): ColumnState
    {
        return current($this->data);
    }

}