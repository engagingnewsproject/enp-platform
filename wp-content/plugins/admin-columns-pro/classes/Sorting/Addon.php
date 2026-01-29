<?php

namespace ACP\Sorting;

use AC\Registerable;
use AC\Services;
use AC\Vendor\Psr\Container\ContainerInterface;
use ACP\Sorting\Service\Table;

class Addon implements Registerable
{

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            $this->container->get(Controller\AjaxResetSorting::class),
            $this->container->get(Table::class),
        ]);
    }

}