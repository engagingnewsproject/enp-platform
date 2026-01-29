<?php

namespace ACP\Editing\TableElement;

use ACP;

class BulkDelete extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct('hide_bulk_delete', __('Bulk Delete', 'codepress-admin-columns'), 'feature');
    }

}