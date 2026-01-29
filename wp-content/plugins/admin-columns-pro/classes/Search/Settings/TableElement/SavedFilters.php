<?php

namespace ACP\Search\Settings\TableElement;

use ACP\Settings\ListScreen\TableElement;

class SavedFilters extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_segments',
            __('Saved Filters', 'codepress-admin-columns'),
            'feature',
            SmartFilters::NAME
        );
    }

}