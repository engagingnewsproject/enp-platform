<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterCommentType extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_comment_type',
            __('Comment Types', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}