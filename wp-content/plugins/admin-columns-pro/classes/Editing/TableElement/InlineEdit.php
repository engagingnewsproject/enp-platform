<?php

namespace ACP\Editing\TableElement;

use ACP;

class InlineEdit extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct('hide_inline_edit', __('Inline Edit', 'codepress-admin-columns'), 'feature');
    }

}