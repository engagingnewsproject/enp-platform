<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class SubMenu extends TableElement
{

    public function __construct(string $label)
    {
        parent::__construct('hide_submenu', sprintf('%s (Quick Links)', $label), 'element');
    }

}