<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

use AC;
use AC\Column\ColumnLabelTrait;
use AC\ColumnIterator;
use AC\FormatterCollection;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Column;
use ACP\Export;
use ACP\Export\EscapeData;
use ACP\Export\Formatter\ListTable;

class TableDataFactory
{

    use ColumnLabelTrait;

    private AC\TableScreen $table_screen;

    private EscapeData $escaper;

    private ?AC\ListTable $list_table = null;

    public function __construct(
        AC\TableScreen $table_screen,
        ?EscapeData $escaper = null
    ) {
        $this->escaper = $escaper ?? new EscapeCsv();
        $this->table_screen = $table_screen;
    }

    /**
     * @param ColumnIterator  $columns    Contains the columns to be exported e.g. name, type and settings
     * @param ValueCollection $rows       Contains the row data (IDs or objects) to be exported
     * @param bool            $has_labels When true, headers will be added to the data
     *
     * @return TableData A TableData object populated with the table headers and cell data used for export
     */
    public function create(ColumnIterator $columns, ValueCollection $rows, bool $has_labels): TableData
    {
        $data = new TableData();

        $this->add_cells($data, $columns, $rows);

        if ($has_labels) {
            $this->add_headers($data, $columns);
        }

        /**
         * This hook allows you to modify export data before it is written to the CSV
         *
         * @param TableData       $data         The TableData object being populated
         * @param ValueCollection $rows         The row data (IDs or objects) to be exported
         * @param ColumnIterator  $columns      The columns of the table being used for export
         * @param AC\TableScreen  $table_screen The type of list table being exported e.g. post, user, product etc.
         *                                      More information about the TableScreen object: @link https://docs.admincolumns.com/article/54-class-ac-listscreen
         */
        do_action('ac/export/data', $data, $rows, $columns, $this->table_screen);

        return $data;
    }

    private function add_cells(TableData $data, ColumnIterator $columns, ValueCollection $values): void
    {
        /**
         * @var Column $column
         * @var Value  $value
         */
        foreach ($columns as $column) {
            $renderer = $this->get_renderer($column);

            foreach ($values as $value) {
                $data->add_cell(
                    (string)$value->get_id(),
                    (string)$column->get_id(),
                    $renderer
                        ? (string)$renderer->format($value)
                        : ''
                );
            }
        }
    }

    private function apply_escape_data(AC\Column $column): bool
    {
        return (bool)apply_filters(
            'ac/export/render/escape',
            true,
            $column->get_context(),
            $this->table_screen
        );
    }

    private function get_headers(ColumnIterator $columns): array
    {
        $headers = [];

        /**
         * @var AC\Column $column
         */
        foreach ($columns as $column) {
            $label = $this->get_column_label($column);

            if ($this->apply_escape_data($column)) {
                $label = $this->escaper->escape($label);
            }

            $headers[(string)$column->get_id()] = $label;
        }

        return apply_filters('ac/export/row_headers', $headers, $this->table_screen);
    }

    private function add_headers(TableData $data, ColumnIterator $columns): void
    {
        foreach ($this->get_headers($columns) as $column_id => $label) {
            $data->add_header(
                (string)$column_id,
                (string)$label
            );
        }
    }

    private function get_list_table(): ?AC\ListTable
    {
        if (null === $this->list_table && $this->table_screen instanceof AC\TableScreen\ListTable) {
            $this->list_table = $this->table_screen->list_table();
        }

        return $this->list_table;
    }

    /**
     * Builds a Renderer that turns a row value into an exportable string for the given column.
     *
     * Formatter resolution order:
     *  1. Column-specific export formatters (when the column defines its own export pipeline).
     *  2. Fallback to the ListTable formatter, which scrapes the already-rendered HTML cell from
     *     WP_List_Table. This preserves the on-screen representation for columns without a
     *     dedicated export pipeline.
     *  3. An empty collection when no list table is available (e.g. non-ListTable screens),
     *     in which case the raw value is passed through untouched.
     *
     * The escaper (e.g. CSV-injection protection) is only attached when the 'ac/export/render/escape'
     * filter allows it for this column context.
     */
    private function get_renderer(Column $column): ?Renderer
    {
        $formatters = $column->export();

        if ( ! $formatters) {
            $list_table = $this->get_list_table();

            if ($list_table) {
                $formatters = new FormatterCollection([
                    new ListTable($list_table, $column->get_id()),
                ]);
            } else {
                $formatters = new FormatterCollection();
            }
        }

        $escaper = $this->apply_escape_data($column)
            ? new Export\Formatter\EscapeData($this->escaper)
            : null;

        return new Renderer(
            $formatters,
            $column->get_context(),
            $this->table_screen,
            $escaper
        );
    }

}