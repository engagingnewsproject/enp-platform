<?php

namespace ACP\QuickAdd;

use AC;
use AC\ListScreenRepository\Storage;
use AC\Services;
use ACP\AdminColumnsPro;
use ACP\QuickAdd\Admin\TableElement;
use ACP\QuickAdd\Model\Factory;
use ACP\QuickAdd\Model\PostFactory;

class Addon implements AC\Registerable
{

    private $storage;

    private $location;

    private $request;

    public function __construct(Storage $storage, AdminColumnsPro $plugin, AC\Request $request)
    {
        $this->storage = $storage;
        $this->location = $plugin->get_location();
        $this->request = $request;
    }

    public function register(): void
    {
        Factory::add_factory(new PostFactory());

        $this->create_services()
             ->register();
    }

    private function create_services(): Services
    {
        $preference = new Table\Preference\ShowButton();
        $filter = new Filter();

        return new Services([
            new Controller\AjaxNewItem($this->storage, $this->request),
            new Controller\AjaxScreenOption($this->storage, $preference),
            new Table\Loader($this->location, new TableElement\QuickAdd(), $preference, $filter),
            new Admin\Settings($filter),
        ]);
    }

}