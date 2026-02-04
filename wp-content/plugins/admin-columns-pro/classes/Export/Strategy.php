<?php

declare(strict_types=1);

namespace ACP\Export;

use AC\ColumnIterator;

/**
 * Base class for governing exporting for a list screen that is exportable. This class should be
 * extended, generally, per list screen. Furthermore, each instance of this class should be linked
 * to an Admin Columns list screen object
 */
abstract class Strategy
{

    protected ?array $ids = null;

    protected ?ColumnIterator $columns = null;

    protected ?int $counter = null;

    protected int $items_per_iteration = 250;

    abstract public function handle_export(): void;

    public function set_ids(array $ids): self
    {
        $this->ids = $ids;

        return $this;
    }

    public function set_columns(ColumnIterator $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function set_counter(int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }

    public function set_items_per_iteration(int $items_per_iteration): self
    {
        $this->items_per_iteration = $items_per_iteration;

        return $this;
    }

    public function get_items_per_iteration(): int
    {
        return $this->items_per_iteration;
    }

}