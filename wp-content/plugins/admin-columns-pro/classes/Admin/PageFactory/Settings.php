<?php

namespace ACP\Admin\PageFactory;

use AC;
use ACP\Admin\MenuFactory;
use ACP\Admin\ScriptFactory\GeneralSettingsFactory;

class Settings extends AC\Admin\PageFactory\Settings
{

    private GeneralSettingsFactory $settings_factory;

    public function __construct(
        AC\AdminColumns $plugin,
        MenuFactory $menu_factory,
        GeneralSettingsFactory $settings_factory
    ) {
        parent::__construct($plugin, $menu_factory);

        $this->settings_factory = $settings_factory;
    }

    public function create(): AC\Admin\Page\Settings
    {
        $this->settings_factory->create()->enqueue();

        return parent::create();
    }

}