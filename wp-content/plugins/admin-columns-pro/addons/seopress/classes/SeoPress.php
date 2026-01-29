<?php

declare(strict_types=1);

namespace ACA\SeoPress;

use AC;
use AC\Plugin\Version;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA\SeoPress\TableScreen\RedirectFactory;
use ACP\Service\IntegrationStatus;
use ACP\Service\View;

class SeoPress implements Registerable
{

    private AC\Asset\Location\Absolute $location;

    private DI\Container $container;

    public function __construct(AC\Asset\Location\Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    private function check_version(): bool
    {
        $version = defined('SEOPRESS_PRO_VERSION')
            ? new Version(SEOPRESS_PRO_VERSION)
            : null;

        return $version && $version->is_valid() && $version->is_gte(new Version('8.9'));
    }

    public function register(): void
    {
        if ( ! $this->check_version()) {
            return;
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PostFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\RedirectFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\RedirectFactory::class));
        AC\TableScreenFactory\Aggregate::add(
            new RedirectFactory()
        );

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroup($this->location),
            new IntegrationStatus('ac-addon-seopress'),
            new View($this->location),
        ]);
    }

}