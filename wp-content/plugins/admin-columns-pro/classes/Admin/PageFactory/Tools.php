<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Admin\PageFactoryInterface;
use ACP\Admin\MenuFactory;
use ACP\Admin\Page;
use ACP\AdminColumnsPro;

class Tools implements PageFactoryInterface
{

    private AdminColumnsPro $plugin;

    private MenuFactory $menu_factory;

    public function __construct(
        AdminColumnsPro $plugin,
        MenuFactory $menu_factory
    ) {
        $this->plugin = $plugin;
        $this->menu_factory = $menu_factory;
    }

    public function create(): Page\Tools
    {
        return new Page\Tools(
            $this->plugin,
            new AC\Admin\View\Menu($this->menu_factory->create('import-export')),
            false
        );
    }

}