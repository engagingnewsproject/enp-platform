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

}