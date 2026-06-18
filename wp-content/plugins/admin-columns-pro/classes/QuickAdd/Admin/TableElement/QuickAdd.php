<?php

namespace ACP\QuickAdd\Admin\TableElement;

use ACP;

class QuickAdd extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_new_inline',
            sprintf(
                '%s (%s)',
                __('Add Row', 'codepress-admin-columns'),
                __('Quick Add', 'codepress-admin-columns')
            ),
            'feature'
            ,
            null,
            false
        );
    }

    public function is_enabled_by_default(): bool
    {
        return false;
    }

}