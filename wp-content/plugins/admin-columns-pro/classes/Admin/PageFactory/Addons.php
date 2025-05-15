<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Admin\PageFactoryInterface;
use AC\Asset\Location;
use AC\IntegrationRepository;
use ACP\Admin\MenuFactory;
use ACP\Admin\Page;
use ACP\Settings\General\IntegrationStatus;

class Addons implements PageFactoryInterface
{

    private $location;

    private $integrations;

    private $menu_factory;

    private $integration_status;

    public function __construct(
        Location\Absolute $location,
        IntegrationRepository $integrations,
        MenuFactory $menu_factory,
        IntegrationStatus $integration_status
    ) {
        $this->location = $location;
        $this->integrations = $integrations;
        $this->menu_factory = $menu_factory;
        $this->integration_status = $integration_status;
    }

    public function create(): Page\Addons
    {
        return new Page\Addons(
            $this->integration_status,
            $this->location,
            $this->integrations,
            new AC\Admin\View\Menu($this->menu_factory->create('addons'))
        );
    }

}