<?php

namespace ACP\Admin\NetworkPageFactory;

use AC;
use AC\Admin\PageFactoryInterface;
use ACP\Admin\MenuNetworkFactory;
use ACP\Admin\Page;
use ACP\AdminColumnsPro;

final class Tools implements PageFactoryInterface
{

    private AdminColumnsPro $plugin;

    private MenuNetworkFactory $menu_factory;

    public function __construct(
        AdminColumnsPro $plugin,
        MenuNetworkFactory $menu_factory
    ) {
        $this->plugin = $plugin;
        $this->menu_factory = $menu_factory;
    }

    public function create()
    {
        return new Page\Tools(
            $this->plugin,
            new AC\Admin\View\Menu($this->menu_factory->create('import-export')),
            true
        );
    }

}