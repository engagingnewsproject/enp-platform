<?php

namespace ACP\Admin\NetworkPageFactory;

use AC\Admin\View;
use AC\AdminColumns;
use AC\Integration\IntegrationRepository;
use ACP\Admin;
use ACP\Admin\MenuNetworkFactory;

final class Addons extends Admin\PageFactory\Addons
{

    public function __construct(
        AdminColumns $plugin,
        IntegrationRepository $integrations,
        MenuNetworkFactory $menu_factory,
        View\MenuFactory $view_menu_factory
    ) {
        parent::__construct($plugin, $integrations, $menu_factory, $view_menu_factory);
    }

}