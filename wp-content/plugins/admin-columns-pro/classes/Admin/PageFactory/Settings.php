<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Admin\View;
use ACP\Admin\MenuFactory;
use ACP\Admin\ScriptFactory\GeneralSettingsFactory;

class Settings extends AC\Admin\PageFactory\Settings
{

    private GeneralSettingsFactory $settings_factory;

    public function __construct(
        AC\AdminColumns $plugin,
        MenuFactory $menu_factory,
        GeneralSettingsFactory $settings_factory,
        View\MenuFactory $view_menu_factory
    ) {
        parent::__construct($plugin, $menu_factory, $view_menu_factory, true);

        $this->settings_factory = $settings_factory;
    }

    public function create(): AC\Admin\Page\Settings
    {
        $this->settings_factory->create()->enqueue();

        return parent::create();
    }

}