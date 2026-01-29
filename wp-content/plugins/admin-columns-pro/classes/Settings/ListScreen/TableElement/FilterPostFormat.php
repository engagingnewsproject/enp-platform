<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterPostFormat extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_post_format',
            __('Post Format', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}