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

    private ColumnIterator $sortable_columns;

    public function __construct(string $screen_id, ColumnIterator $sortable_columns)
    {
        $this->screen_id = $screen_id;
        $this->sortable_columns = $sortable_columns;
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
        if ( ! $this->sortable_columns->count()) {
            return (array)$defaults;
        }

        $columns = [];

        foreach ($this->sortable_columns as $column) {
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