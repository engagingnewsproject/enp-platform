<?php

declare(strict_types=1);

namespace ACA\BP\ListTable;

use AC\ListTable;
use BP_Groups_List_Table;

class Group implements ListTable
{

    private BP_Groups_List_Table $table;

    public function __construct(BP_Groups_List_Table $table)
    {
        $this->table = $table;
    }

    public function render_cell(string $column_id, $row_id): string
    {
        $method = 'column_' . $column_id;

        if (method_exists($this->table, $method)) {
            ob_start();
            (string)call_user_func([$this->table, $method], $this->get_group((string)$row_id));

            return ob_get_clean();
        }

        return (string)apply_filters(
            'bp_groups_admin_get_group_custom_column',
            '',
            $column_id,
            $this->get_group($row_id)
        );
    }

    public function render_row($id): string
    {
        ob_start();

        $this->table->single_row($this->get_group((string)$id));

        return ob_get_clean();
    }

    private function get_group(string $id): array
    {
        return (array)groups_get_group((int)$id);
    }

}