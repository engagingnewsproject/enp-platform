<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class RowActions extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_row_actions',
            sprintf(
                '%s (%s)',
                __('Row Actions', 'codepress-admin-columns'),
                __('Below Title', 'codepress-admin-columns')
            ),
            'element'
        );
    }

}