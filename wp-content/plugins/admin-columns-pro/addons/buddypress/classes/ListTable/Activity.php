<?php

declare(strict_types=1);

namespace ACA\BP\ListTable;

use AC\ListTable;
use BP_Activity_Activity;
use BP_Activity_List_Table;

class Activity implements ListTable
{

    private BP_Activity_List_Table $table;

    public function __construct(BP_Activity_List_Table $table)
    {
        $this->table = $table;
    }

    public function render_cell(string $column_id, $row_id): string
    {
        $method = 'column_' . $column_id;

        if (method_exists($this->table, $method)) {
            ob_start();
            (string)call_user_func([$this->table, $method], $this->get_activity($row_id));

            return ob_get_clean();
        }

        return (string)apply_filters(
            'bp_activity_admin_get_custom_column',
            '',
            $column_id,
            $this->get_activity($row_id)
        );
    }

    public function render_row($id): string
    {
        ob_start();

        /** @noinspection PhpParamsInspection */
        $this->table->single_row($this->get_activity($id));

        return ob_get_clean();
    }

    private function get_activity($id): array
    {
        return (array)(new BP_Activity_Activity($id));
    }

}