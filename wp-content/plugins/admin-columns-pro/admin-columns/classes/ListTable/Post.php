<?php

namespace AC\ListTable;

use AC\ListTable;
use WP_Posts_List_Table;

class Post implements ListTable
{

    use WpListTableTrait;

    public function __construct(WP_Posts_List_Table $table)
    {
        $this->table = $table;
    }

    public function get_column_value(string $column, $id): string
    {
        ob_start();

        $method = 'column_' . $column;

        if (method_exists($this->table, $method)) {
            call_user_func([$this->table, $method], get_post($id));
        } else {
            $this->table->column_default(get_post($id), $column);
        }

        return ob_get_clean();
    }

    public function render_row($id): string
    {
        ob_start();

        $this->table->single_row(get_post($id));

        return ob_get_clean();
    }

}