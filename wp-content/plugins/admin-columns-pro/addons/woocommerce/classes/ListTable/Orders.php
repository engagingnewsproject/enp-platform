<?php

declare(strict_types=1);

namespace ACA\WC\ListTable;

use AC;
use Automattic;

class Orders implements AC\ListTable
{

    private Automattic\WooCommerce\Internal\Admin\Orders\ListTable $table;

    public function __construct(Automattic\WooCommerce\Internal\Admin\Orders\ListTable $list_table)
    {
        $this->table = $list_table;
    }

    public function render_row($id): string
    {
        ob_start();

        $this->table->single_row(wc_get_order($id));

        return ob_get_clean();
    }

    public function render_cell(string $column_id, $row_id): string
    {
        ob_start();

        $method = 'column_' . $column_id;

        if (method_exists($this->table, $method)) {
            call_user_func([$this->table, $method], wc_get_order($row_id));
        } else {
            $this->table->column_default(wc_get_order($row_id), $column_id);
        }

        return ob_get_clean();
    }

}