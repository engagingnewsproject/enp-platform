<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

use AC;
use AC\Column\ColumnLabelTrait;
use AC\ColumnIterator;
use AC\Formatter\Aggregate;
use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Column;
use ACP\Export;
use ACP\Export\EscapeData;
use ACP\Export\Formatter\ExportFilter;
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

    public function create(ColumnIterator $columns, ValueCollection $values, bool $has_labels): TableData
    {
        $data = new TableData();

        $this->add_cells($data, $columns, $values);

        if ($has_labels) {
            $this->add_headers($data, $columns);
        }

        return $data;
    }

    private function add_cells(TableData $data, ColumnIterator $columns, ValueCollection $values): void
    {
        /**
         * @var Column $column
         * @var Value  $value
         */
        foreach ($columns as $column) {
            $formatter = $this->get_formatter($column);

            foreach ($values as $value) {
                $data->add_cell(
                    (string)$value->get_id(),
                    (string)$column->get_id(),
                    $formatter
                        ? (string)$formatter->format($value)
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

    private function get_formatter(Column $column): ?Aggregate
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

        if ($this->apply_escape_data($column)) {
            $formatters = $formatters->with_formatter(new Export\Formatter\EscapeData($this->escaper));
        }

        $formatters = $formatters->with_formatter(new Separator());
        $formatters = $formatters->with_formatter(
            new ExportFilter(
                $column->get_context(),
                $this->table_screen
            )
        );

        return new Aggregate($formatters);
    }

}