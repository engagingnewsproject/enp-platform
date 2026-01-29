<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Entity\Plugin;
use ACP\Admin\MenuFactory;

class Help extends AC\Admin\PageFactory\Help
{

    public function __construct(Plugin $plugin, MenuFactory $menu_factory)
    {
        parent::__construct($plugin, $menu_factory);
    }

}