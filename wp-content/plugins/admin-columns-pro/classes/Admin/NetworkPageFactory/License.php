<?php

namespace ACP\Admin\NetworkPageFactory;

use AC\Admin\View;
use ACP\Admin;
use ACP\Admin\MenuNetworkFactory;
use ACP\AdminColumnsPro;
use ACP\Type\Url\AccountFactory;

final class License extends Admin\PageFactory\License
{

    public function __construct(
        MenuNetworkFactory $menu_factory,
        AdminColumnsPro $plugin,
        AccountFactory $url_factory,
        View\MenuFactory $view_menu_factory
    ) {
        parent::__construct(
            $menu_factory,
            $plugin,
            $url_factory,
            $view_menu_factory
        );
    }

}