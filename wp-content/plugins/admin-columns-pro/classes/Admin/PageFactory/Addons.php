<?php

namespace ACP\Admin\PageFactory;

use AC\Admin\PageFactoryInterface;
use AC\Admin\View;
use AC\AdminColumns;
use AC\Integration\IntegrationRepository;
use ACP\Admin\MenuFactory;
use ACP\Admin\Page;

class Addons implements PageFactoryInterface
{

    private AdminColumns $plugin;

    private IntegrationRepository $integrations;

    private MenuFactory $menu_factory;

    private View\MenuFactory $view_menu_factory;

    public function __construct(
        AdminColumns $plugin,
        IntegrationRepository $integrations,
        MenuFactory $menu_factory,
        View\MenuFactory $view_menu_factory
    ) {
        $this->plugin = $plugin;
        $this->integrations = $integrations;
        $this->menu_factory = $menu_factory;
        $this->view_menu_factory = $view_menu_factory;
    }

    public function create(): Page\Addons
    {
        return new Page\Addons(
            $this->plugin,
            $this->integrations,
            $this->view_menu_factory->create($this->menu_factory, 'addons')
        );
    }

}