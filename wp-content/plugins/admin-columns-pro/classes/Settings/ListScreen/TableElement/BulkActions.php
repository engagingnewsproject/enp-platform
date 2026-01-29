<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class BulkActions extends TableElement
{

    public function __construct()
    {
        parent::__construct('hide_bulk_actions', __('Bulk Actions', 'codepress-admin-columns'), 'element');
    }

}