<?php

declare(strict_types=1);

namespace ACP\Sorting\TableScreen\ManageHeading;

use AC\Column\ColumnLabelTrait;
use AC\ColumnIterator;
use AC\Registerable;

class ScreenColumns implements Registerable
{

    use ColumnLabelTrait;

    private string $screen_id;

    private ColumnIterator $columns;

    public function __construct(string $screen_id, ColumnIterator $columns)
    {
        $this->screen_id = $screen_id;
        $this->columns = $columns;
    }

    /**
     * @see \WP_List_Table::get_column_info()
     */
    public function register(): void
    {
        add_filter(
            'manage_' . $this->screen_id . '_sortable_columns',
            [$this, 'add_sortable_headings'],
            300
        );
    }

    public function add_sortable_headings($defaults): array
    {
        if ( ! $this->columns->count()) {
            return (array)$defaults;
        }

        $columns = [];

        foreach ($this->columns as $column) {
            $name = (string)$column->get_id();

            if (isset($defaults[$name])) {
                $columns[$name] = $defaults[$name];
                continue;
            }

            $label = $this->get_column_label($column);

            $columns[$name] = [
                $name,
                false,
                $label,
                sprintf(__('Table ordered by %s.', 'codepress-admin-columns'), $label),
            ];
        }

        return $columns;
    }

}