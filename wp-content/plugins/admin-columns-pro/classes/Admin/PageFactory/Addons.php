<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Admin\PageFactoryInterface;
use AC\Integration\IntegrationRepository;
use ACP\Admin\MenuFactory;
use ACP\Admin\Page;

class Addons implements PageFactoryInterface
{

    private $plugin;

    private $integrations;

    private $menu_factory;

    public function __construct(
        AC\AdminColumns $plugin,
        IntegrationRepository $integrations,
        MenuFactory $menu_factory
    ) {
        $this->plugin = $plugin;
        $this->integrations = $integrations;
        $this->menu_factory = $menu_factory;
    }

    public function create()
    {
        return new Page\Addons(
            $this->plugin,
            $this->integrations,
            new AC\Admin\View\Menu($this->menu_factory->create('addons'))
        );
    }

}