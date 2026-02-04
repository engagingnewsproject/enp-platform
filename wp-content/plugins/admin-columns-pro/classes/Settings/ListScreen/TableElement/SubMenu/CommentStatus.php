<?php

namespace ACP\Settings\ListScreen\TableElement\SubMenu;

use ACP\Settings\ListScreen\TableElement\SubMenu;

class CommentStatus extends SubMenu
{

    public function __construct()
    {
        parent::__construct(__('Roles', 'codepress-admin-columns'));
    }

}