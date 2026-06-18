<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

class CsvFactory
{

    public function create(TableData $table_data): Csv
    {
        return new Csv(
            $table_data->get_rows(),
            $table_data->get_headers(),
            $this->get_delimiter()
        );
    }

    private function get_delimiter(): string
    {
        return (string)apply_filters('ac/export/exporter_csv/delimiter', ',');
    }

}