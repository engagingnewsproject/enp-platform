<?php

declare(strict_types=1);

namespace ACA\Pods;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACP\Service\IntegrationStatus;

class Pods implements Registerable
{

    private AC\Asset\Location\Absolute $location;

    private DI\Container $container;

    public function __construct(AC\Asset\Location\Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
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