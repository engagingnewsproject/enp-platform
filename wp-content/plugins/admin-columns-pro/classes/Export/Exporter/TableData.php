<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

class TableData
{

    private array $headers = [];

    private array $rows = [];

    public function add_cell(string $row_id, string $column_id, string $value): void
    {
        $this->rows[$row_id][$column_id] = $value;
    }

    public function add_header(string $column_id, string $label): void
    {
        $this->headers[$column_id] = $label;
    }

    public function get_rows(): array
    {
        return $this->rows;
    }

    public function get_headers(): array
    {
        return $this->headers;
    }

    public function has_column(string $column_id): bool
    {
        return isset($this->headers[$column_id]);
    }

    public function has_row(string $row_id): bool
    {
        return isset($this->rows[$row_id]);
    }

    public function get_cell(string $row_id, string $column_id): ?string
    {
        return $this->rows[$row_id][$column_id] ?? null;
    }

    public function get_header(string $column_id): ?string
    {
        return $this->headers[$column_id] ?? null;
    }

    public function remove_row(string $row_id): void
    {
        unset($this->rows[$row_id]);
    }

    public function remove_column(string $column_id): void
    {
        unset($this->headers[$column_id]);

        foreach ($this->rows as &$row) {
            unset($row[$column_id]);
        }
    }

    public function get_column(string $column_id): array
    {
        $values = [];

        foreach ($this->rows as $row_id => $row) {
            if (isset($row[$column_id])) {
                $values[$row_id] = $row[$column_id];
            }
        }

        return $values;
    }

}