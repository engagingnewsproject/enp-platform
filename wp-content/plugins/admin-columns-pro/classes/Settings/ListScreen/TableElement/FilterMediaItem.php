<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterMediaItem extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_media_type',
            __('Media Items', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}