<?php

namespace ACP\Admin\NetworkPageFactory;

use ACP\Admin;
use ACP\Admin\MenuNetworkFactory;
use ACP\AdminColumnsPro;
use ACP\Type\Url\AccountFactory;

final class License extends Admin\PageFactory\License
{

    public function __construct(
        MenuNetworkFactory $menu_factory,
        AdminColumnsPro $plugin,
        AccountFactory $url_factory
    ) {
        parent::__construct(
            $menu_factory,
            $plugin,
            $url_factory
        );
    }

}