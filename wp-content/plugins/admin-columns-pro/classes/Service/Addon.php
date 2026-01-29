<?php

declare(strict_types=1);

namespace ACP\Service;

use AC;
use AC\Registerable;
use AC\Vendor\Psr\Container\ContainerInterface;
use ACP\AdminColumnsPro;

final class Addon implements Registerable
{

    private array $addons;

    private AC\Storage\Repository\IntegrationStatus $status;

    private ContainerInterface $container;

    private AdminColumnsPro $plugin;

    public function __construct(
        array $addons,
        AC\Storage\Repository\IntegrationStatus $status,
        AdminColumnsPro $plugin,
        ContainerInterface $container
    ) {
        $this->addons = $addons;
        $this->status = $status;
        $this->container = $container;
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        foreach ($this->addons as $key => $fqn) {
            if ( ! $this->is_active($key)) {
                continue;
            }

            $addon = new $fqn(
                $this->plugin->get_location()->with_suffix('addons/' . $key),
                $this->container
            );

            $addon->register();
        }
    }

    private function is_active(string $key): bool
    {
        $is_active = $this->status->is_active(sprintf('ac-addon-%s', $key));

        return apply_filters('acp/addon/' . $key . '/active', $is_active);
    }

}