<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterPostDate extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_post_date',
            __('Date', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}