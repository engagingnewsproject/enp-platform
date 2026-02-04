<?php

namespace ACP\Search\Settings\TableElement;

use ACP\Settings\ListScreen\TableElement;

class SmartFilters extends TableElement
{

    public const NAME = 'hide_smart_filters';

    public function __construct()
    {
        parent::__construct(self::NAME, __('Smart Filters', 'codepress-admin-columns'), 'feature');
    }

}