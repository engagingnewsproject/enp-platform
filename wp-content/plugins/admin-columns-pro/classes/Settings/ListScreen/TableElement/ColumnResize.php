<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class ColumnResize extends TableElement
{

    public function __construct()
    {
        parent::__construct('resize_columns', __('Resize Columns', 'codepress-admin-columns'), 'feature');
    }

}