<?php

namespace ACP\Admin\NetworkPageFactory;

use AC\Admin\PageFactoryInterface;
use AC\Admin\View;
use ACP\Admin\MenuNetworkFactory;
use ACP\Admin\Page;
use ACP\AdminColumnsPro;

final class Tools implements PageFactoryInterface
{

    private AdminColumnsPro $plugin;

    private MenuNetworkFactory $menu_factory;

    private View\MenuFactory $view_menu_factory;

    public function __construct(
        AdminColumnsPro $plugin,
        MenuNetworkFactory $menu_factory,
        View\MenuFactory $view_menu_factory
    ) {
        $this->plugin = $plugin;
        $this->menu_factory = $menu_factory;
        $this->view_menu_factory = $view_menu_factory;
    }

    public function create(): Page\Tools
    {
        return new Page\Tools(
            $this->plugin,
            $this->view_menu_factory->create($this->menu_factory, 'import-export'),
            true
        );
    }

}