<?php

namespace ACP\Editing\TableElement;

use ACP;

class BulkEdit extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct('hide_bulk_edit', __('Bulk Edit', 'codepress-admin-columns'), 'feature');
    }

}