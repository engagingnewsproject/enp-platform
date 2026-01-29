<?php

namespace ACP\Editing;

use AC\Asset\Location;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACP\AdminColumnsPro;
use ACP\Editing\Registerable\AdminTableElements;
use ACP\Editing\Registerable\RequestHandlers;
use ACP\Editing\Registerable\Scripts;

final class Addon implements Registerable
{

    private DI\Container $container;

    private Location\Absolute $location;

    public function __construct(DI\Container $container, AdminColumnsPro $plugin)
    {
        $this->container = $container;
        $this->location = $plugin->get_location();
    }

    public function register(): void
    {
        Strategy\AggregateFactory::add($this->container->make(Strategy\PostFactory::class));
        Strategy\AggregateFactory::add($this->container->make(Strategy\UserFactory::class));
        Strategy\AggregateFactory::add($this->container->make(Strategy\SiteFactory::class));
        Strategy\AggregateFactory::add($this->container->make(Strategy\CommentFactory::class));
        Strategy\AggregateFactory::add($this->container->make(Strategy\TaxonomyFactory::class));

        BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\PostFactory::class));
        BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\UserFactory::class));
        BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\CommentFactory::class));
        BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\TaxonomyFactory::class));

        $this->create_services()
             ->register();
    }

    private function create_services(): Services
    {
        return new Services([
            $this->container->make(Scripts::class, ['location' => $this->location]),
            $this->container->make(AdminTableElements::class),
            $this->container->make(RequestHandlers::class),
        ]);
    }

}