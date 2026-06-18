<?php

namespace ACP\Settings\ListScreen\TableElement\SubMenu;

use ACP\Settings\ListScreen\TableElement\SubMenu;

class PostStatus extends SubMenu
{

    public function __construct()
    {
        parent::__construct(__('Status', 'codepress-admin-columns'));
    }

}