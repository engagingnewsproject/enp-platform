<?php

namespace ACP\Filtering;

use AC\Registerable;
use AC\Services;
use AC\Vendor\Psr\Container\ContainerInterface;

class Addon implements Registerable
{

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $services = new Services();

        DefaultFilters\Aggregate::add($this->container->get(DefaultFilters\Comment::class));
        DefaultFilters\Aggregate::add($this->container->get(DefaultFilters\Post::class));
        DefaultFilters\Aggregate::add($this->container->get(DefaultFilters\Media::class));

        $services_fqn = [
            Service\Table\FilterRequestHandler::class,
            Service\Table\FilterContainers::class,
            Service\Table\Scripts::class,
        ];

        foreach ($services_fqn as $service) {
            $services->add($this->container->get($service));
        }

        $services->register();
    }

}