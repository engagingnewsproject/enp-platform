<?php

declare(strict_types=1);

namespace ACA\SeoPress;

use AC;
use AC\DI\Container;
use AC\Plugin\Version;
use AC\Services;
use ACA\SeoPress\TableScreen\RedirectFactory;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;
use AC\Service\View;

class SeoPress implements Addon
{

    private AC\Asset\Location $location;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->location = $container->get(AdminColumnsPro::class)->get_addon_location($this->get_id());
    }

    public function get_id(): string
    {
        return 'seopress';
    }

    private function check_version(): bool
    {
        $version = defined('SEOPRESS_PRO_VERSION') && constant('SEOPRESS_PRO_VERSION')
            ? new Version((string)constant('SEOPRESS_PRO_VERSION'))
            : null;

        return $version &&
               $version->is_valid() &&
               $version->is_gte(new Version('8.9'));
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