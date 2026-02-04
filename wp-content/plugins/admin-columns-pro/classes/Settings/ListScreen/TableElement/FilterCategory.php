<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterCategory extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_category',
            __('Category', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}