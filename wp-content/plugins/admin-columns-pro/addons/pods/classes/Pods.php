<?php

declare(strict_types=1);

namespace ACA\Pods;

use AC;
use AC\DI\Container;
use AC\Services;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Service\IntegrationStatus;

class Pods implements Addon
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
        return 'pods';
    }

    public function register(): void
    {
        if ( ! function_exists('pods') ||
             ! defined('PODS_VERSION') ||
             ! version_compare(PODS_VERSION, '2.7', '>=')) {
            return;
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PodFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PodsDeprecatedFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\Columns($this->location),
            new Service\MetaFix(),
            new IntegrationStatus('ac-addon-pods'),
        ]);
    }

}