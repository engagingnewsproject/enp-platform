<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class ColumnOrder extends TableElement
{

    public function __construct()
    {
        parent::__construct('column_order', __('Column Order', 'codepress-admin-columns'), 'feature');
    }

}