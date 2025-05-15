<?php

declare(strict_types=1);

namespace ACP;

use AC\Asset\Location\Absolute;
use AC\Entity\Plugin;
use AC\Vendor\Psr\Container\ContainerInterface;
use LogicException;

class Container
{

    private static $instance;

    public static function set_container(ContainerInterface $container): void
    {
        if (self::$instance) {
            throw new LogicException('Container is already set.');
        }

        self::$instance = $container;
    }

    public static function get_location(): Absolute
    {
        return self::$instance->get(Absolute::class);
    }

    public static function get_plugin(): Plugin
    {
        return self::$instance->get(Plugin::class);
    }

}