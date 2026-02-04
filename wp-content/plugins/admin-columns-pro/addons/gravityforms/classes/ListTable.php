<?php

declare(strict_types=1);

namespace ACA\GravityForms;

use AC;
use GF_Entry_List_Table;
use GFAPI;

class ListTable implements AC\ListTable
{

    private GF_Entry_List_Table $table;

    public function __construct(GF_Entry_List_Table $table)
    {
        $this->table = $table;
    }

    public function render_cell(string $column_id, $row_id): string
    {
        ob_start();
        $this->table->column_default(GFAPI::get_entry($row_id), $column_id);

        return ob_get_clean();
    }

    public function render_row($id): string
    {
        ob_start();
        $this->table->single_row(GFAPI::get_entry($id));

        return ob_get_clean();
    }

    public function get_list_table(): GF_Entry_List_Table
    {
        return $this->table;
    }

}