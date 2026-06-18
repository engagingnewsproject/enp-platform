<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableElement;

use ACP\Settings\ListScreen\TableElement;

class EntryFilters extends TableElement
{

    public function __construct()
    {
        parent::__construct('hide_entry_filters', __('Entry Search', 'codepress-admin-columns'), 'element');
    }

}