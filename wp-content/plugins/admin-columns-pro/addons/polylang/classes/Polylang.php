<?php

declare(strict_types=1);

namespace ACA\Polylang;

use AC;
use AC\DI\Container;
use AC\Services;
use ACP\Addon;

class Polylang implements Addon
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get_id(): string
    {
        return 'polylang';
    }

    public function register(): void
    {
        if ( ! defined('POLYLANG_VERSION')) {
            return;
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnTypesFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\Columns(),
            new Service\Table(),
        ]);
    }

}