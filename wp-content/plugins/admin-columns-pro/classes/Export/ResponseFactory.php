<?php

declare(strict_types=1);

namespace ACP\Export;

use ACP\Export\Exporter\CsvFactory;
use ACP\Export\Exporter\TableData;

class ResponseFactory
{

    private CsvFactory $csv_factory;

    public function __construct(CsvFactory $csv_factory)
    {
        $this->csv_factory = $csv_factory;
    }

    public function create(TableData $table_data): void
    {
        $csv = $this->csv_factory->create($table_data);

        wp_send_json_success([
            'rows'               => $csv->get_contents(),
            'num_rows_processed' => $csv->count_rows(),
        ]);
    }
}