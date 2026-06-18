<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class Search extends TableElement
{

    public function __construct()
    {
        parent::__construct('hide_search', __('Search', 'codepress-admin-columns'), 'element');
    }

}