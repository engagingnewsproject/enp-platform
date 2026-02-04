<?php

namespace ACP\Admin\PageFactory;

use AC;
use ACP\Admin\MenuFactory;

class Help extends AC\Admin\PageFactory\Help
{

    public function __construct(MenuFactory $menu_factory)
    {
        parent::__construct($menu_factory);
    }

}