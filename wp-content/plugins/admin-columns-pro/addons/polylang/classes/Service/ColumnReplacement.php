<?php

declare(strict_types=1);

namespace ACA\Polylang\Service;

use AC\Column;
use AC\ColumnCollection;
use AC\ColumnIterator;
use AC\ColumnNamesTrait;
use AC\Registerable;
use AC\TableScreen;
use ACA\Polylang\ColumnFactory\Language;

class ColumnReplacement implements Registerable
{

    use ColumnNamesTrait;

    private array $polylang_columns;

    private TableScreen $table_screen;

    private ColumnIterator $columns;

    public function __construct(TableScreen $table_screen, ColumnIterator $columns)
    {
        $this->table_screen = $table_screen;
        $this->polylang_columns = [];
        $this->columns = $columns;
    }

    public function register(): void
    {
        $screen_id = $this->table_screen->get_screen_id();

        add_filter("manage_{$screen_id}_columns", [$this, 'set_dynamic_columns'], 199);
        add_filter("manage_{$screen_id}_columns", [$this, 're_add_dynamic_columns'], 201);
        add_filter("manage_{$screen_id}_columns", [$this, 'remove_placeholder_columns'], 202);
    }

    public function set_dynamic_columns($headings)
    {
        foreach ($headings as $key => $label) {
            if (strpos($key, 'language_') !== false) {
                $this->polylang_columns[$key] = $label;
            }
        }

        return $headings;
    }

    private function get_placeholder_column_name(): ?string
    {
        $columns = $this->get_placeholder_column_names();

        return empty($columns) ? null : reset($columns);
    }

    private function get_placeholder_column_names(): array
    {
        $columns = new ColumnCollection(
            array_filter(iterator_to_array($this->columns), static function (Column $column) {
                return $column->get_type() === Language::COLUMN_TYPE;
            })
        );

        return $this->get_column_names_from_collection($columns);
    }

    public function remove_placeholder_columns($headings)
    {
        foreach ($this->get_placeholder_column_names() as $key) {
            if (array_key_exists($key, $headings)) {
                unset($headings[$key]);
            }
        }

        return $headings;
    }

    public function re_add_dynamic_columns($headings)
    {
        $replacement_key = $this->get_placeholder_column_name();

        return $replacement_key
            ? $this->replace_placeholder_column($headings, $replacement_key)
            : $headings;
    }

    private function replace_placeholder_column($headings, $replacement_key)
    {
        foreach ($headings as $key => $label) {
            if ($replacement_key === $key) {
                $index = array_search($replacement_key, array_keys($headings), true);

                $headings = array_slice($headings, 0, $index, true) + $this->polylang_columns + array_slice(
                        $headings,
                        $index,
                        count($headings) - $index,
                        true
                    );
            }
        }

        return $headings;
    }

}