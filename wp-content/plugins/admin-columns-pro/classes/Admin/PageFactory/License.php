<?php

declare(strict_types=1);

namespace ACP\Admin\PageFactory;

use AC\Admin\MenuFactoryInterface;
use AC\Admin\PageFactoryInterface;
use AC\Admin\View;
use ACP;
use ACP\Admin\MenuFactory;
use ACP\Admin\Page;
use ACP\AdminColumnsPro;

class License implements PageFactoryInterface
{

    private MenuFactoryInterface $menu_factory;

    private AdminColumnsPro $plugin;

    private ACP\Type\Url\AccountFactory $url_factory;

    private View\MenuFactory $view_menu_factory;

    public function __construct(
        MenuFactory $menu_factory,
        AdminColumnsPro $plugin,
        ACP\Type\Url\AccountFactory $url_factory,
        View\MenuFactory $view_menu_factory
    ) {
        $this->menu_factory = $menu_factory;
        $this->plugin = $plugin;
        $this->url_factory = $url_factory;
        $this->view_menu_factory = $view_menu_factory;
    }

    public function create(): Page\License
    {
        return new Page\License(
            $this->view_menu_factory->create($this->menu_factory, 'license'),
            $this->plugin,
            $this->url_factory
        );
    }

}